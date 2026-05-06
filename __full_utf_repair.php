<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS));
$exts = ['php','html','htm','js','css','md','txt'];

function fix_text($s){
  $map = [
    'ó'=>'ó','ñ'=>'ñ','á'=>'á','é'=>'é','í'=>'í','ú'=>'ú',
    'ó'=>'ó','ñ'=>'ñ','á'=>'á','é'=>'é','í'=>'í','ú'=>'ú',
    'ó'=>'ó','ñ'=>'ñ','á'=>'á','é'=>'é','í'=>'í','ú'=>'ú',
    'ó'=>'ó','ñ'=>'ñ','á'=>'á','é'=>'é','í'=>'í','ú'=>'ú',
    'Á'=>'Á','É'=>'É','Í'=>'Í','Ó'=>'Ó','Ú'=>'Ú','Ñ'=>'Ñ',
    '¿'=>'¿','¡'=>'¡','©'=>'©','©'=>'©',
    '"'=>'"','"'=>'"','''=>"'",'''=>"'",'-'=>'-','-'=>'-','*'=>'*','...'=>'...','←'=>'←','→'=>'→','✓'=>'✓',
    '🇪🇸'=>'🇪🇸','🇯🇵'=>'🇯🇵','🇲🇽'=>'🇲🇽','🇵🇪'=>'🇵🇪','🇭🇺'=>'🇭🇺','🇧🇪'=>'🇧🇪','🇿🇦'=>'🇿🇦',
    'electrónico'=>'electrónico','conexión'=>'conexión','inválido'=>'inválido','Código'=>'Código','código'=>'código','dígitos'=>'dígitos','Mínimo'=>'Mínimo','Número'=>'Número','más'=>'más',
    'sesión'=>'sesión','Administración'=>'Administración','Recuperación'=>'Recuperación','Incorporación'=>'Incorporación','Institución'=>'Institución','País'=>'País','Teléfono'=>'Teléfono','Información'=>'Información','Finalización'=>'Finalización','Localización'=>'Localización','académico'=>'académico','científico'=>'científico','categoría'=>'categoría','Máster'=>'Máster','política'=>'política','protección'=>'protección','gestión'=>'gestión','envío'=>'envío','rectificación'=>'rectificación','supresión'=>'supresión','oposición'=>'oposición','comunicación'=>'comunicación','modificación'=>'modificación','está'=>'está','después'=>'después','término'=>'término','Inténtelo'=>'Inténtelo'
  ];
  $s = strtr($s,$map);
  // limpia restos frecuentes
  $s = str_replace(['','','ÃƒÆ’Ã†'','','',''],['','','','','',''],$s);
  return $s;
}

$changed=0;
foreach($it as $f){
  if(!$f->isFile()) continue;
  $p=$f->getPathname();
  if(strpos($p,DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)!==false) continue;
  if(strpos($p,DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR)!==false) continue;
  if(strpos($p,DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR)!==false) continue;
  $ext=strtolower(pathinfo($p,PATHINFO_EXTENSION));
  if(!in_array($ext,$exts,true)) continue;

  $raw=@file_get_contents($p);
  if($raw===false||$raw==='') continue;
  $new=fix_text($raw);
  $new=preg_replace('/^\xEF\xBB\xBF/','',$new);
  if($new!==$raw){
    file_put_contents($p,$new);
    $changed++;
    echo "fixed: $p\n";
  }
}
echo "TOTAL_CHANGED=$changed\n";
