<?php
require __DIR__ . '/api/auth.php';
requireRole(['supervisor', 'coordinador', 'admin']);

$user = getSessionUser();
$fullName = $user ? htmlspecialchars(trim(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? ''))) : '';
// ObtÃƒÂ©n el nombre de grupo desde group_name (nuevo) o grupo (legacy)
$groupLabel = htmlspecialchars(trim($user['group_name'] ?? $user['grupo'] ?? ''));
?>

<!DOCTYPE html>

<html class="light" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>GestIUBO - Coordinador</title>
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
                <div class="flex items-center gap-3 flex-wrap">
                    <img alt="Logo de la InstituciÃƒÂ³n" class="h-10 w-auto object-contain" src="/iubolab/imagenes/instituto-biorganica-agonzalez-original.png" />
                    <h2 class="text-slate-900 dark:text-slate-100 text-lg font-bold leading-tight tracking-[-0.015em] border-l border-slate-300 dark:border-slate-700 pl-4">Coordinador</h2>
                    <?php if ($fullName): ?>
                        <span class="text-sm text-slate-500 dark:text-slate-400 pl-4">Hola, <?php echo $fullName; ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                    <button id="mobileMenuToggleSupervisor" type="button" class="md:hidden flex shrink-0 items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary bg-white dark:bg-slate-900 text-primary hover:bg-primary hover:text-white transition-colors" aria-label="Abrir menÃƒÂº">
                        <span class="material-symbols-outlined text-base">menu</span>
                    </button>
                    <div class="hidden md:flex items-center gap-3">
                        <button id="saveButton" aria-label="Guardar cambios" title="Guardar cambios" class="flex shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary text-primary text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-xl">save</span>                          <span class="truncate sr-only">Guardar cambios</span>
                        </button>
                        <a href="/iubolab/quimicos" aria-label="Químicos" title="Químicos" class="flex shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary text-primary text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary hover:text-white transition-colors"><span class="material-symbols-outlined text-xl">science</span></a>
                        <a href="#" onclick="logout(); return false;" aria-label="Cerrar sesiÃƒÂ³n" title="Cerrar sesiÃƒÂ³n" class="flex shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl h-11 w-11 border border-primary bg-white dark:bg-slate-900 text-primary text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-base">power_settings_new</span>
                        </a>
                    </div>
                </div>
                <div id="mobileMenuSupervisor" class="hidden md:hidden w-full border-t border-slate-200 dark:border-slate-800 pt-3 flex flex-col gap-2">
                    <button type="button" onclick="document.getElementById('saveButton')?.click();" class="w-full rounded-xl h-11 border border-primary text-primary text-sm font-bold hover:bg-primary hover:text-white transition-colors">Guardar cambios</button>
                    <a href="/iubolab/quimicos" aria-label="Químicos" title="Químicos" class="w-full flex items-center justify-center rounded-xl h-11 border border-primary text-primary text-sm font-bold hover:bg-primary hover:text-white transition-colors"><span class="material-symbols-outlined text-xl">science</span></a>
                    <a href="#" onclick="logout(); return false;" class="w-full flex items-center justify-center rounded-xl h-11 border border-primary text-primary text-sm font-bold hover:bg-primary hover:text-white transition-colors">Cerrar sesiÃƒÂ³n</a>
                </div>
            </header>

            <main class="flex-1 flex justify-center pt-6 md:pt-28 pb-10 px-4 md:px-0">
                <div class="w-full max-w-[980px] flex flex-col gap-8">
                    
                    <!-- Header section -->
                    <div class="flex flex-col gap-2">
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100">Panel del Coordinador</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Administra tu grupo y aprueba nuevas solicitudes de incorporaciÃƒÂ³n</p>
                    </div>

                    <!-- Supervisor profile section -->
                    <div class="flex flex-col gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Tu perfil</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">InformaciÃƒÂ³n de tu estancia en el grupo</p>
                        </div>
                        <div id="supervisorCard" class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-slate-100 dark:border-slate-800 p-6"></div>
                    </div>

                    <!-- Change password section -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 p-8">
                        <h3 class="text-primary text-sm font-bold uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">key</span>
                            Actualizar contraseÃ±a
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">Cambia tu contraseÃ±a de acceso. Debe tener al menos 6 caracteres.</p>
                        <form id="pwdInlineForm" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">ContraseÃ±a actual</label>
                                <input id="pwdCurrent" type="password" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-3 focus:ring-primary focus:border-primary" required>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Nueva contraseÃ±a</label>
                                <input id="pwdNew" type="password" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-3 focus:ring-primary focus:border-primary" required>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Confirmar nueva</label>
                                <input id="pwdConfirm" type="password" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-3 focus:ring-primary focus:border-primary" required>
                            </div>
                            <div class="md:col-span-3 flex items-center gap-3">
                                <button id="pwdSubmit" type="submit" class="h-11 px-5 rounded-lg border border-primary bg-white text-primary font-semibold hover:bg-primary hover:text-white transition-colors flex items-center gap-2">
                                    <span id="pwdSubmitText">Guardar nueva contraseÃƒÂ±a</span>
                                    <span class="material-symbols-outlined text-sm">check</span>
                                </button>
                                <span id="pwdMsg" class="text-sm"></span>
                            </div>
                        </form>
                    </div>

                    <!-- Group members section -->
                    <div class="flex flex-col gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Miembros del grupo</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Usuarios activos en <?php echo $groupLabel; ?></p>
                        </div>
                        <div id="employeesContainer" class="grid gap-6"></div>
                    </div>

                    <!-- Pending requests section -->
                    <div id="requestsSection" style="display: none;" class="flex flex-col gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Solicitudes pendientes</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Nuevas solicitudes de incorporaciÃƒÂ³n al grupo</p>
                        </div>
                        <div id="requestsContainer" class="grid gap-4"></div>
                    </div>

                </div>
            </main>

            <footer class="text-center py-6 text-slate-500 text-sm">
                © 2026 GestIUBO. Todos los derechos reservados.
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
            const suspicious = /[Ãƒ?][\x80-\u017F]?|Ãƒ?Ã‚Â¢ÃƒÂ¢Ã¢??Ã‚Â¬|Ãƒ?Ã‚Â¢ÃƒÂ¢Ã¢??Ã‚Â¬ÃƒÂ¢Ã¢??Ã‚Â¢|Ãƒ?Ã‚Â¢ÃƒÂ¢Ã¢??Ã‚Â¬Ãƒ?Ã¢??|Ãƒ?Ã‚Â¢ÃƒÂ¢Ã¢??Ã‚Â¬Ã‚Â|Ãƒ?Ã†?Ãƒ?Ã¢??/;

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

        document.addEventListener('DOMContentLoaded', () => {
            normalizeNodeText(document.body);
            const observer = new MutationObserver((mutations) => {
                for (const m of mutations) {
                    if (m.type === 'characterData') normalizeNodeText(m.target);
                    if (m.type === 'childList') m.addedNodes.forEach((n) => normalizeNodeText(n));
                }
            });
            observer.observe(document.body, { childList: true, subtree: true, characterData: true });
        });

        // Toast reutilizable (morado) alineado con el panel
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

        let employees = [];
        let supervisorData = <?php echo json_encode($user ?? []); ?>;
        const supervisorId = supervisorData?.id ? Number(supervisorData.id) : null;
        const groupToShow = '<?php echo $groupLabel; ?>';
        const pendingChanges = new Map();
        const saveButtonBaseText = 'Guardar cambios';
        const horarioOptions = [{
                value: 1,
                label: 'Completo'
            },
            {
                value: 0,
                label: 'Solo lectivo'
            },
        ];
        const maskDni = (value) => {
            const str = String(value ?? '').trim();
            if (!str) return '-';
            if (str.length <= 4) return `**${str.slice(0, 1)}***`;
            const middle = str.slice(2, -2) || '***';
            return `**${middle}**`;
        };

        function formatDate(dateStr) {
            const d = new Date(dateStr);
            if (Number.isNaN(d.getTime())) return '';
            return d.toLocaleDateString(undefined, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }

        function normalizeDateKey(dateStr) {
            const raw = String(dateStr ?? '').trim();
            if (!raw) return '';
            return raw.split('T')[0];
        }

        function addDaysToDateKey(dateKey, days) {
            if (!dateKey) return '';
            const d = new Date(`${dateKey}T00:00:00`);
            if (Number.isNaN(d.getTime())) return '';
            d.setDate(d.getDate() + Number(days || 0));
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        function renderSupervisorCard() {
            const container = document.getElementById('supervisorCard');
            if (!supervisorData || !supervisorData.id) {
                container.innerHTML = '<p class="text-slate-500 dark:text-slate-400">No hay datos disponibles</p>';
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'flex flex-col gap-8';

            // Section: InformaciÃƒÂ³n Personal
            const personalSection = document.createElement('section');
            const personalTitle = document.createElement('h3');
            personalTitle.className = 'text-primary text-sm font-bold uppercase tracking-wider mb-6 flex items-center gap-2';
            personalTitle.innerHTML = '<span class="material-symbols-outlined text-sm">person</span> InformaciÃ³n Personal';
            
            const personalGrid = document.createElement('div');
            personalGrid.className = 'grid grid-cols-1 md:grid-cols-3 gap-6 items-start';
            
            const fotoDiv = document.createElement('div');
            fotoDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5 flex flex-col items-center gap-3 md:row-span-2';
            const fotoImg = document.createElement('img');
            fotoImg.className = 'h-28 w-28 rounded-full object-cover border border-slate-200 dark:border-slate-700';
            fotoImg.src = supervisorData.foto_url || 'https://via.placeholder.com/112';
            fotoImg.alt = 'Foto del supervisor';
            const fotoLabel = document.createElement('p');
            fotoLabel.className = 'text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase';
            fotoLabel.textContent = 'Foto';
            fotoDiv.appendChild(fotoImg);
            fotoDiv.appendChild(fotoLabel);

            const nombreDiv = document.createElement('div');
            nombreDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            nombreDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Nombre</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.nombre || '-'}</p>
            `;

            const apellidosDiv = document.createElement('div');
            apellidosDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            apellidosDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Apellidos</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.apellidos || '-'}</p>
            `;

            const dniDiv = document.createElement('div');
            dniDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5 md:col-span-2';
            dniDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">DNI/Pasaporte</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.dni_pasaporte || '-'}</p>
            `;

            const fechaNacDiv = document.createElement('div');
            fechaNacDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            fechaNacDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Fecha de Nacimiento</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.fecha_nacimiento || '-'}</p>
            `;

            const emailDiv = document.createElement('div');
            emailDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            emailDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Email</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.email || '-'}</p>
            `;

            const phoneDiv = document.createElement('div');
            phoneDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            phoneDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">TelÃ©fono</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.phone_prefix || '+34'} ${supervisorData.phone_number || '000000000'}</p>
            `;

            personalGrid.appendChild(fotoDiv);
            personalGrid.appendChild(nombreDiv);
            personalGrid.appendChild(apellidosDiv);
            personalGrid.appendChild(dniDiv);
            personalGrid.appendChild(fechaNacDiv);
            personalGrid.appendChild(emailDiv);
            personalGrid.appendChild(phoneDiv);

            personalSection.appendChild(personalTitle);
            personalSection.appendChild(personalGrid);

            // Separator
            const hr1 = document.createElement('hr');
            hr1.className = 'border-slate-100 dark:border-slate-800';

            // Section: Origen AcadÃƒÂ©mico
            const academicSection = document.createElement('section');
            const academicTitle = document.createElement('h3');
            academicTitle.className = 'text-primary text-sm font-bold uppercase tracking-wider mb-6 flex items-center gap-2';
            academicTitle.innerHTML = '<span class="material-symbols-outlined text-sm">school</span> Origen AcadÃ©mico';

            const academicGrid = document.createElement('div');
            academicGrid.className = 'grid grid-cols-1 md:grid-cols-2 gap-6';
            
            const institucionDiv = document.createElement('div');
            institucionDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            institucionDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">InstituciÃ³n</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.institucion || '-'}</p>
            `;

            const paisDiv = document.createElement('div');
            paisDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            paisDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">PaÃ­s</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.pais || '-'}</p>
            `;

            academicGrid.appendChild(institucionDiv);
            academicGrid.appendChild(paisDiv);

            academicSection.appendChild(academicTitle);
            academicSection.appendChild(academicGrid);

            // Separator
            const hr2 = document.createElement('hr');
            hr2.className = 'border-slate-100 dark:border-slate-800';

            // Section: Detalles de IncorporaciÃƒÂ³n
            const incorporationSection = document.createElement('section');
            const incorporationTitle = document.createElement('h3');
            incorporationTitle.className = 'text-primary text-sm font-bold uppercase tracking-wider mb-6 flex items-center gap-2';
            incorporationTitle.innerHTML = '<span class="material-symbols-outlined text-sm">science</span> Detalles de la IncorporaciÃ³n';

            const incorporationGrid = document.createElement('div');
            incorporationGrid.className = 'grid grid-cols-1 md:grid-cols-3 gap-6';

            const motivoDiv = document.createElement('div');
            motivoDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            motivoDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Motivo</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.motivo || '-'}</p>
            `;

            const inicioDiv = document.createElement('div');
            inicioDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            inicioDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Fecha de Inicio</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${formatDate(supervisorData.fecha_inicio) || '-'}</p>
            `;

            const finDiv = document.createElement('div');
            finDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            finDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Fecha de FinalizaciÃ³n</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${formatDate(supervisorData.fecha_fin) || '-'}</p>
            `;

            incorporationGrid.appendChild(motivoDiv);
            incorporationGrid.appendChild(inicioDiv);
            incorporationGrid.appendChild(finDiv);

            const incorporationGrid2 = document.createElement('div');
            incorporationGrid2.className = 'mt-6 grid grid-cols-1 md:grid-cols-2 gap-6';

            const grupoDiv = document.createElement('div');
            grupoDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            grupoDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Grupo</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.group_name || '-'}</p>
            `;

            const horarioDiv = document.createElement('div');
            horarioDiv.className = 'rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-5';
            horarioDiv.innerHTML = `
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Horario</p>
                <p class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">${supervisorData.horario == 1 ? 'Completo' : 'Solo lectivo'}</p>
            `;

            incorporationGrid2.appendChild(grupoDiv);
            incorporationGrid2.appendChild(horarioDiv);

            incorporationSection.appendChild(incorporationTitle);
            incorporationSection.appendChild(incorporationGrid);
            incorporationSection.appendChild(incorporationGrid2);

            wrapper.appendChild(personalSection);
            wrapper.appendChild(hr1);
            wrapper.appendChild(academicSection);
            wrapper.appendChild(hr2);
            wrapper.appendChild(incorporationSection);

            container.innerHTML = '';
            container.appendChild(wrapper);
        }

        async function fetchEmployees() {
            try {
                const resp = await fetch('api/employees.php');
                const json = await resp.json();
                if (!Array.isArray(json.employees)) throw new Error('Respuesta invÃƒÂ¡lida');
                employees = json.employees.map((emp) => ({
                    ...emp,
                    id: Number(emp.id),
                    dni: emp.dni_pasaporte,
                    foto: emp.foto_url,
                    horario: typeof emp.horario !== 'undefined' ? Number(emp.horario) : 1,
                    // Normalizamos nombre de grupo para usar en el filtro/render
                    grupo: emp.group_name || emp.grupo || '',
                }));
            } catch (error) {
                console.error('No se pudieron cargar los usuarios:', error);
                employees = [];
            }
        }

        function render() {
            const container = document.getElementById('employeesContainer');
            container.innerHTML = '';

            const groupEmployees = groupToShow ?
                employees.filter((e) => (e.grupo || '').toUpperCase() === groupToShow.toUpperCase() && e.id !== supervisorId) :
                employees.filter((e) => e.id !== supervisorId);

            if (groupEmployees.length === 0) {
                container.innerHTML = `<div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center"><p class="text-lg font-semibold text-slate-900 dark:text-slate-100">No hay usuarios en el grupo ${groupToShow}.</p></div>`;
                return;
            }

            groupEmployees.forEach((emp) => {
                const startKey = normalizeDateKey(emp.fecha_inicio);
                const endKey = normalizeDateKey(emp.fecha_fin);
                const minEndKey = addDaysToDateKey(startKey, 1);
                const card = document.createElement('div');
                card.className = 'bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-slate-100 dark:border-slate-800 p-6';

                const row = document.createElement('div');
                row.className = 'flex flex-col md:flex-row md:items-center md:justify-between gap-4';

                const profile = document.createElement('div');
                profile.className = 'flex items-center gap-4';
                profile.innerHTML = `
                <img class="h-16 w-16 rounded-full object-cover border border-slate-200 dark:border-slate-700" src="${emp.foto}" alt="${emp.nombre} ${emp.apellidos}" />
                <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">${emp.nombre} ${emp.apellidos}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">${emp.email}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">DNI: ${maskDni(emp.dni)}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">TelÃƒÂ©fono: ${emp.phone_prefix || '+34'} ${emp.phone_number || '000000000'}</p>
                </div>
            `;

                const dates = document.createElement('div');
                dates.className = 'grid grid-cols-1 sm:grid-cols-3 gap-3 w-full md:w-auto';
                dates.innerHTML = `
                <div class="rounded-lg bg-slate-50 dark:bg-slate-950 p-3 border border-slate-200 dark:border-slate-800">
                    <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400">Inicio</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">${formatDate(emp.fecha_inicio)}</p>
                </div>
                <div class="rounded-lg bg-slate-50 dark:bg-slate-950 p-3 border border-slate-200 dark:border-slate-800">
                    <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400">Fin</p>
                    <input type="date" value="${endKey}" min="${minEndKey}" class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary" data-employee-id="${emp.id}" data-field="fecha_fin" />
                </div>
                <div class="rounded-lg bg-slate-50 dark:bg-slate-950 p-3 border border-slate-200 dark:border-slate-800">
                    <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400">Horario</p>
                    <select class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 focus:outline-none focus:ring-primary focus:border-primary" data-employee-id="${emp.id}" data-field="horario">
                        ${horarioOptions.map(opt => `<option value=\"${opt.value}\" ${Number(opt.value) === Number(emp.horario) ? 'selected' : ''}>${opt.label}</option>`).join('')}
                    </select>
                </div>
            `;

                row.appendChild(profile);
                row.appendChild(dates);
                card.appendChild(row);
                container.appendChild(card);
            });

            // Delegated listener to capturar cambios aunque se re-renderice
            container.onchange = (event) => {
                const target = event.target;
                if (target?.dataset?.employeeId) {
                    const id = Number(target.dataset.employeeId);
                    const emp = employees.find((e) => e.id === id);
                    if (!emp) return;
                    const field = target.dataset.field;
                    if (field === 'horario') {
                        emp.horario = Number(target.value);
                    } else if (field === 'fecha_fin') {
                        const startKey = normalizeDateKey(emp.fecha_inicio);
                        const nextEndKey = normalizeDateKey(target.value);
                        if (startKey && nextEndKey && nextEndKey <= startKey) {
                            showToast('La fecha de fin debe ser posterior a la fecha de inicio.', 'error');
                            target.value = normalizeDateKey(emp.fecha_fin);
                            return;
                        }
                        emp.fecha_fin = target.value;
                    }
                    const current = pendingChanges.get(id) || {
                        id
                    };
                    if (field === 'horario') current.horario = emp.horario;
                    if (field === 'fecha_fin') current.fecha_fin = emp.fecha_fin;
                    pendingChanges.set(id, current);
                    updateSaveButtonLabel();
                }
            };
        }

        function updateSaveButtonLabel() {
            const btn = document.getElementById('saveButton');
            if (!btn) return;
            const count = pendingChanges.size;
            btn.querySelector('.truncate').textContent = count > 0 ?
                `${saveButtonBaseText} (${count})` :
                saveButtonBaseText;
        }

        async function saveChanges() {
            if (pendingChanges.size === 0) {
                showToast('No hay cambios para guardar.', 'info');
                return;
            }

            try {
                const payload = {
                    updates: Array.from(pendingChanges.values())
                };

                for (const upd of payload.updates) {
                    if (!upd.fecha_fin) continue;
                    const emp = employees.find((e) => e.id === Number(upd.id));
                    if (!emp) continue;
                    const startKey = normalizeDateKey(emp.fecha_inicio);
                    const endKey = normalizeDateKey(upd.fecha_fin);
                    if (startKey && endKey && endKey <= startKey) {
                        showToast('Hay una fecha fin igual o anterior al inicio. CorrÃƒÂ­gela antes de guardar.', 'error');
                        return;
                    }
                }

                const resp = await fetch('api/update_end_date.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });
                const result = await resp.json();
                const hasErrors = Array.isArray(result?.errors) && result.errors.length > 0;

                if (resp.ok && !hasErrors) {
                    const updatedCount = Number(result?.updated ?? payload.updates.length);
                    showToast(`Cambios guardados (${updatedCount} actualizaciones).`, 'success');
                    pendingChanges.clear();
                    updateSaveButtonLabel();
                    await loadAndRender();
                } else {
                    console.error(result);
                    const firstErr = hasErrors ? result.errors[0]?.error : null;
                    showToast(firstErr || result?.error || 'Error al guardar. Revisa la consola.', 'error');
                    pendingChanges.clear();
                    updateSaveButtonLabel();
                    await loadAndRender();
                }
            } catch (error) {
                console.error(error);
                showToast('Error al guardar. Revisa la consola.', 'error');
                pendingChanges.clear();
                updateSaveButtonLabel();
                await loadAndRender();
            }
        }

        async function loadAndRender() {
            await fetchEmployees();
            await fetchRequests();
            renderSupervisorCard();
            render();
            renderRequests();
        }

        let pendingRequests = [];

        async function fetchRequests() {
            try {
                const resp = await fetch('api/group_requests.php');
                const json = await resp.json();
                if (!Array.isArray(json.requests)) throw new Error('Respuesta invÃƒÂ¡lida');
                pendingRequests = json.requests.map((req) => ({
                    ...req,
                    id: Number(req.id ?? req.request_id ?? req.requestId ?? 0),
                    employee_id: Number(req.employee_id),
                    group_id: Number(req.group_id),
                    horario: Number(req.horario),
                }));
            } catch (error) {
                console.error('No se pudieron cargar las solicitudes:', error);
                pendingRequests = [];
            }
        }

        function renderRequests() {
            const section = document.getElementById('requestsSection');
            const container = document.getElementById('requestsContainer');
            
            if (pendingRequests.length === 0) {
                section.style.display = 'none';
                container.innerHTML = '';
                return;
            }

            section.style.display = 'block';
            container.innerHTML = '';

            pendingRequests.forEach((req) => {
                const card = document.createElement('div');
                card.className = 'bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-slate-100 dark:border-slate-800 p-6';

                const header = document.createElement('div');
                header.className = 'flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4';

                const info = document.createElement('div');
                info.className = 'flex-1';
                info.innerHTML = `
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">${req.nombre} ${req.apellidos}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">${req.requested_by_email}</p>
                `;

                const badge = document.createElement('div');
                badge.className = 'inline-flex items-center gap-1 rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-3 py-1 text-yellow-700 dark:text-yellow-400 text-xs font-semibold';
                badge.textContent = 'Pendiente';

                header.appendChild(info);
                header.appendChild(badge);
                card.appendChild(header);

                const details = document.createElement('div');
                details.className = 'grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4';
                details.innerHTML = `
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-950 p-3 border border-slate-200 dark:border-slate-800">
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400">Grupo</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">${req.group_name}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-950 p-3 border border-slate-200 dark:border-slate-800">
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400">Motivo</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">${req.motivo || '-'}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-950 p-3 border border-slate-200 dark:border-slate-800">
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400">Periodo</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">${formatDate(req.fecha_inicio)} - ${formatDate(req.fecha_fin)}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-950 p-3 border border-slate-200 dark:border-slate-800">
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400">InstituciÃƒÂ³n</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">${req.institucion || '-'}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-950 p-3 border border-slate-200 dark:border-slate-800">
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400">PaÃƒÂ­s</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">${req.pais || '-'}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-950 p-3 border border-slate-200 dark:border-slate-800">
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400">Horario</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">${Number(req.horario) === 1 ? 'Completo' : 'Solo lectivo'}</p>
                    </div>
                `;
                card.appendChild(details);

                const actions = document.createElement('div');
                actions.className = 'flex gap-3';
                
                const approveBtn = document.createElement('button');
                approveBtn.className = 'flex-1 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 transition-colors';
                approveBtn.textContent = 'Aprobar';
                approveBtn.onclick = () => handleRequest({
                    request_id: Number(req.id ?? req.request_id ?? req.requestId ?? 0),
                    employee_id: Number(req.employee_id || 0),
                    group_id: Number(req.group_id || 0),
                }, 'approve');

                const rejectBtn = document.createElement('button');
                rejectBtn.className = 'flex-1 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 transition-colors';
                rejectBtn.textContent = 'Rechazar';
                rejectBtn.onclick = () => handleRequest({
                    request_id: Number(req.id ?? req.request_id ?? req.requestId ?? 0),
                    employee_id: Number(req.employee_id || 0),
                    group_id: Number(req.group_id || 0),
                }, 'reject');

                actions.appendChild(approveBtn);
                actions.appendChild(rejectBtn);
                card.appendChild(actions);

                container.appendChild(card);
            });
        }

        async function handleRequest(requestData, action) {
            const numericRequestId = Number.parseInt(requestData?.request_id ?? requestData, 10);
            const numericEmployeeId = Number.parseInt(requestData?.employee_id, 10);
            const numericGroupId = Number.parseInt(requestData?.group_id, 10);
            if ((!Number.isFinite(numericRequestId) || numericRequestId <= 0) &&
                (!Number.isFinite(numericEmployeeId) || numericEmployeeId <= 0 || !Number.isFinite(numericGroupId) || numericGroupId <= 0)) {
                showToast('No se pudo identificar la solicitud', 'error');
                return;
            }
            try {
                const accion = action === 'approve' ? 'aprobar' : 'rechazar';
                const form = new URLSearchParams({
                    request_id: String(numericRequestId),
                    requestId: String(numericRequestId),
                    id: String(numericRequestId),
                    employee_id: String(numericEmployeeId),
                    group_id: String(numericGroupId),
                    action,
                    accion
                });
                const qs = new URLSearchParams({
                    request_id: String(numericRequestId),
                    employee_id: String(numericEmployeeId),
                    group_id: String(numericGroupId),
                    action,
                    accion
                });
                const resp = await fetch(`api/group_requests.php?${qs.toString()}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    credentials: 'same-origin',
                    body: form.toString(),
                });
                const result = await resp.json();

                if (resp.ok) {
                    const msg = action === 'approve' ? 'Solicitud aprobada correctamente' : 'Solicitud rechazada';
                    showToast(msg, 'success');
                    await loadAndRender();
                } else {
                    showToast(result?.error || `Error al ${action === 'approve' ? 'aprobar' : 'rechazar'} la solicitud`, 'error');
                }
            } catch (error) {
                console.error(error);
                showToast('Error procesando solicitud', 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('saveButton').addEventListener('click', saveChanges);
            updateSaveButtonLabel();
            loadAndRender();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mobileMenuToggleSupervisor');
            const menu = document.getElementById('mobileMenuSupervisor');
            if (!btn || !menu) return;
            btn.addEventListener('click', () => menu.classList.toggle('hidden'));
        });

        function logout() {
            window.location.href = '/iubolab/logout';
        }

        // Cambio de contraseÃƒÂ±a inline
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
                    showMsg('Las contraseÃƒÂ±as no coinciden.');
                    return;
                }
                if (neu.length < 6) {
                    showMsg('La nueva contraseÃƒÂ±a debe tener al menos 6 caracteres.');
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
                        showMsg(json.error || 'No se pudo actualizar la contraseÃƒÂ±a.');
                    } else {
                        showMsg('contraseÃƒÂ±a actualizada correctamente.', true);
                        form.reset();
                    }
                } catch (err) {
                    console.error(err);
                    showMsg('Error de red al actualizar la contraseÃƒÂ±a.');
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('opacity-70');
                    btnText.textContent = 'Guardar nueva contraseÃƒÂ±a';
                }
            });
        })();
    </script>
    <script src="/iubolab/scripts/mobile_fab_menu.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initIuboFabMenu({
                actions: [
                    { label: 'Quimicos', icon: 'science', href: '/iubolab/quimicos' },
                    { label: 'Cerrar sesion', icon: 'power_settings_new', onClick: () => logout() }
                ]
            });
        });
    </script>
</body>

</html>











