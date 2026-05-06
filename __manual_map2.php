<?php
$files=['landing.php','admin.php'];
$map=[
'Ã¡'=>'á','Ã©'=>'é','Ã­'=>'í','Ã³'=>'ó','Ãº'=>'ú','Ã±'=>'ñ','Ã'=>'Á','Ã‰'=>'É','Ã'=>'Í','Ã“'=>'Ó','Ãš'=>'Ú','Ã‘'=>'Ñ',
'Â¿'=>'¿','Â¡'=>'¡','Â·'=>'·','â??'=>'→','Ã¢â??Â¸'=>'▸','Ã³n'=>'ón','acciÃ³n'=>'acción','QuÃ­micos'=>'Químicos','AdministraciÃ³n'=>'Administración','clÃ­nicas'=>'clínicas','Ãºnico'=>'único','investigaciÃ³n'=>'investigación','aÃ±os'=>'años','satÃ©lite'=>'satélite','MÃ¡s'=>'Más','acciÃ³n'=>'acción','tÃ©cnico'=>'técnico','InformaciÃ³n'=>'Información','QuiÃ©nes'=>'Quiénes','QuÃ©'=>'Qué','Ã¡reas'=>'áreas','crÃ­ticas'=>'críticas','mÃ©dicas'=>'médicas','CÃ³mo'=>'Cómo','sesiÃ³n'=>'sesión','microscopÃ­a'=>'microscopía','atÃ³mica'=>'atómica','GestiÃ³n'=>'Gestión','GeoquÃ­mica'=>'Geoquímica','campaÃ±as'=>'campañas','anÃ¡lisis'=>'análisis','BioquÃ­mica'=>'Bioquímica','InvestigaciÃ³n'=>'Investigación','bioprospecciÃ³n'=>'bioprospección','QuimiometrÃ­a'=>'Quimiometría','quimiomÃ©tricos'=>'quimiométricos','SÃ­ntesis'=>'Síntesis','orgÃ¡nica'=>'orgánica','diseÃ±o'=>'diseño','sÃ­ntesis'=>'síntesis','analÃ­tica'=>'analítica','segÃºn'=>'según','OlvidÃ©'=>'Olvidé','envÃ­a'=>'envía','cÃ³digo'=>'código','QuiÃ©n'=>'Quién','SÃ­,'=>'Sí,','priorizarÃ¡'=>'priorizará','acreditado.'=>'acreditado.'
];
foreach($files as $f){$c=file_get_contents($f);$n=strtr($c,$map);file_put_contents($f,$n);echo "fixed $f\n";}
