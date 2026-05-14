<?php
require __DIR__ . '/api/auth.php';
requireRole(['empleado', 'supervisor', 'coordinador', 'admin']);
require_once __DIR__ . '/api/stay_lifecycle.php';

// Obtener datos del usuario autenticado desde la base de datos
$employee = getSessionUser();
$activeStay = null;
$stayHistory = [];
$pendingRequest = null;
$config = require __DIR__ . '/api/config.php';
$mysqli = @new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);
if (!$mysqli->connect_errno) {
    try {
        $mysqli->set_charset($config['charset']);
        $mysqli->query("SET NAMES {$config['charset']}");
        expireStaysAndPendingRequests($mysqli);

        // Asegurar tabla stays (para estancias activas + historico)
        $mysqli->query("
            CREATE TABLE IF NOT EXISTS stays (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                fecha_inicio DATE NOT NULL,
                fecha_fin DATE NOT NULL,
                motivo VARCHAR(150) NULL,
                group_id INT NULL,
                horario TINYINT(1) NOT NULL DEFAULT 1,
                institucion VARCHAR(255) NULL,
                pais VARCHAR(255) NULL,
                status ENUM('active','archived') NOT NULL DEFAULT 'active',
                archived_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_stays_employee (employee_id),
                INDEX idx_stays_status (status)
            )
        ");
        $mysqli->query("
            CREATE TABLE IF NOT EXISTS group_join_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                group_id INT NOT NULL,
                requested_by_email VARCHAR(255) NOT NULL,
                requested_by_name VARCHAR(255) NOT NULL,
                motivo VARCHAR(150) NULL,
                fecha_inicio DATE NOT NULL,
                fecha_fin DATE NOT NULL,
                horario TINYINT(1) NOT NULL DEFAULT 1,
                institucion VARCHAR(255) NULL,
                pais VARCHAR(255) NULL,
                approval_token VARCHAR(64) NOT NULL,
                status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                email_sent_at DATETIME NULL,
                approved_at DATETIME NULL,
                approved_by_employee_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY ux_group_join_requests_token (approval_token),
                INDEX idx_group_join_requests_employee (employee_id),
                INDEX idx_group_join_requests_group (group_id),
                INDEX idx_group_join_requests_status (status)
            )
        ");

        $stmt = $mysqli->prepare('SELECT id, nombre, apellidos, dni_pasaporte, username, fecha_nacimiento, email, phone_prefix, phone_number, foto_url, rol FROM employees WHERE id = ? LIMIT 1');
        if ($stmt && isset($employee['id'])) {
            $id = (int) $employee['id'];
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $employee = $result->fetch_assoc();
            }
            $stmt->close();
        }

        // Estancia activa (si existe) + historico (archivadas)
        if (isset($employee['id'])) {
            $targetId = (int) $employee['id'];

            $activeStmt = $mysqli->prepare("SELECT s.id, s.employee_id, s.fecha_inicio, s.fecha_fin, s.motivo, s.group_id, g.name AS group_name, s.horario, s.institucion, s.pais
                FROM stays s
                LEFT JOIN groups g ON g.id = s.group_id
                WHERE s.employee_id = ? AND s.status = 'active'
                ORDER BY s.updated_at DESC LIMIT 1");
            if ($activeStmt) {
                $activeStmt->bind_param('i', $targetId);
                $activeStmt->execute();
                $activeRes = $activeStmt->get_result();
                if ($activeRes && $activeRes->num_rows === 1) {
                    $activeStay = $activeRes->fetch_assoc();
                }
                $activeStmt->close();
            }

            // Si la estancia activa ya termino, no mostrarla como activa
            if ($activeStay) {
                $today = new DateTime('today');
                $fin = !empty($activeStay['fecha_fin']) ? new DateTime($activeStay['fecha_fin']) : null;
                if ($fin && $fin < $today && ($activeStay['fecha_fin'] ?? '') !== '2100-01-01') {
                    $activeStay = null;
                }
            }

            $histStmt = $mysqli->prepare("SELECT s.id, s.employee_id, s.fecha_inicio, s.fecha_fin, s.motivo, s.group_id, g.name AS group_name, s.horario, s.institucion, s.pais, s.archived_at
                FROM stays s
                LEFT JOIN groups g ON g.id = s.group_id
                WHERE s.employee_id = ? AND s.status = 'archived'
                ORDER BY s.fecha_inicio DESC, s.fecha_fin DESC, COALESCE(s.archived_at, s.updated_at) DESC");
            if ($histStmt) {
                $histStmt->bind_param('i', $targetId);
                $histStmt->execute();
                $histRes = $histStmt->get_result();
                while ($histRes && ($row = $histRes->fetch_assoc())) {
                    $stayHistory[] = $row;
                }
                $histStmt->close();
            }

            $pendingStmt = $mysqli->prepare("SELECT r.id, r.fecha_inicio, r.fecha_fin, r.motivo, r.horario, r.institucion, r.pais, r.status, g.name AS group_name
                FROM group_join_requests r
                LEFT JOIN groups g ON g.id = r.group_id
                WHERE r.employee_id = ? AND r.status = 'pending'
                ORDER BY r.created_at DESC
                LIMIT 1");
            if ($pendingStmt) {
                $pendingStmt->bind_param('i', $targetId);
                $pendingStmt->execute();
                $pendingRes = $pendingStmt->get_result();
                if ($pendingRes && $pendingRes->num_rows === 1) {
                    $pendingRequest = $pendingRes->fetch_assoc();
                }
                $pendingStmt->close();
            }
        }
    } catch (Throwable $e) {
        // Si falla la consulta (schema incompleto, etc), mantener datos de sesion.
        $activeStay = null;
        $stayHistory = [];
        $pendingRequest = null;
    } finally {
        $mysqli->close();
    }
}

// Helper para escapar texto (usa '-' como valor por defecto)
$safe = function ($key, $fallback = '-') use ($employee, $activeStay) {
    // Campos que pertenecen a la estancia activa (no al perfil del empleado)
    $preferStayKeys = ['fecha_inicio', 'fecha_fin', 'motivo', 'institucion', 'pais', 'horario', 'group_id', 'group_name'];

    if (is_array($activeStay) && in_array($key, $preferStayKeys, true) && array_key_exists($key, $activeStay) && $activeStay[$key] !== null && $activeStay[$key] !== '') {
        return htmlspecialchars((string) $activeStay[$key], ENT_QUOTES, 'UTF-8');
    }
    if (is_array($employee) && array_key_exists($key, $employee) && $employee[$key] !== null && $employee[$key] !== '') {
        return htmlspecialchars((string) $employee[$key], ENT_QUOTES, 'UTF-8');
    }
    if (is_array($activeStay) && array_key_exists($key, $activeStay) && $activeStay[$key] !== null && $activeStay[$key] !== '') {
        return htmlspecialchars((string) $activeStay[$key], ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars((string) $fallback, ENT_QUOTES, 'UTF-8');
};
$fotoUrl = !empty($employee['foto_url'])
    ? $employee['foto_url']
    : 'https://i.pravatar.cc/160?u=' . urlencode($employee['email'] ?? ($employee['username'] ?? 'user'));

$formatFechaFin = function ($value) {
    $date = (string) ($value ?? '');
    if ($date === '2100-01-01') return 'Personal indefinido';
    return $date !== '' ? htmlspecialchars($date, ENT_QUOTES, 'UTF-8') : '-';
};

$formatHorario = function ($value) {
    return ((int) $value === 1) ? 'Completo' : 'Solo lectivo';
};

$hasActiveStay = is_array($activeStay) && !empty($activeStay);
$hasPendingRequest = is_array($pendingRequest) && !empty($pendingRequest);
$fullName = htmlspecialchars(trim(($employee['nombre'] ?? '') . ' ' . ($employee['apellidos'] ?? '')), ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>

<html class="light" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>GestIUBO - Usuario</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="icon" href="/iubolab/imagenes/icono_circulo.png" type="image/png">
    <link rel="icon" type="image/png" sizes="32x32" href="/iubolab/imagenes/icono_circulo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/iubolab/imagenes/icono_circulo.png">
    <link rel="apple-touch-icon" href="/iubolab/imagenes/icono_circulo.png">
    <link href="https://fonts.googleapis.com/css2?family=Argentum+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#5c068c",
                        "background-light": "#f8f6f6",
                        "background-dark": "#221610",
                    },
                    fontFamily: {
                        "display": ["Argentum Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Argentum Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark min-h-screen text-slate-900 dark:text-slate-100">
    <div class="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
        <div class="layout-container flex h-full grow flex-col">
            <!-- Navigation / Header -->
            <header class="hidden md:flex md:flex-row md:items-center md:justify-between gap-3 border-b border-solid border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 md:px-10 py-4 fixed top-0 left-0 right-0 z-50">
                <div class="flex items-center gap-4 flex-wrap">
                    <img alt="Logo de la Institución" class="h-10 w-auto object-contain" src="/iubolab/imagenes/instituto-biorganica-agonzalez-original.png" />
                    <h2 id="headerTitle" class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight tracking-[-0.015em] border-l border-slate-300 dark:border-slate-700 pl-4">Bienvenido/a</h2>
                    <?php if ($fullName): ?>
                        <span id="greetText" data-fullname="<?php echo $fullName; ?>" class="text-sm text-slate-500 dark:text-slate-400 pl-4">Hola, <?php echo $fullName; ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                    <button id="mobileMenuToggleEmployee" type="button" class="md:hidden flex shrink-0 items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary bg-white dark:bg-slate-900 text-primary hover:bg-primary hover:text-white transition-colors" aria-label="Abrir menú">
                        <span class="material-symbols-outlined text-base">menu</span>
                    </button>
                    <div class="hidden md:flex items-center gap-3">
                        <?php if ($hasActiveStay || $hasPendingRequest) : ?>
                            <button type="button" disabled class="flex shrink-0 cursor-not-allowed items-center justify-center overflow-hidden rounded-xl h-11 px-5 border border-slate-300 text-slate-400 text-sm font-bold leading-normal tracking-[0.015em] bg-slate-100 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500">
                                <span id="newStayTextDisabled" class="truncate">Nueva estancia</span>
                            </button>
                        <?php else : ?>
                            <a href="nueva-estancia" class="flex shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl h-11 px-5 border border-primary text-primary text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary hover:text-white transition-colors">
                                <span id="newStayText" class="truncate">Nueva estancia</span>
                            </a>
                        <?php endif; ?>
                        <a href="/iubolab/quimicos.php" aria-label="Químicos" title="Químicos" class="flex shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary text-primary text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary hover:text-white transition-colors"><span class="material-symbols-outlined text-xl">science</span></a>
                        <button type="button" id="lang-toggle" onclick="toggleLanguage()" class="flex shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary bg-white dark:bg-slate-900 text-primary text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary hover:text-white transition-colors">EN</button>
                        <a href="#" onclick="logout(); return false;" aria-label="Cerrar sesión" title="Cerrar sesión" class="flex shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary bg-white dark:bg-slate-900 text-primary text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-base">power_settings_new</span>
                        </a>
                    </div>
                </div>
                <div id="mobileMenuEmployee" class="hidden md:hidden w-full border-t border-slate-200 dark:border-slate-800 pt-3 flex flex-col gap-3">
                    <button type="button" id="lang-toggle-mobile" onclick="toggleLanguage()" class="w-full flex items-center justify-center rounded-xl h-11 border border-primary text-primary text-sm font-bold hover:bg-primary hover:text-white transition-colors">EN</button>
                    <?php if ($hasActiveStay || $hasPendingRequest) : ?>
                        <button type="button" disabled class="w-full rounded-xl h-11 border border-slate-300 text-slate-400 text-sm font-bold bg-slate-100 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500">
                            <span id="newStayTextDisabledMobile" class="truncate">Nueva estancia</span>
                        </button>
                    <?php else : ?>
                        <a href="nueva-estancia" class="w-full flex items-center justify-center rounded-xl h-11 border border-primary text-primary text-sm font-bold hover:bg-primary hover:text-white transition-colors">
                            <span id="newStayTextMobile" class="truncate">Nueva estancia</span>
                        </a>
                    <?php endif; ?>
                    <a href="/iubolab/quimicos.php" aria-label="Químicos" title="Químicos" class="w-full flex items-center justify-center rounded-xl h-11 border border-primary text-primary text-sm font-bold hover:bg-primary hover:text-white transition-colors"><span class="material-symbols-outlined text-xl">science</span></a>
                    <a href="#" onclick="logout(); return false;" class="w-full flex items-center justify-center rounded-xl h-11 border border-primary text-primary text-sm font-bold hover:bg-primary hover:text-white transition-colors">Cerrar sesión</a>
                </div>
            </header>

            <main class="flex-1 flex justify-center pt-6 md:pt-28 pb-10 px-4 md:px-0">
                <div class="w-full max-w-[860px] flex flex-col gap-6">
                    <!-- Profile Card -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 p-8">
                        <div class="flex flex-col gap-8">
                            <!-- Section: Información Personal -->
                            <section>
                                <h3 class="text-primary text-sm font-bold uppercase tracking-wider mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">person</span>
                                    <span id="secPersonal">Información Personal</span>
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5 flex flex-col items-center justify-center gap-3 md:row-span-2 h-full">
                                        <img class="h-28 w-28 rounded-full object-cover border border-slate-200 dark:border-slate-700" src="<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Foto del empleado" />
                                        <p id="lblFoto" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Foto</p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5 h-full">                                         <p id="lblNombre" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Nombre</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100"><?= $safe('nombre') ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5 h-full">                                         <p id="lblApellidos" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Apellidos</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100"><?= $safe('apellidos') ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5 md:col-span-2 h-full">                                         <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Email</p>                                         <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100 break-all leading-relaxed"><?= $safe('email') ?></p>                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5 h-full">                                         <p id="lblNacimiento" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Fecha de Nacimiento</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100"><?= $safe('fecha_nacimiento') ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5 h-full">                                         <p id="lblDni" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">DNI/Pasaporte</p>                                         <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100 break-words"><?= $safe('dni_pasaporte') ?></p>                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5 h-full">                                         <p id="lblTelefono" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Teléfono</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100"><?= $safe('phone_prefix') ?> <?= $safe('phone_number') ?></p>
                                    </div>
                                </div>
                            </section>
                            <hr class="border-slate-100 dark:border-slate-800" />
                            <!-- Section: Origen Académico -->
                            <section>
                                <h3 class="text-primary text-sm font-bold uppercase tracking-wider mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">school</span>
                                    <span id="secAcademico">Origen Académico</span>
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5">
                                        <p id="lblInstitucion" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Institución</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100"><?= $safe('institucion') ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5">
                                        <p id="lblPais" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">País</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100"><?= $safe('pais') ?></p>
                                    </div>
                                </div>
                            </section>
                            <hr class="border-slate-100 dark:border-slate-800" />
                            <!-- Section: Detalles de Incorporación -->
                            <section>
                                <h3 class="text-primary text-sm font-bold uppercase tracking-wider mb-6 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">science</span>
                                    <span id="secIncorporacion">Detalles de la Incorporación</span>
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5">
                                        <p id="lblMotivo" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Motivo</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100"><?= $safe('motivo') ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5">
                                        <p id="lblInicio" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Fecha de Inicio</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100"><?= $safe('fecha_inicio') ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5">
                                        <p id="lblFin" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Fecha de Finalización</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">
                                            <?php
                                            $fechaFin = $activeStay['fecha_fin'] ?? '';
                                            echo $formatFechaFin($fechaFin);
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5">
                                        <p id="lblGrupo" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Grupo</p>
                                        <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">
                                            <?php if ($hasPendingRequest && !$hasActiveStay) : ?>
                                                <?= htmlspecialchars(($pendingRequest['group_name'] ?? '-') . ' - Pendiente de aprobación / Pending approval', ENT_QUOTES, 'UTF-8') ?>
                                            <?php else : ?>
                                                <?= $safe('group_name') ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                            </section>
                        </div>
                    </div>
                    <!-- Historial de estancias -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 p-8 mt-6">
                        <h3 class="text-primary text-sm font-bold uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">history</span>
                            <span id="secHistorial">Historial de estancias</span>
                        </h3>

                        <?php if (!$hasActiveStay && !$hasPendingRequest && empty($stayHistory)) : ?>
                            <p id="noHistoryText" class="text-sm text-slate-600 dark:text-slate-300">No hay estancias registradas aún</p>
                        <?php else : ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php if ($hasPendingRequest) : ?>
                                    <article class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 p-5">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-xs uppercase tracking-wider text-amber-700 dark:text-amber-300" data-i18n="pendingRequest">Solicitud pendiente</p>
                                            <p class="text-xs font-semibold text-amber-900 dark:text-amber-100"><?= htmlspecialchars($pendingRequest['group_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($pendingRequest['institucion'] ?? '-', ENT_QUOTES, 'UTF-8') ?> � <?= htmlspecialchars($pendingRequest['pais'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="mt-3 text-xs text-slate-700 dark:text-slate-200">
                                            <span class="font-semibold" data-i18n="startLabel">Inicio:</span> <?= htmlspecialchars($pendingRequest['fecha_inicio'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                            <span class="ml-3 font-semibold" data-i18n="endLabel">Fin:</span> <?= $formatFechaFin($pendingRequest['fecha_fin'] ?? '') ?>
                                        </p>
                                        <p class="mt-2 text-xs text-slate-700 dark:text-slate-200"><span class="font-semibold" data-i18n="purposeLabel">Motivo:</span> <?= htmlspecialchars($pendingRequest['motivo'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="mt-1 text-xs text-slate-700 dark:text-slate-200"><span class="font-semibold" data-i18n="scheduleLabel">Horario:</span> <?= htmlspecialchars($formatHorario($pendingRequest['horario'] ?? 1), ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="mt-3 inline-flex rounded-full bg-amber-200 dark:bg-amber-900 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-amber-900 dark:text-amber-100" data-i18n="pendingApproval">Pendiente de aprobación</p>
                                    </article>
                                <?php endif; ?>
                                <?php if ($hasActiveStay) : ?>
                                    <article class="rounded-xl border border-primary/40 bg-primary/5 dark:bg-primary/10 p-5">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-xs uppercase tracking-wider text-primary" data-i18n="activeInternship">Estancia activa</p>
                                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200"><?= htmlspecialchars($activeStay['group_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($activeStay['institucion'] ?? '-', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($activeStay['pais'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="mt-3 text-xs text-slate-700 dark:text-slate-200">
                                            <span class="font-semibold" data-i18n="startLabel">Inicio:</span> <?= htmlspecialchars($activeStay['fecha_inicio'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                            <span class="ml-3 font-semibold" data-i18n="endLabel">Fin:</span> <?= $formatFechaFin($activeStay['fecha_fin'] ?? '') ?>
                                        </p>
                                        <p class="mt-2 text-xs text-slate-700 dark:text-slate-200"><span class="font-semibold" data-i18n="purposeLabel">Motivo:</span> <?= htmlspecialchars($activeStay['motivo'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="mt-1 text-xs text-slate-700 dark:text-slate-200"><span class="font-semibold" data-i18n="scheduleLabel">Horario:</span> <?= htmlspecialchars($formatHorario($activeStay['horario'] ?? 1), ENT_QUOTES, 'UTF-8') ?></p>
                                    </article>
                                <?php endif; ?>
                                <?php foreach ($stayHistory as $stay) : ?>
                                    <article class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400" data-i18n="completedInternship">Estancia finalizada</p>
                                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300"><?= htmlspecialchars($stay['group_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($stay['institucion'] ?? '-', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($stay['pais'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="mt-3 text-xs text-slate-600 dark:text-slate-300">
                                            <span class="font-semibold" data-i18n="startLabel">Inicio:</span> <?= htmlspecialchars($stay['fecha_inicio'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                            <span class="ml-3 font-semibold" data-i18n="endLabel">Fin:</span> <?= $formatFechaFin($stay['fecha_fin'] ?? '') ?>
                                        </p>
                                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-300"><span class="font-semibold" data-i18n="purposeLabel">Motivo:</span> <?= htmlspecialchars($stay['motivo'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300"><span class="font-semibold" data-i18n="scheduleLabel">Horario:</span> <?= htmlspecialchars($formatHorario($stay['horario'] ?? 1), ENT_QUOTES, 'UTF-8') ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Cambio de contraseña -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 p-8 mt-6">
                        <h3 class="text-primary text-sm font-bold uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">key</span>
                            <span id="secPwd">Actualizar contraseña</span>
                        </h3>
                        <p id="secPwdDesc" class="text-sm text-slate-600 dark:text-slate-300 mb-4">Cambia tu contraseña de acceso. Debe tener al menos 6 caracteres.</p>
                        <form id="pwdInlineForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="flex flex-col gap-2">
                                <label id="lblPwdCurrent" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Contraseña actual</label>
                                <input id="pwdCurrent" type="password" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-3 focus:ring-primary focus:border-primary" required>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label id="lblPwdNew" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Nueva contraseña</label>
                                <input id="pwdNew" type="password" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-3 focus:ring-primary focus:border-primary" required>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label id="lblPwdConfirm" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Confirmar nueva</label>
                                <input id="pwdConfirm" type="password" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-3 focus:ring-primary focus:border-primary" required>
                            </div>
                            <div class="md:col-span-3 flex items-center gap-3">
                                <button id="pwdSubmit" type="submit" class="h-11 px-5 rounded-lg border border-primary bg-white text-primary font-semibold hover:bg-primary hover:text-white transition-colors flex items-center gap-2">
                                    <span id="pwdSubmitText">Guardar nueva contraseña</span>
                                    <span class="material-symbols-outlined text-sm">check</span>
                                </button>
                                <span id="pwdMsg" class="text-sm"></span>
                            </div>
                        </form>
                    </div>

                </div>
            </main>

            <footer class="text-center py-6 text-slate-500 text-sm">
                � 2026 GestIUBO. Todos los derechos reservados / All rights reserved.
            </footer>
        </div>
    </div>
    <script>
        function fixMojibakeText(input) {
            let s = String(input ?? '');
            const cp1252Reverse = {
                0x20AC: 0x80, 0x201A: 0x82, 0x0192: 0x83, 0x201E: 0x84, 0x2026: 0x85,
                0x2020: 0x86, 0x2021: 0x87, 0x02C6: 0x88, 0x2030: 0x89, 0x0160: 0x8A,
                0x2039: 0x8B, 0x0152: 0x8C, 0x017D: 0x8E, 0x2018: 0x91, 0x2019: 0x92,
                0x201C: 0x93, 0x201D: 0x94, 0x2022: 0x95, 0x2013: 0x96, 0x2014: 0x97,
                0x02DC: 0x98, 0x2122: 0x99, 0x0161: 0x9A, 0x203A: 0x9B, 0x0153: 0x9C,
                0x017E: 0x9E, 0x0178: 0x9F
            };
            const utf8 = new TextDecoder('utf-8', { fatal: false });
            const suspicious = /[Ã][\x80-\u017F]?|Ã¢â‚¬|Ã¢â‚¬â„¢|Ã¢â‚¬Å“|Ã¢â‚¬|ÃƒÆ’/;

            const toBytes = (str) => Uint8Array.from([...str].map((ch) => {
                const code = ch.codePointAt(0);
                if (code <= 0xFF) return code;
                if (cp1252Reverse[code] !== undefined) return cp1252Reverse[code];
                return 0x3F;
            }));

            for (let i = 0; i < 4; i++) {
                if (!suspicious.test(s)) break;
                const repaired = utf8.decode(toBytes(s));
                if (!repaired || repaired === s) break;
                s = repaired;
            }
            return s;
        }

        function normalizeNodeText(node) {
            if (!node) return;
            if (node.nodeType === Node.TEXT_NODE) {
                const fixed = fixMojibakeText(node.nodeValue);
                if (fixed !== node.nodeValue) node.nodeValue = fixed;
                return;
            }
            if (node.nodeType !== Node.ELEMENT_NODE) return;
            for (const attr of ['title', 'aria-label', 'placeholder']) {
                const val = node.getAttribute(attr);
                if (val) {
                    const fixed = fixMojibakeText(val);
                    if (fixed !== val) node.setAttribute(attr, fixed);
                }
            }
            for (const child of node.childNodes) normalizeNodeText(child);
        }

        function switchLanguage(lang) {
            const t = {
                es: {
                    headerTitle: 'Bienvenido/a',
                    greetText: 'Hola',
                    newStayText: 'Nueva estancia',
                    newStayTextDisabled: 'Nueva estancia',
                    newStayTextMobile: 'Nueva estancia',
                    newStayTextDisabledMobile: 'Nueva estancia',
                    secPersonal: 'Información Personal',
                    lblFoto: 'Foto',
                    lblNombre: 'Nombre',
                    lblApellidos: 'Apellidos',
                    lblDni: 'DNI/Pasaporte',
                    lblNacimiento: 'Fecha de Nacimiento',
                    lblTelefono: 'Teléfono',
                    secAcademico: 'Origen Académico',
                    lblInstitucion: 'Institución',
                    lblPais: 'País',
                    secIncorporacion: 'Detalles de la Incorporación',
                    lblMotivo: 'Motivo',
                    lblInicio: 'Fecha de Inicio',
                    lblFin: 'Fecha de Finalización',
                    lblGrupo: 'Grupo',
                    secHistorial: 'Historial de estancias',
                    noHistoryText: 'No hay estancias registradas a�n',
                    pendingRequest: 'Solicitud pendiente',
                    activeInternship: 'Estancia activa',
                    completedInternship: 'Estancia finalizada',
                    startLabel: 'Inicio:',
                    endLabel: 'Fin:',
                    purposeLabel: 'Motivo:',
                    scheduleLabel: 'Horario:',
                    pendingApproval: 'Pendiente de aprobaci�n',
                    secPwd: 'Actualizar contraseña',
                    secPwdDesc: 'Cambia tu contraseña de acceso. Debe tener al menos 6 caracteres.',
                    lblPwdCurrent: 'Contraseña actual',
                    lblPwdNew: 'Nueva contraseña',
                    lblPwdConfirm: 'Confirmar nueva',
                    pwdSubmitText: 'Guardar nueva contraseña',
                },
                en: {
                    headerTitle: 'Welcome',
                    greetText: 'Hello',
                    newStayText: 'New internship',
                    newStayTextDisabled: 'New internship',
                    newStayTextMobile: 'New internship',
                    newStayTextDisabledMobile: 'New internship',
                    secPersonal: 'Personal Information',
                    lblFoto: 'Photo',
                    lblNombre: 'Name',
                    lblApellidos: 'Surnames',
                    lblDni: 'DNI/Passport',
                    lblNacimiento: 'Date of Birth',
                    lblTelefono: 'Phone',
                    secAcademico: 'Academic Background',
                    lblInstitucion: 'Institution',
                    lblPais: 'Country',
                    secIncorporacion: 'Incorporation Details',
                    lblMotivo: 'Purpose',
                    lblInicio: 'Start Date',
                    lblFin: 'End Date',
                    lblGrupo: 'Group',
                    secHistorial: 'Internship History',
                    noHistoryText: 'No internships registered yet.',
                    pendingRequest: 'Pending request',
                    activeInternship: 'Active internship',
                    completedInternship: 'Completed internship',
                    startLabel: 'Start:',
                    endLabel: 'End:',
                    purposeLabel: 'Purpose:',
                    scheduleLabel: 'Schedule:',
                    pendingApproval: 'Pending approval',
                    secPwd: 'Update password',
                    secPwdDesc: 'Change your access password. It must be at least 6 characters.',
                    lblPwdCurrent: 'Current password',
                    lblPwdNew: 'New password',
                    lblPwdConfirm: 'Confirm new',
                    pwdSubmitText: 'Save new password',
                }
            }[lang];

            if (!t) return;
            Object.keys(t).forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.textContent = t[id];
            });
            document.querySelectorAll('[data-i18n]').forEach((el) => {
                const key = el.getAttribute('data-i18n');
                if (key && t[key]) el.textContent = t[key];
            });
            const greetEl = document.getElementById('greetText');
            if (greetEl) {
                const fullName = greetEl.dataset.fullname || '';
                greetEl.textContent = `${t.greetText}, ${fullName}`.trim();
            }

            const langToggle = document.getElementById('lang-toggle');
            const langToggleMobile = document.getElementById('lang-toggle-mobile');
            const nextLang = lang === 'es' ? 'EN' : 'ES';
            if (langToggle) langToggle.textContent = nextLang;
            if (langToggleMobile) langToggleMobile.textContent = nextLang;
            localStorage.setItem('gestiubo_lang', lang);
        }

        function toggleLanguage() {
            const current = localStorage.getItem('gestiubo_lang') || 'es';
            switchLanguage(current === 'es' ? 'en' : 'es');
        }

        document.addEventListener('DOMContentLoaded', () => {
            normalizeNodeText(document.body);
            const observer = new MutationObserver((mutations) => {
                for (const m of mutations) {
                    if (m.type === 'characterData') normalizeNodeText(m.target);
                    if (m.type === 'childList') m.addedNodes.forEach((n) => normalizeNodeText(n));
                }
            });
            observer.observe(document.body, { childList: true, subtree: true, characterData: true });

            switchLanguage(localStorage.getItem('gestiubo_lang') || 'es');
            const btn = document.getElementById('mobileMenuToggleEmployee');
            const menu = document.getElementById('mobileMenuEmployee');
            if (btn && menu) btn.addEventListener('click', () => menu.classList.toggle('hidden'));
        });

        // Toast reutilizable con estética del panel (morado)
        const toastHost = (() => {
            const existing = document.getElementById('toastHost');
            if (existing) return existing;
            const el = document.createElement('div');
            el.id = 'toastHost';
            el.className = 'fixed bottom-4 right-4 flex flex-col gap-3 z-[9999] pointer-events-none';
            document.addEventListener('DOMContentLoaded', () => document.body.appendChild(el));
            return el;
        })();

        function showToast(message, variant = 'info') {
            const palette = {
                success: 'bg-primary text-white',
                error: 'bg-primary text-white',
                info: 'bg-primary text-white'
            };
            const toast = document.createElement('div');
            toast.className = `pointer-events-auto min-w-[240px] max-w-xs rounded-lg shadow-lg px-4 py-3 text-sm font-semibold ${palette[variant] || palette.info}`;
            toast.textContent = message;
            toastHost.appendChild(toast);
            setTimeout(() => toast.remove(), 3200);
        }

        function logout() {
            window.location.href = '/iubolab/logout';
        }

        // Cambio de contraseña inline
        (() => {
            const form = document.getElementById('pwdInlineForm');
            if (!form) return;
            const msg = document.getElementById('pwdMsg');
            const btn = document.getElementById('pwdSubmit');
            const btnText = document.getElementById('pwdSubmitText');
            const showMsg = (text, ok = false) => {
                msg.textContent = text;
                msg.className = ok ? 'text-sm text-emerald-600' : 'text-sm text-rose-600';
            };
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const current = document.getElementById('pwdCurrent').value.trim();
                const neu = document.getElementById('pwdNew').value.trim();
                const confirm = document.getElementById('pwdConfirm').value.trim();
                if (neu !== confirm) {
                    showMsg('Las contraseñas no coinciden.');
                    return;
                }
                if (neu.length < 6) {
                    showMsg('La nueva contraseña debe tener al menos 6 caracteres.');
                    return;
                }
                btn.disabled = true;
                btn.classList.add('opacity-70');
                btnText.textContent = 'Guardando...';
                try {
                    const resp = await fetch('api/change_password.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            current,
                            new: neu,
                            confirm
                        }),
                    });
                    const json = await resp.json();
                    if (!resp.ok || json.error) {
                        showMsg(json.error || 'No se pudo actualizar la contraseña.');
                    } else {
                        showMsg('contraseña actualizada correctamente.', true);
                        form.reset();
                    }
                } catch (err) {
                    console.error(err);
                    showMsg('Error de red al actualizar la contraseña.');
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('opacity-70');
                    const lang = localStorage.getItem('gestiubo_lang') || 'es';
                    btnText.textContent = lang === 'en' ? 'Save new password' : 'Guardar nueva contraseña';
                }
            });
        })();
    </script>
    <script src="/iubolab/scripts/mobile_fab_menu.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initIuboFabMenu({
                actions: [
                    { label: 'Cambiar idioma', textIcon: 'EN', onClick: () => toggleLanguage() },
                    { label: 'Quimicos', icon: 'science', href: '/iubolab/quimicos.php' },
                    { label: 'Cerrar sesion', icon: 'power_settings_new', onClick: () => logout() }
                ]
            });
        });
    </script>
</body>

</html>











