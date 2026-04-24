<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

requireLogin(true);

$q = trim((string)($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 100);
$like = '%' . $q . '%';

if ($q === '') {
    echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$dbInfo = connectDb();
$driver = $dbInfo['driver'];
$db = $dbInfo['conn'];

try {
    if ($driver === 'mysqli') {
        $stmt = $db->prepare(
            'SELECT id, nombre, cantidad, unidad, updated_at
             FROM chemical_products
             WHERE nombre LIKE ?
             ORDER BY nombre ASC
             LIMIT 200'
        );
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    } else {
        $stmt = $db->prepare(
            'SELECT id, nombre, cantidad, unidad, updated_at
             FROM chemical_products
             WHERE nombre LIKE ?
             ORDER BY nombre ASC
             LIMIT 200'
        );
        $stmt->execute([$like]);
        $rows = $stmt->fetchAll();
    }

    echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    sendDbError('No se pudieron cargar los productos');
}
