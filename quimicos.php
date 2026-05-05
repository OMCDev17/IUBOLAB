<?php
require __DIR__ . '/api/auth.php';
requireRole(['empleado','supervisor','coordinador','admin']);
header('Content-Type: text/html; charset=UTF-8');

$config = require __DIR__ . '/api/config.php';
$mysqli = new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);
if ($mysqli->connect_errno) { die('Error de conexion con la base de datos.'); }
$mysqli->set_charset($config['charset'] ?? 'utf8mb4');

$user = getSessionUser();
$userId = (int)($user['id'] ?? 0);
$userRole = strtolower((string)($user['rol'] ?? ''));
$userGroupId = (int)($user['group_id'] ?? 0);
$isAdmin = $userRole === 'admin';
$profileUrl = 'empleado';
if ($isAdmin) {
    $profileUrl = 'admin';
} elseif (in_array($userRole, ['supervisor', 'coordinador'], true)) {
    $profileUrl = 'coordinador';
}

$mysqli->query("CREATE TABLE IF NOT EXISTS chemicals (
 id INT AUTO_INCREMENT PRIMARY KEY,
 cas_nr VARCHAR(64) NOT NULL,
 proveedor VARCHAR(150) NOT NULL,
 nombre VARCHAR(180) NOT NULL,
 grupo_owner_id INT NOT NULL,
 localizacion VARCHAR(180) NOT NULL,
 cantidad DECIMAL(12,2) NOT NULL DEFAULT 0,
 acceso ENUM('publico','privado') NOT NULL DEFAULT 'publico',
 grupo_privado_id INT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_nombre (nombre), INDEX idx_grupo_owner_id (grupo_owner_id), INDEX idx_grupo_privado_id (grupo_privado_id)
)");
$mysqli->query("CREATE TABLE IF NOT EXISTS chemicals_log (
 id INT AUTO_INCREMENT PRIMARY KEY,
 chemical_id INT NOT NULL,
 action_type ENUM('update_cantidad','prestamo','create','update_full') NOT NULL,
 cantidad_anterior DECIMAL(12,2) NULL,
 cantidad_nueva DECIMAL(12,2) NULL,
 cantidad_modificada DECIMAL(12,2) NULL,
 usuario_id INT NULL,
 usuario_nombre VARCHAR(180) NULL,
 usuario_rol VARCHAR(50) NULL,
 detalle TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_chemical (chemical_id), INDEX idx_created (created_at)
)");

$groups = [];
$gr = $mysqli->query("SELECT id, name FROM groups ORDER BY name");
while ($gr && ($g = $gr->fetch_assoc())) { $groups[] = $g; }
$hasOwnerId = false;
$hasPrivateId = false;
$colRes = $mysqli->query("SHOW COLUMNS FROM chemicals");
while ($colRes && ($col = $colRes->fetch_assoc())) {
    if (($col['Field'] ?? '') === 'grupo_owner_id') $hasOwnerId = true;
    if (($col['Field'] ?? '') === 'grupo_privado_id') $hasPrivateId = true;
}
$defaultGroupId = isset($groups[0]['id']) ? (int)$groups[0]['id'] : 0;
if ($defaultGroupId <= 0 && $userGroupId > 0) {
    $defaultGroupId = $userGroupId;
}
if ($defaultGroupId <= 0) {
    $defaultGroupId = 1;
}

$check = $mysqli->query('SELECT COUNT(*) c FROM chemicals');
$count = (int)($check ? ($check->fetch_assoc()['c'] ?? 0) : 0);
if ($count === 0) {
    $stmt = $mysqli->prepare("INSERT INTO chemicals (cas_nr, proveedor, nombre, grupo_owner_id, localizacion, cantidad, acceso, grupo_privado_id) VALUES (?,?,?,?,?,?,?,?)");
    $acc = 'publico'; $null = null;
    $samples = [
      ['67-56-1','ChemLab SA','Metanol','Armario A1',120.0],
      ['64-17-5','Reactivos Norte','Etanol absoluto','Armario B2',95.0],
      ['7732-18-5','AquaPure','Agua destilada','Almacen central',500.0]
    ];
    foreach ($samples as $s) {
      [$cas,$prov,$nom,$loc,$cant] = $s;
      $stmt->bind_param('sssidssi',$cas,$prov,$nom,$defaultGroupId,$loc,$cant,$acc,$null);
      $stmt->execute();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_qty') {
        $id = (int)($_POST['chemical_id'] ?? 0);
        $qty = (float)($_POST['cantidad'] ?? 0);
        if ($id > 0) {
            $oldQty = null;
            $s0 = $mysqli->prepare('SELECT cantidad FROM chemicals WHERE id=? LIMIT 1');
            if ($s0) {
                $s0->bind_param('i', $id);
                $s0->execute();
                $res0 = $s0->get_result();
                if ($res0 && ($r0 = $res0->fetch_assoc())) {
                    $oldQty = (float)$r0['cantidad'];
                }
                $s0->close();
            }
            $stmt = $mysqli->prepare('UPDATE chemicals SET cantidad=? WHERE id=?');
            if ($stmt) {
                $stmt->bind_param('di', $qty, $id);
                $stmt->execute();
                $stmt->close();
            }
            if ($oldQty !== null) {
                $delta = $qty - $oldQty;
                $userName = trim((string)(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? '')));
                $detail = 'Actualización manual de cantidad';
                $type = 'update_cantidad';
                $sl = $mysqli->prepare('INSERT INTO chemicals_log (chemical_id, action_type, cantidad_anterior, cantidad_nueva, cantidad_modificada, usuario_id, usuario_nombre, usuario_rol, detalle) VALUES (?,?,?,?,?,?,?,?,?)');
                if ($sl) {
                    $sl->bind_param('isdddisss', $id, $type, $oldQty, $qty, $delta, $userId, $userName, $userRole, $detail);
                    $sl->execute();
                    $sl->close();
                }
            }
        }
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$where = [];
$shouldQuery = $isAdmin || strlen($q) >= 3;
if (!$isAdmin && $hasPrivateId) { $where[] = "(c.acceso='publico' OR c.grupo_privado_id=" . (int)$userGroupId . ")"; }
if (!$isAdmin && !$hasPrivateId) { $where[] = "(c.acceso='publico' OR c.grupo_privado='" . $mysqli->real_escape_string((string)($user['group_name'] ?? $user['grupo'] ?? '')) . "')"; }
if (!$isAdmin && strlen($q) >= 3) { $where[] = "c.nombre LIKE '%" . $mysqli->real_escape_string($q) . "%'"; }
if ($hasOwnerId) {
    $sql = "SELECT c.*, go.name AS grupo_owner, " .
        ($hasPrivateId ? "gp.name AS grupo_privado " : "NULL AS grupo_privado ") .
        "FROM chemicals c
        LEFT JOIN groups go ON go.id = c.grupo_owner_id " .
        ($hasPrivateId ? "LEFT JOIN groups gp ON gp.id = c.grupo_privado_id " : "");
} else {
    $sql = "SELECT c.*, c.grupo_owner AS grupo_owner, c.grupo_privado AS grupo_privado FROM chemicals c";
}
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY c.nombre ASC LIMIT 300';
$rows = [];
if ($shouldQuery) {
    $r = $mysqli->query($sql);
    $sqlError = $r ? '' : $mysqli->error;
    while ($r && ($row = $r->fetch_assoc())) { $rows[] = $row; }
} else {
    $sqlError = '';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Químicos</title><script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script></head>
<body class="bg-background-light min-h-screen text-slate-900">
<div class="relative flex h-auto min-h-screen w-full flex-col overflow-x-hidden">
<div class="layout-container flex h-full grow flex-col">
<header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-solid border-slate-200 bg-white px-4 md:px-10 py-4 fixed top-0 left-0 right-0 z-50">
<div class="flex items-center gap-3 flex-wrap">
<img alt="Logo" class="h-10 w-auto object-contain" src="/iubolab/imagenes/instituto-biorganica-agonzalez-original.png" />
<h2 class="text-slate-900 text-lg font-bold leading-tight tracking-[-0.015em] border-l border-slate-300 pl-4">Control de Químicos</h2>
</div>
<div class="flex items-center gap-3 w-full md:w-auto justify-end">
<a href="<?= htmlspecialchars($profileUrl) ?>" class="flex shrink-0 items-center justify-center overflow-hidden rounded-xl h-11 px-4 border border-slate-400 text-slate-700 text-sm font-bold hover:bg-slate-100 transition-colors">Volver al perfil</a>
<a href="/iubolab/logout" class="flex shrink-0 items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary bg-white text-primary text-sm font-bold hover:bg-primary hover:text-white transition-colors" title="Cerrar sesión">
<span class="material-symbols-outlined text-base">power_settings_new</span>
</a>
</div>
</header>
<main class="flex-1 pt-28 pb-10">
<div class="max-w-7xl mx-auto p-4 md:p-8">
<?php if (!empty($sqlError)): ?><div class="mt-3 p-3 rounded bg-red-100 text-red-800">Error SQL: <?= htmlspecialchars($sqlError) ?></div><?php endif; ?>
<form method="get" class="mt-4 flex gap-2"><input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre (mínimo 3 caracteres)" class="flex-1 border rounded px-3 py-2"><button class="px-4 py-2 bg-slate-800 text-white rounded">Buscar</button></form>
<div class="mt-5 overflow-auto bg-white rounded border"><table class="min-w-full text-sm"><thead class="bg-slate-200"><tr><th class="p-2">CAS-NR</th><th>Proveedor</th><th>Nombre</th><th>Grupo owner</th><th>Localización</th><th>Cantidad</th><th>Acceso</th><th>Grupo privado</th><th>Acción</th></tr></thead><tbody>
<?php foreach($rows as $c): ?><tr class="border-t"><td class="p-2"><?= htmlspecialchars($c['cas_nr']) ?></td><td><?= htmlspecialchars($c['proveedor']) ?></td><td><?= htmlspecialchars($c['nombre']) ?></td><td><?= htmlspecialchars((string)$c['grupo_owner']) ?></td><td><?= htmlspecialchars($c['localizacion']) ?></td><td>
<form method="post" class="flex items-center gap-2">
<input type="hidden" name="action" value="save_qty">
<input type="hidden" name="chemical_id" value="<?= (int)$c['id'] ?>">
<input type="number" step="0.01" min="0" name="cantidad" value="<?= htmlspecialchars((string)$c['cantidad']) ?>" class="w-24 border rounded px-2 py-1">
</td><td><?= htmlspecialchars($c['acceso']) ?></td><td><?= htmlspecialchars((string)$c['grupo_privado']) ?></td><td><button class="px-3 py-1 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">Guardar cambios</button></form></td></tr><?php endforeach; ?>
<?php if (!$isAdmin && strlen($q) > 0 && strlen($q) < 3): ?><tr><td colspan="8" class="p-4 text-center text-amber-700">Escribe al menos 3 caracteres para buscar.</td></tr><?php endif; ?>
<?php if (!$isAdmin && strlen($q) === 0): ?><tr><td colspan="8" class="p-4 text-center text-slate-500">Usa el buscador (mínimo 3 caracteres) y pulsa Enter.</td></tr><?php endif; ?>
<?php if (($isAdmin || strlen($q) >= 3) && count($rows) === 0): ?><tr><td colspan="8" class="p-4 text-center text-slate-500">No hay resultados.</td></tr><?php endif; ?>
</tbody></table></div>
</div>
</main>
</div>
</div>
</body></html>
