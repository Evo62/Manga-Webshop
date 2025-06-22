<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
$input = json_decode(file_get_contents('php://input'), true) ?: [];
// Bestellung erstellen (POST)
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $userId = intval($input['user_id'] ?? 0);
    $items  = $input['items'] ?? [];
    if (!$userId || empty($items)) {
        echo json_encode(['success'=>false,'message'=>'Fehlende Daten']); exit;
    }
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO orders (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        $orderId = $pdo->lastInsertId();
        $itemStmt = $pdo->prepare(
            "INSERT INTO order_items (order_id,product_id,quantity,price)
             VALUES (?,?,?,?)"
        );
        foreach ($items as $it) {
            $itemStmt->execute([$orderId,$it['product_id'],$it['quantity'],$it['price']]);
            $pdo->prepare("UPDATE products SET stock=stock-? WHERE id=?")
                ->execute([$it['quantity'],$it['product_id']]);
        }
        $pdo->commit();
        echo json_encode(['success'=>true,'order_id'=>$orderId]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'Bestellfehler']);
    }
    exit;
}
elseif (isset($_GET['user_id'])) {
    // Eigene Käufe abrufen
    $uid = intval($_GET['user_id']);
    $stmt = $pdo->prepare(
        "SELECT o.id AS order_id, o.created_at,
                oi.product_id, oi.quantity, oi.price,
                p.name
         FROM orders o
         JOIN order_items oi ON o.id = oi.order_id
         JOIN products p ON oi.product_id = p.id
         WHERE o.user_id = ?");
    $stmt->execute([$uid]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
else {
    echo json_encode(['error' => 'Fehlende Daten']);
}
?>
