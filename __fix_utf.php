<?php
$files = ['admin.php','empleado.php','supervisor.php','quimicos.php','.htaccess'];

function fix_mojibake($s){
    $prev = null;
    $cur = $s;
    for($i=0;$i<3;$i++){
        if($cur===$prev) break;
        $prev = $cur;
        $cur = @mb_convert_encoding($cur, 'UTF-8', 'Windows-1252');
    }
    return $cur;
}

foreach($files as $f){
    $raw = file_get_contents($f);
    if($raw===false) continue;
    $fixed = fix_mojibake($raw);
    // normaliza BOM fuera
    $fixed = preg_replace('/^\xEF\xBB\xBF/', '', $fixed);
    file_put_contents($f, $fixed);
    echo "fixed $f\n";
}
