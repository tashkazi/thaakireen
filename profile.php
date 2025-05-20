<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT id, firstName, lastName, email, phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['success' => true, 'teacher' => [
            'id' => $user['id'],
            'first_name' => $user['firstName'],
            'last_name' => $user['lastName'],
            'email' => $user['email'],
            'phone' => $user['phone']
        ]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
