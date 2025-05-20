<?php
session_start();
header('Content-Type: application/json');

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

// ✅ Validate ID
if (!$id) {
    echo json_encode(["success" => false, "message" => "Missing employee ID"]);
    exit;
}

// ✅ DELETE from employee_directory
$stmt = $conn->prepare("DELETE FROM employee_directory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->error) {
    echo json_encode(["success" => false, "message" => $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

$action = "Deleted employee record (ID $id)";
$stmt->close();

// ✅ Log audit trail
$user_name = $_SESSION['userName'] ?? 'Unknown User';
$role = $_SESSION['role'] ?? 'Unknown Role';
$log = $conn->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
$log->bind_param("sss", $user_name, $role, $action);
$log->execute();
$log->close();

$conn->close();
echo json_encode(["success" => true]);
