<?php
$c=require __DIR__ . '/api/config.php';
$m=new mysqli($c['host'],$c['user'],$c['pass'],$c['db']);
if($m->connect_errno){ echo "DB_ERR\n"; exit; }
$m->set_charset('utf8mb4');

function one($m,$sql){ $r=$m->query($sql); if(!$r){ return 'err'; } $a=$r->fetch_assoc(); return $a['c'] ?? '0'; }

$r=$m->query("SHOW TABLES LIKE 'groups'");
echo 'groups_table='.(($r&&$r->num_rows)?'yes':'no').PHP_EOL;
$r=$m->query("SHOW TABLES LIKE 'chemicals'");
echo 'chemicals_table='.(($r&&$r->num_rows)?'yes':'no').PHP_EOL;
echo 'groups_count='.one($m,'SELECT COUNT(*) c FROM groups').PHP_EOL;
echo 'chemicals_count='.one($m,'SELECT COUNT(*) c FROM chemicals').PHP_EOL;

$r=$m->query('SELECT c.id,c.nombre,c.grupo_owner_id,g.id gid,g.name gname FROM chemicals c LEFT JOIN groups g ON g.id=c.grupo_owner_id LIMIT 10');
while($r&&($x=$r->fetch_assoc())){
 echo $x['id'].'|'.$x['nombre'].'|owner='.$x['grupo_owner_id'].'|gid='.(string)$x['gid'].'|gname='.(string)$x['gname'].PHP_EOL;
}
