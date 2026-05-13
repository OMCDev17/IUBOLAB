<?php
require __DIR__ . '/api/auth.php';
requireRole(['empleado','supervisor','coordinador','admin']);
require_once __DIR__ . '/api/stay_lifecycle.php';
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
$isUserRole = $userRole === 'empleado';
$canManageGroupChemicals = $isAdmin || in_array($userRole, ['empleado','supervisor','coordinador'], true);
$profileUrl = $isAdmin ? 'admin.php' : (in_array($userRole, ['supervisor','coordinador'], true) ? 'supervisor.php' : 'empleado.php');

// Acceso permitido solo con estancia activa (tambien para admin)
$hasActiveStay = false;
try {
  expireStaysAndPendingRequests($mysqli);
  $st = $mysqli->prepare("SELECT fecha_fin FROM stays WHERE employee_id=? AND status='active' ORDER BY updated_at DESC LIMIT 1");
  if ($st) {
    $st->bind_param('i', $userId);
    $st->execute();
    $rs = $st->get_result();
    if ($rs && ($row = $rs->fetch_assoc())) {
      $hasActiveStay = true;
      $fin = (string)($row['fecha_fin'] ?? '');
      if ($fin !== '' && $fin !== '2100-01-01') {
        $today = new DateTime('today');
        $end = new DateTime($fin);
        if ($end < $today) { $hasActiveStay = false; }
      }
    }
    $st->close();
  }
} catch (Throwable $e) {
  $hasActiveStay = false;
}

if (!$hasActiveStay) {
  ?>
  <!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acceso restringido</title><meta http-equiv="refresh" content="10;url=<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>"><script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script><link rel="icon" href="/iubolab/imagenes/icono_circulo.png" type="image/png"><link href="https://fonts.googleapis.com/css2?family=Argentum+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" /><style>body{font-family:'Argentum Sans',sans-serif;}</style><script>tailwind.config={theme:{extend:{colors:{primary:'#5c068c','background-light':'#f8f6f6'}}}};</script></head>
  <body class="bg-background-light min-h-screen text-slate-900"><div class="min-h-screen flex items-center justify-center p-4"><div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm"><h1 class="text-2xl font-bold text-primary">No puedes acceder a Químicos</h1><p class="mt-3 text-slate-600">Solo los usuarios con estancia activa pueden entrar en esta sección. Si tu estancia está pendiente, finalizada o no existe, el acceso queda bloqueado.</p><p class="mt-4 text-sm text-slate-500">Serás redirigido a tu perfil en <span id="countdown">10</span> segundos.</p><a href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex mt-6 rounded-xl h-11 px-4 border border-primary text-primary text-sm font-bold items-center hover:bg-primary hover:text-white transition-colors">Volver ahora al perfil</a></div></div><script>let s=10;const el=document.getElementById('countdown');setInterval(()=>{s=Math.max(0,s-1);if(el)el.textContent=String(s);},1000);</script></body></html>
  <?php
  exit;
}
$groups = [];
// Mostrar solo grupos activos (soporta distintos esquemas: active, is_active o deleted_at)
$groupWhere = '';
$groupCols = [];
$gc = $mysqli->query("SHOW COLUMNS FROM groups");
while ($gc && ($col = $gc->fetch_assoc())) { $groupCols[] = strtolower((string)($col['Field'] ?? '')); }
if (in_array('is_active', $groupCols, true)) {
  $groupWhere = ' WHERE is_active = 1';
} elseif (in_array('active', $groupCols, true)) {
  $groupWhere = ' WHERE active = 1';
} elseif (in_array('deleted_at', $groupCols, true)) {
  $groupWhere = ' WHERE deleted_at IS NULL';
}
$gr = $mysqli->query("SELECT id, name FROM groups" . $groupWhere . " ORDER BY name ASC");
while ($gr && ($g = $gr->fetch_assoc())) { $groups[] = $g; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_all') {
  $quantities = $_POST['cantidades'] ?? [];
  $editRows = $_POST['edit_rows'] ?? [];
  if ($isAdmin && is_array($editRows) && isset($editRows['cas_nr']) && is_array($editRows['cas_nr'])) {
    foreach ($editRows['cas_nr'] as $idRaw => $casRaw) {
      $id = (int)$idRaw; if ($id <= 0) continue;
      $hasQty = isset($quantities[$id]);
      $qty = $hasQty ? (float)$quantities[$id] : 0.0;
      $cas = trim((string)$casRaw);
      $prov = trim((string)($editRows['proveedor'][$id] ?? ''));
      $nom = trim((string)($editRows['nombre'][$id] ?? ''));
      $owner = (int)($editRows['grupo_id'][$id] ?? 0);
      $loc = trim((string)($editRows['localizacion'][$id] ?? ''));
      $fmt = trim((string)($editRows['formato_size'][$id] ?? ''));
      $units = trim((string)($editRows['unidades'][$id] ?? ''));
      $acc = (($editRows['acceso'][$id] ?? 'publico') === 'privado') ? 'privado' : 'publico';
      $loanText = trim((string)($editRows['en_prestamo'][$id] ?? ''));
      $fdsUrl = trim((string)($editRows['fds_sds_url'][$id] ?? ''));
      $gpriv = (int)($editRows['acceso_grupo_privado_id'][$id] ?? 0);
      $gprivValue = ($acc === 'privado' && $gpriv > 0) ? $gpriv : null;
      if ($cas !== '' && $prov !== '' && $nom !== '' && $owner > 0 && $loc !== '') {
        if ($hasQty) {
          $up = $mysqli->prepare('UPDATE chemicals SET cas_nr=?, proveedor=?, nombre=?, formato_size=?, unidades=?, grupo_id=?, localizacion=?, cantidad=?, acceso=?, en_prestamo=?, fds_sds_url=?, acceso_grupo_privado_id=? WHERE id=?');
          if ($up) { $up->bind_param('sssssisdsssii', $cas, $prov, $nom, $fmt, $units, $owner, $loc, $qty, $acc, $loanText, $fdsUrl, $gprivValue, $id); $up->execute(); $up->close(); }
        } else {
          $up = $mysqli->prepare('UPDATE chemicals SET cas_nr=?, proveedor=?, nombre=?, formato_size=?, unidades=?, grupo_id=?, localizacion=?, acceso=?, en_prestamo=?, fds_sds_url=?, acceso_grupo_privado_id=? WHERE id=?');
          if ($up) { $up->bind_param('sssssissssii', $cas, $prov, $nom, $fmt, $units, $owner, $loc, $acc, $loanText, $fdsUrl, $gprivValue, $id); $up->execute(); $up->close(); }
        }
      }
    }
  } elseif (is_array($editRows) && isset($editRows['cas_nr']) && is_array($editRows['cas_nr'])) {
    foreach ($editRows['cas_nr'] as $idRaw => $casRaw) {
      $id = (int)$idRaw; if ($id <= 0) continue;
      $cas = trim((string)$casRaw);
      $prov = trim((string)($editRows['proveedor'][$id] ?? ''));
      $nom = trim((string)($editRows['nombre'][$id] ?? ''));
      $loc = trim((string)($editRows['localizacion'][$id] ?? ''));
      $fmt = trim((string)($editRows['formato_size'][$id] ?? ''));
      $units = trim((string)($editRows['unidades'][$id] ?? ''));
      $acc = (($editRows['acceso'][$id] ?? 'publico') === 'privado') ? 'privado' : 'publico';
      $loanText = trim((string)($editRows['en_prestamo'][$id] ?? ''));
      $fdsUrl = trim((string)($editRows['fds_sds_url'][$id] ?? ''));
      $gprivValue = ($acc === 'privado') ? $userGroupId : null;
      if ($cas === '' || $prov === '' || $nom === '' || $loc === '') { continue; }
      $up = $mysqli->prepare("UPDATE chemicals SET cas_nr=?, proveedor=?, nombre=?, formato_size=?, unidades=?, localizacion=?, acceso=?, en_prestamo=?, fds_sds_url=?, acceso_grupo_privado_id=? WHERE id=? AND grupo_id=?");
      if ($up) { $up->bind_param('sssssssssiii', $cas, $prov, $nom, $fmt, $units, $loc, $acc, $loanText, $fdsUrl, $gprivValue, $id, $userGroupId); $up->execute(); $up->close(); }
    }
  } elseif (is_array($quantities)) {
    foreach ($quantities as $idRaw => $qRaw) {
      $id = (int)$idRaw; $qty = (float)$qRaw; if ($id <= 0) continue;
      if ($isAdmin && is_array($editRows)) {
        $cas = trim((string)($editRows['cas_nr'][$id] ?? ''));
        $prov = trim((string)($editRows['proveedor'][$id] ?? ''));
        $nom = trim((string)($editRows['nombre'][$id] ?? ''));
        $owner = (int)($editRows['grupo_id'][$id] ?? 0);
        $loc = trim((string)($editRows['localizacion'][$id] ?? ''));
        $fmt = trim((string)($editRows['formato_size'][$id] ?? ''));
        $units = trim((string)($editRows['unidades'][$id] ?? ''));
        $acc = (($editRows['acceso'][$id] ?? 'publico') === 'privado') ? 'privado' : 'publico';
        $loanText = trim((string)($editRows['en_prestamo'][$id] ?? ''));
        $fdsUrl = trim((string)($editRows['fds_sds_url'][$id] ?? ''));
        $gpriv = (int)($editRows['acceso_grupo_privado_id'][$id] ?? 0);
        $gprivValue = ($acc === 'privado' && $gpriv > 0) ? $gpriv : null;
        if ($cas !== '' && $prov !== '' && $nom !== '' && $owner > 0 && $loc !== '') {
          $up = $mysqli->prepare('UPDATE chemicals SET cas_nr=?, proveedor=?, nombre=?, formato_size=?, unidades=?, grupo_id=?, localizacion=?, cantidad=?, acceso=?, en_prestamo=?, fds_sds_url=?, acceso_grupo_privado_id=? WHERE id=?');
          if ($up) { $up->bind_param('sssssisdsssii', $cas, $prov, $nom, $fmt, $units, $owner, $loc, $qty, $acc, $loanText, $fdsUrl, $gprivValue, $id); $up->execute(); $up->close(); }
        }
      } else {
        $loc = trim((string)($editRows['localizacion'][$id] ?? ''));
        if ($loc === '') { $loc = trim((string)($rows[$id]['localizacion'] ?? '')); }
        $up = $mysqli->prepare("UPDATE chemicals SET cantidad=?, localizacion=? WHERE id=? AND grupo_id=? AND (acceso='publico' OR (acceso='privado' AND acceso_grupo_privado_id=?))");
        if ($up) { $up->bind_param('dsiii', $qty, $loc, $id, $userGroupId, $userGroupId); $up->execute(); $up->close(); }
      }
    }
  }
  if ($canManageGroupChemicals && isset($_POST['new_rows']) && is_array($_POST['new_rows'])) {
    $nr = $_POST['new_rows'];
    $total = max(
      is_array($nr['cas_nr'] ?? null) ? count($nr['cas_nr']) : 0,
      is_array($nr['proveedor'] ?? null) ? count($nr['proveedor']) : 0,
      is_array($nr['nombre'] ?? null) ? count($nr['nombre']) : 0,
      is_array($nr['grupo_id'] ?? null) ? count($nr['grupo_id']) : 0,
      is_array($nr['localizacion'] ?? null) ? count($nr['localizacion']) : 0
    );
    for ($i = 0; $i < $total; $i++) {
      $cas = trim((string)($nr['cas_nr'][$i] ?? '')); $prov = trim((string)($nr['proveedor'][$i] ?? '')); $nom = trim((string)($nr['nombre'][$i] ?? ''));
      $owner = $isAdmin ? (int)($nr['grupo_id'][$i] ?? 0) : $userGroupId; $loc = trim((string)($nr['localizacion'][$i] ?? '')); $cant = (float)($nr['cantidad'][$i] ?? 0);
      $fmt = trim((string)($nr['formato_size'][$i] ?? '')); $units = trim((string)($nr['unidades'][$i] ?? ''));
      $acc = (($nr['acceso'][$i] ?? 'publico') === 'privado') ? 'privado' : 'publico'; $gp = (int)($nr['acceso_grupo_privado_id'][$i] ?? 0);
      $loanText = trim((string)($nr['en_prestamo'][$i] ?? ''));
      $fdsUrl = trim((string)($nr['fds_sds_url'][$i] ?? ''));
      $gpv = ($acc === 'privado' && $gp > 0) ? $gp : null;
      if ($cas === '' || $prov === '' || $nom === '' || $owner <= 0 || $loc === '') continue;
      $in = $mysqli->prepare('INSERT INTO chemicals (cas_nr, proveedor, nombre, formato_size, unidades, grupo_id, localizacion, cantidad, acceso, en_prestamo, fds_sds_url, acceso_grupo_privado_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
      if ($in) { $in->bind_param('sssssisdsssi', $cas, $prov, $nom, $fmt, $units, $owner, $loc, $cant, $acc, $loanText, $fdsUrl, $gpv); $in->execute(); $in->close(); }
    }
  }
  // PRG: evita aviso de reenvio de formulario en el navegador
  $redirectTo = 'quimicos.php';
  $parts = [];
  if (isset($_GET['q']) && trim((string)$_GET['q']) !== '') {
    $parts[] = 'q=' . rawurlencode(trim((string)$_GET['q']));
  }
  if (isset($_GET['lang']) && in_array($_GET['lang'], ['es','en'], true)) {
    $parts[] = 'lang=' . $_GET['lang'];
  }
  if ($parts) {
    $redirectTo .= '?' . implode('&', $parts);
  }
  header('Location: ' . $redirectTo);
  exit;
}
$q = trim((string)($_GET['q'] ?? ''));
$shouldQuery = strlen($q) >= 3;
$where = [];
if (!$isAdmin) { $where[] = "(c.acceso='publico' OR (c.acceso='privado' AND (c.acceso_grupo_privado_id=" . (int)$userGroupId . " OR c.grupo_id=" . (int)$userGroupId . ')))'; }
if (strlen($q) >= 3) { $where[] = "c.nombre LIKE '%" . $mysqli->real_escape_string($q) . "%'"; }
$sql = "SELECT c.*, go.name AS grupo_owner, gp.name AS grupo_privado FROM chemicals c LEFT JOIN groups go ON go.id=c.grupo_id LEFT JOIN groups gp ON gp.id=c.acceso_grupo_privado_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY c.nombre ASC LIMIT 300';
$rows=[]; if ($shouldQuery) { $r=$mysqli->query($sql); while($r&&($row=$r->fetch_assoc())) $rows[]=$row; }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Químicos</title><script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script><link rel="icon" href="/iubolab/imagenes/icono_circulo.png" type="image/png"><link rel="icon" type="image/png" sizes="32x32" href="/iubolab/imagenes/icono_circulo.png"><link rel="icon" type="image/png" sizes="16x16" href="/iubolab/imagenes/icono_circulo.png"><link rel="apple-touch-icon" href="/iubolab/imagenes/icono_circulo.png"><link href="https://fonts.googleapis.com/css2?family=Argentum+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" /><link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" /><script>tailwind.config={theme:{extend:{colors:{primary:'#5c068c','background-light':'#f8f6f6'},fontFamily:{display:['Argentum Sans','sans-serif']}}}};</script><style>body{font-family:'Argentum Sans',sans-serif;}</style></head>
<body class="bg-background-light min-h-screen text-slate-900"><div class="relative flex min-h-screen w-full flex-col"><header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-slate-200 bg-white px-4 md:px-10 py-4 fixed top-0 left-0 right-0 z-50"><div class="flex items-center gap-3"><img alt="Logo" class="h-10" src="/iubolab/imagenes/instituto-biorganica-agonzalez-original.png" /><h2 data-i18n="title" class="text-lg font-bold border-l border-slate-300 pl-4">Control de Químicos</h2></div><div class="flex items-center gap-3 w-full md:w-auto justify-end"><?php if($isUserRole): ?><div class="hidden md:flex items-center gap-2 text-sm font-medium"><span id="langEs" class="text-primary cursor-pointer border-b-2 border-primary pb-0.5">ES</span><span class="text-slate-400">|</span><span id="langEn" class="text-slate-400 hover:text-primary cursor-pointer transition-colors border-b-2 border-transparent hover:border-slate-400 pb-0.5">EN</span></div><?php endif; ?><a href="<?= htmlspecialchars($profileUrl) ?>" title="Volver al perfil" class="rounded-xl h-11 w-11 border border-primary text-primary flex items-center justify-center hover:bg-primary hover:text-white transition-colors"><span class="material-symbols-outlined">person</span></a><button form="chemicalsForm" data-i18n="save" class="hidden md:flex rounded-xl h-11 px-4 border border-primary text-primary text-sm font-bold items-center hover:bg-primary hover:text-white">Guardar cambios</button><a href="/iubolab/logout" class="rounded-xl h-11 w-11 border border-primary text-primary flex items-center justify-center hover:bg-primary hover:text-white transition-colors"><span class="material-symbols-outlined">power_settings_new</span></a></div><?php if($isUserRole): ?><div class="md:hidden w-full flex items-center justify-end gap-2 text-sm font-medium"><button type="button" id="langEsMobile" class="text-primary border-b-2 border-primary pb-0.5">ES</button><span class="text-slate-400">|</span><button type="button" id="langEnMobile" class="text-slate-400 border-b-2 border-transparent pb-0.5">EN</button></div><?php endif; ?></header><main class="pt-32 md:pt-28 pb-10"><div class="max-w-[1400px] mx-auto p-4 md:p-6"><section class="rounded-2xl border border-slate-200 bg-white p-3 md:p-4"><form method="get" class="flex gap-2 items-center"><input id="searchInput" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre (mínimo 3 caracteres)" class="flex-1 rounded-lg border-slate-300 px-3 py-2"><button data-i18n="search" class="rounded-lg px-4 py-2 bg-primary text-white font-semibold">Buscar</button><?php if($canManageGroupChemicals): ?><button type="button" id="addChemicalRowBtn" class="h-10 w-10 rounded-lg bg-primary text-white text-xl">+</button><?php endif; ?></form><form id="chemicalsForm" method="post" class="mt-4"><input type="hidden" name="action" value="save_all"><div class="md:hidden mb-3"><button data-i18n="save" class="w-full h-11 rounded-xl border border-primary text-primary text-sm font-bold">Guardar cambios</button></div><div class="rounded-xl border border-slate-200"><table class="w-full table-fixed text-sm"><thead class="bg-slate-100"><tr><th class="px-3 py-3 text-center"></th><th class="px-3 py-3 text-center">CAS-NR</th><th data-i18n="name" class="px-3 py-3 text-left">Nombre</th><th data-i18n="supplier" class="px-3 py-3 text-center">Proveedor</th><th class="px-3 py-3 text-center">Formato/Size</th><th class="px-3 py-3 text-center">Unidades</th><th data-i18n="owner" class="px-3 py-3 text-center">Grupo</th><th data-i18n="location" class="px-3 py-3 text-center">Localización</th><th class="px-3 py-3 text-center">Prestamo / On loan</th><th class="px-3 py-3 text-center">FDS/SDS</th></tr></thead><tbody>
<?php foreach($rows as $c): $canEditOwn = ((int)$c['grupo_id'] === (int)$userGroupId) || $isAdmin; ?><tr class="border-t border-slate-200"><td class="px-3 py-3 text-center"><?php if($canEditOwn): ?><select name="edit_rows[acceso][<?= (int)$c['id'] ?>]" class="w-full rounded border-slate-300 px-2 py-1"><option value="publico"<?= $c['acceso']==='publico'?' selected':'' ?>>publico</option><option value="privado"<?= $c['acceso']==='privado'?' selected':'' ?>>privado</option></select><?php else: ?><?php if(($c['acceso'] ?? '')==='privado'): ?><span class="material-symbols-outlined" title="Privado">lock</span><?php endif; ?><?php endif; ?></td><td class="px-3 py-3 text-center break-words"><?php if($canEditOwn): ?><input name="edit_rows[cas_nr][<?= (int)$c['id'] ?>]" value="<?= htmlspecialchars($c['cas_nr']) ?>" class="w-full rounded border-slate-300 px-2 py-1 text-center"><?php else: ?><?= htmlspecialchars($c['cas_nr']) ?><?php endif; ?></td><td class="px-3 py-3 break-words"><?php if($canEditOwn): ?><input name="edit_rows[nombre][<?= (int)$c['id'] ?>]" value="<?= htmlspecialchars($c['nombre']) ?>" class="w-full rounded border-slate-300 px-2 py-1"><?php else: ?><?= htmlspecialchars($c['nombre']) ?><?php endif; ?></td><td class="px-3 py-3 text-center break-words"><?php if($canEditOwn): ?><input name="edit_rows[proveedor][<?= (int)$c['id'] ?>]" value="<?= htmlspecialchars($c['proveedor']) ?>" class="w-full rounded border-slate-300 px-2 py-1 text-center"><?php else: ?><?= htmlspecialchars($c['proveedor']) ?><?php endif; ?></td><td class="px-3 py-3 text-center break-words"><?php if($canEditOwn): ?><input name="edit_rows[formato_size][<?= (int)$c['id'] ?>]" value="<?= htmlspecialchars((string)($c['formato_size'] ?? '')) ?>" class="w-full rounded border-slate-300 px-2 py-1 text-center"><?php else: ?><?= htmlspecialchars((string)($c['formato_size'] ?? '')) ?><?php endif; ?></td><td class="px-3 py-3 text-center break-words"><?php if($canEditOwn): ?><input name="edit_rows[unidades][<?= (int)$c['id'] ?>]" value="<?= htmlspecialchars((string)($c['unidades'] ?? '')) ?>" class="w-full rounded border-slate-300 px-2 py-1 text-center"><?php else: ?><?= htmlspecialchars((string)($c['unidades'] ?? '')) ?><?php endif; ?></td><td class="px-3 py-3 text-center"><?php if($isAdmin): ?><select name="edit_rows[grupo_id][<?= (int)$c['id'] ?>]" class="w-full rounded border-slate-300 px-2 py-1"><?php foreach($groups as $g): ?><option value="<?= (int)$g['id'] ?>"<?= ((int)$c['grupo_id']===(int)$g['id'])?' selected':'' ?>><?= htmlspecialchars($g['name']) ?></option><?php endforeach; ?></select><?php else: ?><?= htmlspecialchars((string)$c['grupo_owner']) ?><?php endif; ?></td><td class="px-3 py-3 text-center break-words"><?php if($canEditOwn): ?><input name="edit_rows[localizacion][<?= (int)$c['id'] ?>]" value="<?= htmlspecialchars($c['localizacion']) ?>" class="w-full rounded border-slate-300 px-2 py-1 text-center"><?php else: ?><span class="text-slate-500"><?= htmlspecialchars($c['localizacion']) ?></span><?php endif; ?></td><td class="px-3 py-3 text-center"><?php if($canEditOwn): ?><input name="edit_rows[en_prestamo][<?= (int)$c['id'] ?>]" value="<?= htmlspecialchars((string)($c['en_prestamo'] ?? '')) ?>" class="w-full rounded border-slate-300 px-2 py-1 text-center" placeholder="Detalle préstamo"><?php else: ?><?= htmlspecialchars((string)($c['en_prestamo'] ?? '')) ?><?php endif; ?></td><td class="px-3 py-3 text-center"><?php if($canEditOwn): ?><input name="edit_rows[fds_sds_url][<?= (int)$c['id'] ?>]" value="<?= htmlspecialchars((string)($c['fds_sds_url'] ?? '')) ?>" class="w-full rounded border-slate-300 px-2 py-1 text-center" placeholder="https://..."><?php else: ?><?php $url = trim((string)($c['fds_sds_url'] ?? '')); if ($url !== ''): ?><a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer" class="text-primary underline">Ver</a><?php endif; ?><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(strlen($q)===0): ?><tr><td colspan="10" class="p-4 text-center text-slate-500">Usa el buscador (mínimo 3 caracteres) y pulsa Enter.</td></tr><?php endif; ?>
<?php if(strlen($q)>0 && strlen($q)<3): ?><tr><td colspan="10" class="p-4 text-center text-amber-700">Escribe al menos 3 caracteres para buscar.</td></tr><?php endif; ?>
<?php if(strlen($q)>=3 && count($rows)===0): ?><tr><td colspan="10" class="p-4 text-center text-slate-500">No hay resultados.</td></tr><?php endif; ?>
</tbody></table></div></form></section></div></main></div>
<?php if($canManageGroupChemicals): ?><script>const groupOptions=`<?php foreach($groups as $g): ?><option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['name'],ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?>`;const userGroupId=<?= (int)$userGroupId ?>;const isAdminUser=<?= $isAdmin ? 'true' : 'false' ?>;document.getElementById('addChemicalRowBtn')?.addEventListener('click',()=>{const tb=document.querySelector('#chemicalsForm tbody');if(!tb)return;const tr=document.createElement('tr');tr.className='border-t border-slate-200 bg-amber-50';const groupCell=isAdminUser?('<select name="new_rows[grupo_id][]" class="rounded border-slate-300 px-2 py-1" required>'+groupOptions+'</select>'):('<input type="hidden" name="new_rows[grupo_id][]" value="'+String(userGroupId)+'"><span class="text-slate-600 text-xs">Tu grupo</span>');tr.innerHTML='<td class="px-4 py-3 text-center"></td><td class="px-4 py-3 text-center"><input name="new_rows[cas_nr][]" class="w-32 rounded border-slate-300 px-2 py-1 text-center" required></td><td class="px-4 py-3"><input name="new_rows[nombre][]" class="w-full rounded border-slate-300 px-2 py-1" required></td><td class="px-4 py-3 text-center"><input name="new_rows[proveedor][]" class="w-36 rounded border-slate-300 px-2 py-1 text-center" required></td><td class="px-4 py-3 text-center"><input name="new_rows[formato_size][]" class="w-32 rounded border-slate-300 px-2 py-1 text-center"></td><td class="px-4 py-3 text-center"><input name="new_rows[unidades][]" class="w-24 rounded border-slate-300 px-2 py-1 text-center"></td><td class="px-4 py-3 text-center">'+groupCell+'</td><td class="px-4 py-3 text-center"><input name="new_rows[localizacion][]" class="w-36 rounded border-slate-300 px-2 py-1 text-center" required></td><td class="px-4 py-3 text-center"><input name="new_rows[en_prestamo][]" class="w-36 rounded border-slate-300 px-2 py-1 text-center" placeholder="Detalle préstamo"></td><td class="px-4 py-3 text-center"><input name="new_rows[fds_sds_url][]" class="w-40 rounded border-slate-300 px-2 py-1 text-center" placeholder="https://..."><input type="hidden" name="new_rows[acceso][]" value="publico"><input type="hidden" name="new_rows[acceso_grupo_privado_id][]" value="0"><input type="hidden" name="new_rows[cantidad][]" value="0"></td>';tb.prepend(tr);});</script><?php endif; ?>
<script>
const i18n={es:{title:'Control de Químicos',back:'Volver al perfil',save:'Guardar cambios',search:'Buscar',supplier:'Proveedor',name:'Nombre',owner:'Grupo propietario',location:'Localización',amount:'Cantidad',access:'Acceso',privateGroup:'Grupo privado',searchPh:'Buscar por nombre (mínimo 3 caracteres)'},en:{title:'Chemicals Control',back:'Back to profile',save:'Save changes',search:'Search',supplier:'Supplier',name:'Name',owner:'Owner group',location:'Location',amount:'Amount',access:'Access',privateGroup:'Private group',searchPh:'Search by name (minimum 3 characters)'}};
function paintLang(l){const es=[document.getElementById('langEs'),document.getElementById('langEsMobile')];const en=[document.getElementById('langEn'),document.getElementById('langEnMobile')];if(l==='es'){es.forEach(b=>b&&(b.className='text-primary cursor-pointer border-b-2 border-primary pb-0.5'));en.forEach(b=>b&&(b.className='text-slate-400 hover:text-primary cursor-pointer transition-colors border-b-2 border-transparent hover:border-slate-400 pb-0.5'));}else{en.forEach(b=>b&&(b.className='text-primary cursor-pointer border-b-2 border-primary pb-0.5'));es.forEach(b=>b&&(b.className='text-slate-400 hover:text-primary cursor-pointer transition-colors border-b-2 border-transparent hover:border-slate-400 pb-0.5'));}}
function applyLang(l){localStorage.setItem('chem_lang',l);document.querySelectorAll('[data-i18n]').forEach(el=>{const k=el.getAttribute('data-i18n');if(i18n[l][k])el.textContent=i18n[l][k];});const s=document.getElementById('searchInput');if(s)s.placeholder=i18n[l].searchPh;paintLang(l);}
document.getElementById('langEs')?.addEventListener('click',()=>applyLang('es'));document.getElementById('langEn')?.addEventListener('click',()=>applyLang('en'));document.getElementById('langEsMobile')?.addEventListener('click',()=>applyLang('es'));document.getElementById('langEnMobile')?.addEventListener('click',()=>applyLang('en'));applyLang(localStorage.getItem('chem_lang')||'es');
</script></body></html>


