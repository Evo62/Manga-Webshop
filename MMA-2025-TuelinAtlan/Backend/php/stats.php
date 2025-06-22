<?php

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

// 1) Lagerbestand pro Kategorie
$stmt = $pdo->query(
    "SELECT category, SUM(stock) AS total_stock
     FROM products
     GROUP BY category"
);
$stockData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2) Umsatz pro Monat (letzte 6 Monate)
$stmt2 = $pdo->query(
    "SELECT DATE_FORMAT(o.created_at, '%Y-%m') AS month,
             SUM(oi.quantity * oi.price) AS total_sales
       FROM orders o
       JOIN order_items oi ON o.id = oi.order_id
      WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
      GROUP BY month
      ORDER BY month"
);
$salesData = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// JSON-Ausgabe
echo json_encode([
    'stockByCategory' => $stockData,
    'monthlySales'    => $salesData
]);

?>