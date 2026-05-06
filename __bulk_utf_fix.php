<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS));
$exts = ['php','html','htm','js','css','md','txt'];
$changed = 0;

function suspicious_count($s){
  $needles = ['Ã','¿','¡','â','Ãƒ','','�'];
  $n = 0;
  foreach($needles as $x){ $n += substr_count($s, $x); }
  return $n;
}
function try_fix($s){
  $prev = $s;
  for($i=0;$i<3;$i++){
    $fixed = @iconv('Windows-1252','UTF-8//IGNORE',$prev);
    if(!is_string($fixed) || $fixed === '' || $fixed === $prev) break;
    $prev = $fixed;
  }
  return $prev;
}

foreach($it as $f){
  if(!$f->isFile()) continue;
  $path = $f->getPathname();
  if(strpos($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)!==false) continue;
  if(strpos($path, DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR)!==false) continue;
  if(basename($path)==='__bulk_utf_fix.php') continue;
  $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  if(!in_array($ext, $exts, true)) continue;

  $raw = @file_get_contents($path);
  if($raw===false || $raw==='') continue;
  $before = suspicious_count($raw);
  if($before===0) continue;

  $fixed = try_fix($raw);
  $fixed = preg_replace('/^\xEF\xBB\xBF/', '', $fixed);
  $after = suspicious_count($fixed);

  if($after < $before){
    file_put_contents($path, $fixed);
    $changed++;
    echo "fixed: $path ($before -> $after)\n";
  }
}

echo "TOTAL_CHANGED=$changed\n";
