<?php
$files = ['Loggin.php','admin.php','empleado.php','supervisor.php','Formulario.php','Recuperacion.html','change_password.php','resetear_contraseña.php','.htaccess'];
$repl = [
  'Contraseña' => 'Contraseña',
  'contraseña' => 'contraseña',
  '¿' => '¿',
  '¡' => '¡',
  'sesión' => 'sesión',
  'Administración' => 'Administración',
  'Recuperación' => 'Recuperación',
  'Incorporación' => 'Incorporación',
  'Institución' => 'Institución',
  'País' => 'País',
  'Teléfono' => 'Teléfono',
  'Información' => 'Información',
  'Finalización' => 'Finalización',
  'Quimicos' => 'Químicos',
  'quimicos' => 'quimicos',
  'Localizacion' => 'Localización',
  'minimo' => 'mínimo',
  'Redirección' => 'Redirección',
  'canónica' => 'canónica',
  'Recuperación / contraseña' => 'Recuperación / contraseña',
  'resetear_contraseña.php' => 'resetear_contraseña.php'
];
foreach($files as $f){
  if(!file_exists($f)) continue;
  $c = file_get_contents($f);
  if($c===false) continue;
  $c = preg_replace('/^\xEF\xBB\xBF/', '', $c);
  $c = strtr($c, $repl);
  file_put_contents($f, $c);
  echo "fixed $f\n";
}
