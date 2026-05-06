<?php
foreach(['seguridad.php','empleado.php','supervisor.php'] as $f){
  $c=file_get_contents($f); if($c===false) continue;
  $c=preg_replace('/const suspicious = \/.*?\//','const suspicious = /[Ã][\\x80-\\u017F]?|Ã¢â‚¬|Ã¢â‚¬â„¢|Ã¢â‚¬Å“|Ã¢â‚¬|ÃƒÆ’/', $c, 1);
  file_put_contents($f,$c);
  echo "fixed $f\n";
}
