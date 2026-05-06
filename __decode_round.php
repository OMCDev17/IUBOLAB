<?php
$files=['landing.php','admin.php'];
function bad($s){return substr_count($s,'Ã')+substr_count($s,'Â')+substr_count($s,'â');}
foreach($files as $f){$c=file_get_contents($f); if($c===false) continue; $orig=$c; for($i=0;$i<3;$i++){ $d=utf8_decode($c); $e=utf8_encode($d); if($e===$c) break; if(bad($e)<=bad($c)) $c=$e; else break; }
if($c!==$orig){file_put_contents($f,$c); echo "fixed $f\n";}}
