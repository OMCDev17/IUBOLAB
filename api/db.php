<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function sendDbError(string $message, int $status = 500): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function connectDb()
{
    $config = require __DIR__ . '/config.php';
    $usingMysqli = class_exists('mysqli');

    if ($usingMysqli) {
        $db = @new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);
        if ($db->connect_errno) {
            sendDbError('Error de conexión con la base de datos');
        }
        $db->set_charset($config['charset']);
        $db->query("SET NAMES {$config['charset']}");
        return ['driver' => 'mysqli', 'conn' => $db];
    }

    if (extension_loaded('pdo_mysql')) {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";
            $db = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return ['driver' => 'pdo', 'conn' => $db];
        } catch (Throwable $e) {
            sendDbError('Error de conexión con la base de datos');
        }
    }

    sendDbError('Extensiones mysqli/pdo_mysql no disponibles en PHP.');
}
