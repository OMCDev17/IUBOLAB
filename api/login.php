<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/db.php';

$dbInfo = connectDb();
$driver = $dbInfo['driver'];
$db = $dbInfo['conn'];

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || empty($input['username']) || !array_key_exists('password', $input)) {
    $input = $_POST;
}
if (!is_array($input) || empty($input['username']) || !array_key_exists('password', $input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan credenciales'], JSON_UNESCAPED_UNICODE);
    exit;
}

$username = trim((string)$input['username']);
$password = (string)$input['password'];

if (session_status() === PHP_SESSION_NONE) {
    session_name('GESTIUBOSESSID');
    session_start();
}

try {
    $user = null;

    if ($driver === 'mysqli') {
        $stmt = $db->prepare('SELECT id, username, password, rol FROM app_users WHERE LOWER(username) = LOWER(?) LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    } else {
        $stmt = $db->prepare('SELECT id, username, password, rol FROM app_users WHERE LOWER(username) = LOWER(?) LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    }

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario o contraseña incorrectos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stored = (string)($user['password'] ?? '');
    $plainMatch = hash_equals($stored, $password);
    $hashMatch = password_get_info($stored)['algo'] !== 0 && password_verify($password, $stored);
    if (!$plainMatch && !$hashMatch) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario o contraseña incorrectos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sessionUser = [
        'id' => (int)$user['id'],
        'username' => (string)$user['username'],
        'rol' => (string)$user['rol'],
        'nombre' => (string)$user['username'],
        'apellidos' => '',
    ];

    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['user'] = $sessionUser;

    $role = strtolower($sessionUser['rol'] ?? '');
    $redirect = 'empleado.php';
    if ($role === 'admin') {
        $redirect = 'admin.php';
    } elseif ($role === 'supervisor' || $role === 'coordinador') {
        $redirect = 'supervisor.php';
    } elseif ($role === 'seguridad') {
        $redirect = 'seguridad.php';
    }

    echo json_encode([
        'success' => true,
        'user' => $sessionUser,
        'redirect' => $redirect,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno de autenticación'], JSON_UNESCAPED_UNICODE);
}
