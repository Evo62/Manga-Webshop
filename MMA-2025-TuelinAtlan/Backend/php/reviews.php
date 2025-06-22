<?php
// POST: Bewertung speichern
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!$userId) { echo json_encode(['success'=>false,'message'=>'Login nötigt']); exit; }
    $d = json_decode(file_get_contents('php://input'),true);
    $pid = intval($d['product_id'] ?? 0);
    $rt  = intval($d['rating'] ?? 0);
    $cm  = $d['comment'] ?? '';
    if ($pid && $rt) {
        $stmt = $pdo->prepare(
            "INSERT INTO reviews (product_id,user_id,rating,comment)
             VALUES (?,?,?,?)"
        );
        echo json_encode(['success'=>$stmt->execute([$pid,$userId,$rt,$cm])]);
    } else {
        echo json_encode(['success'=>false,'message'=>'Ungültige Daten']);
    }
    exit;
}

// GET: Bewertungen mit verified-Flag
if (isset($_GET['product_id'])) {
    $pid = intval($_GET['product_id']);
    $stmt= $pdo->prepare(
        "SELECT r.user_id,r.rating,r.comment,r.created_at,u.email
         FROM reviews r JOIN users u ON r.user_id=u.id
         WHERE r.product_id=?"
    );
    $stmt->execute([$pid]);
    $revs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $chk = $pdo->prepare(
        "SELECT 1 FROM orders o
         JOIN order_items oi ON o.id=oi.order_id
         WHERE o.user_id=? AND oi.product_id=? LIMIT 1"
    );
    foreach ($revs as &$rv) {
        $chk->execute([$rv['user_id'],$pid]);
        $rv['verified'] = (bool)$chk->fetchColumn();
    }
    echo json_encode($revs); exit;
}

// Fallback
echo json_encode(['error'=>'Ungültige Anfrage']);
?>
