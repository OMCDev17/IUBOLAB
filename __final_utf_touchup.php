<?php
$targets = [
  'supervisor.php',
  'admin.php',
  'landing.php',
  'send_recovery_email.php',
  'seguridad.php',
  'empleado.php'
];

$map = [
  "ÃƒÆ’¢Ãƒ¢Ã¢â‚¬Å¡¬Ãƒ¢Ã¢â€š¬" => "-",
  "invÃƒÆ’Ã†'¡lida" => "inválida",
  "EstÃƒÆ’¡s" => "Estás",
  "serÃƒÆ’¡" => "será",
  "â–¸" => "▸",
  "âœ”" => "✔",
  "ÃƒÆ’Ã†'" => "",
  "ÃƒÆ’" => "Ã",
];

foreach ($targets as $f) {
  if (!file_exists($f)) continue;
  $c = file_get_contents($f);
  if ($c === false) continue;
  $n = strtr($c, $map);

  // Fix known broken regex literals left by prior corruption
  $n = str_replace("const suspicious = /[ÃƒÆ’][\\x80-\\u017F]?|Ãƒ¢Ã¢â€š¬|Ãƒ¢Ã¢â€š¬Ã¢â€ž¢|Ãƒ¢Ã¢â€š¬Ã…\"|Ãƒ¢Ã¢â€š¬|ÃƒÆ’Ã†'|/;",
                   "const suspicious = /[Ã][\\x80-\\u017F]?|Ã¢â‚¬|Ã¢â‚¬â„¢|Ã¢â‚¬Å“|Ã¢â‚¬|ÃƒÆ’|/;", $n);
  $n = str_replace("const suspicious = /[ÃƒÆ’][\\x80-\\u017F]?|Ãƒ¢Ã¢â€š¬|Ãƒ¢Ã¢â€š¬Ã¢â€ž¢|Ãƒ¢Ã¢â€š¬Ã…\"|Ãƒ¢Ã¢â€š¬|ÃƒÆ’Ã†'|/;",
                   "const suspicious = /[Ã][\\x80-\\u017F]?|Ã¢â‚¬|Ã¢â‚¬â„¢|Ã¢â‚¬Å“|Ã¢â‚¬|ÃƒÆ’|/;", $n);

  if ($n !== $c) {
    file_put_contents($f, preg_replace('/^\xEF\xBB\xBF/', '', $n));
    echo "fixed: $f\n";
  }
}
