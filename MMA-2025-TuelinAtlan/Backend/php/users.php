<?php
header('Content-Type: application/json');
require_once 'db.php';
$action = $_GET['action'] ?? '';

if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
   
}
elseif ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
}
elseif ($action === 'list') {
    // Alle Benutzer abrufen (Admin)
    $stmt = $pdo->query("SELECT id, email, first_name, last_name, role FROM users");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
elseif ($action === 'search') {
    // Nutzer suchen (Admin)
    $q = $_GET['query'] ?? '';
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, role
                          FROM users
                          WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ?");
    $like = "%" . $q . "%";
    $stmt->execute([$like, $like, $like]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
else {
    echo json_encode(['error' => 'Ungültige Aktion']);
}

// 1) Registrierung
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $input['email'] ?? '';
    $first = $input['firstName'] ?? '';
    $last  = $input['lastName'] ?? '';
    $pass  = password_hash($input['password'] ?? '', PASSWORD_DEFAULT);

    // Check E-Mail existiert?
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount()) {
        echo json_encode(['success' => false, 'message' => 'E-Mail bereits vorhanden']);
        exit;
    }

    // Neuen User anlegen
    $stmt = $pdo->prepare(
        "INSERT INTO users (email, first_name, last_name, passwort, role)
         VALUES (?, ?, ?, ?, 'user')"
    );
    $ok = $stmt->execute([$email, $first, $last, $pass]);
    echo json_encode(['success' => $ok]);
    exit;
}

// 2) Login
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $input['email'] ?? '';
    $pwd   = $input['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($pwd, $user['passwort'])) {
        // Session speichern
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        unset($user['passwort']);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Login fehlgeschlagen']);
    }
    exit;
}

// 3) Logout
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// 4) User-Liste (Admin)
if ($action === 'list') {
    // Nur Admin
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['error' => 'Keine Berechtigung']); exit;
    }
    $stmt = $pdo->query("SELECT id, email, first_name, last_name, role FROM users");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}
elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // User löschen (Admin)
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $success = $stmt->execute([$id]);
    echo json_encode(['success' => $success]);
}


session_start(); //Session Managment

// Nach erfolgreichem Login
$_SESSION['user_id'] = $user['id'];      // damit man später weißt, welcher User eingeloggt ist
$_SESSION['role']    = $user['role'];    // 'admin' oder 'customer'

elseif ($action==='logout') { //alle Session Daten werden gelöscht
  session_destroy();
  echo json_encode(['success'=>true]); //Logout war erfolgreich 
  exit;
}

// Fallback
echo json_encode(['error' => 'Ungültige Aktion']);

?>
