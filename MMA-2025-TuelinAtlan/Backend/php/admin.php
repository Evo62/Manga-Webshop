<?php
// Datenbankverbindung einbinden
require_once("db.php");

// Damit der Browser JSON versteht
header("Content-Type: application/json");

// Nur POST erlaubt
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // JSON-Daten vom Frontend lesen
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    // Eingaben prüfen und vorbereiten
    $name = $data["name"] ?? "";
    $description = $data["description"] ?? "";
    $price = floatval($data["price"] ?? 0);
    $stock = intval($data["stock"] ?? 0);
    $category = $data["category"] ?? "";

    // Wenn alle Felder ausgefüllt sind
    if ($name && $description && $price > 0 && $stock >= 0 && $category) {
        
        // SQL-Befehl vorbereiten
        $sql = "INSERT INTO products (name, description, price, stock, category)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdiss", $name, $description, $price, $stock, $category);

        // Ausführen und Ergebnis zurückgeben
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Fehler beim Einfügen"]);
        }

    } else {
        echo json_encode(["success" => false, "error" => "Ungültige Eingaben"]);
    }

} else {
    echo json_encode(["success" => false, "error" => "Nur POST erlaubt"]);
}
