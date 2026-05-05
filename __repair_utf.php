<?php
$files = ['Loggin.php','admin.php','empleado.php','supervisor.php','Formulario.php','Recuperacion.html','change_password.php','resetear_contraseña.php','.htaccess'];
$repl = [
  'ContraseÃ±a' => 'Contraseña',
  'contraseÃ±a' => 'contraseña',
  'Â¿' => '¿',
  'Â¡' => '¡',
  'sesiÃ³n' => 'sesión',
  'AdministraciÃ³n' => 'Administración',
  'RecuperaciÃ³n' => 'Recuperación',
  'IncorporaciÃ³n' => 'Incorporación',
  'InstituciÃ³n' => 'Institución',
  'PaÃ­s' => 'País',
  'TelÃ©fono' => 'Teléfono',
  'InformaciÃ³n' => 'Información',
  'FinalizaciÃ³n' => 'Finalización',
  'Quimicos' => 'Químicos',
  'quimicos' => 'quimicos',
  'Localizacion' => 'Localización',
  'minimo' => 'mínimo',
  'RedirecciÃ³n' => 'Redirección',
  'canÃ³nica' => 'canónica',
  'RecuperaciÃ³n / contraseÃ±a' => 'Recuperación / contraseña',
  'resetear_contraseÃ±a.php' => 'resetear_contraseña.php'
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
