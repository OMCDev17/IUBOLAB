<?php
$files=['supervisor.php','admin.php','seguridad.php'];
function bad($s){return substr_count($s,'Ã')+substr_count($s,'Â')+substr_count($s,'â');}
foreach($files as $f){
  $c=file_get_contents($f); if($c===false){echo "skip $f\n"; continue;}
  $orig=$c;
  for($i=0;$i<4;$i++){
    $e=utf8_encode(utf8_decode($c));
    if($e===$c) break;
    if(bad($e)<=bad($c)) $c=$e; else break;
  }
  if($c!==$orig){file_put_contents($f,$c); echo "fixed $f\n";} else {echo "ok $f\n";}
}
?>
