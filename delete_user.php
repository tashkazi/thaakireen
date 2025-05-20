<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$data = json_decode(file_get_contents("php://input"), true);

$userId = $data['userId'] ?? null;
if (!$userId || !is_numeric($userId)) {
    echo json_encode(["success" => false, "message" => "Missing or invalid user ID"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // ✅ Check if user exists before deleting
    $check = $pdo->prepare("SELECT email, firstName, lastName FROM users WHERE id = ?");
    $check->execute([$userId]);
    $user = $check->fetch();

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    $fullName = $user['firstName'] . ' ' . $user['lastName'];
    $email = $user['email'];

    // ✅ Delete from users table
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    // ✅ Optional: delete from other role-specific tables
    // $pdo->prepare("DELETE FROM teachers WHERE user_id = ?")->execute([$userId]);

    // ✅ Audit log entry
    $performedBy = $_SESSION['userName'] ?? 'Unknown User';
    $role = $_SESSION['role'] ?? 'Unknown Role';
    $action = "Deleted user '$fullName' (email: $email, ID: $userId)";

    $log = $pdo->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
    $log->execute([$performedBy, $role, $action]);

    echo json_encode([
        "success" => true,
        "message" => "User deleted and action logged"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error deleting user",
        "error" => $e->getMessage()
    ]);
}
