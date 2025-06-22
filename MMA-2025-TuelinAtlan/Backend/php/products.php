<?php
header('Content-Type: application/json');
include 'db.php';

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $like = "%$search%";
    $sql  = "
      SELECT id, name, category, price, description
      FROM products
      WHERE name LIKE '$like' 
         OR description LIKE '$like'
    ";
    $stmt = $pdo->query($sql);
} else {
    $stmt = $pdo->query(
      "SELECT id, name, category, price, description
       FROM products"
    );
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
