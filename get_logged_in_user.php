<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

// Connect to database to fetch title and first name
try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT title, firstName FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            "success" => true,
            "title" => $user['title'] ?? '',
            "firstName" => $user['firstName'] ?? '',
            "name" => trim(($user['title'] ?? '') . ' ' . ($user['firstName'] ?? ''))
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB error: " . $e->getMessage()]);
}
