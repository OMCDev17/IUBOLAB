<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

requireLogin(true);
$user = getSessionUser();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$newQuantity = isset($input['new_quantity']) ? (int)$input['new_quantity'] : null;

if ($productId <= 0 || $newQuantity === null || $newQuantity < 0) {
    sendDbError('Datos inválidos para actualizar stock', 400);
}

$userId = isset($user['id']) ? (int)$user['id'] : null;
$username = trim((string)($user['username'] ?? 'desconocido'));
if ($username === '') {
    $username = 'desconocido';
}

$dbInfo = connectDb();
$driver = $dbInfo['driver'];
$db = $dbInfo['conn'];

try {
    if ($driver === 'mysqli') {
        $db->begin_transaction();

        $select = $db->prepare('SELECT cantidad FROM chemical_products WHERE id = ? FOR UPDATE');
        $select->bind_param('i', $productId);
        $select->execute();
        $res = $select->get_result();
        $product = $res ? $res->fetch_assoc() : null;
        $select->close();

        if (!$product) {
            $db->rollback();
            sendDbError('Producto no encontrado', 404);
        }

        $oldQuantity = (int)$product['cantidad'];

        $update = $db->prepare('UPDATE chemical_products SET cantidad = ? WHERE id = ?');
        $update->bind_param('ii', $newQuantity, $productId);
        $update->execute();
        $update->close();

        $log = $db->prepare(
            'INSERT INTO stock_update_log (product_id, user_id, username, old_quantity, new_quantity)
             VALUES (?, ?, ?, ?, ?)'
        );
        $log->bind_param('iisii', $productId, $userId, $username, $oldQuantity, $newQuantity);
        $log->execute();
        $log->close();

        $db->commit();
    } else {
        $db->beginTransaction();

        $select = $db->prepare('SELECT cantidad FROM chemical_products WHERE id = ? FOR UPDATE');
        $select->execute([$productId]);
        $product = $select->fetch();
        if (!$product) {
            $db->rollBack();
            sendDbError('Producto no encontrado', 404);
        }

        $oldQuantity = (int)$product['cantidad'];

        $update = $db->prepare('UPDATE chemical_products SET cantidad = ? WHERE id = ?');
        $update->execute([$newQuantity, $productId]);

        $log = $db->prepare(
            'INSERT INTO stock_update_log (product_id, user_id, username, old_quantity, new_quantity)
             VALUES (?, ?, ?, ?, ?)'
        );
        $log->execute([$productId, $userId, $username, $oldQuantity, $newQuantity]);

        $db->commit();
    }

    echo json_encode([
        'success' => true,
        'product_id' => $productId,
        'old_quantity' => $oldQuantity,
        'new_quantity' => $newQuantity
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($driver === 'mysqli') {
        if ($db->errno) {
            $db->rollback();
        }
    } else {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
    }
    sendDbError('No se pudo actualizar el stock');
}
