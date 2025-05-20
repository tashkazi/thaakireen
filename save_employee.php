<?php
session_start();
header('Content-Type: application/json');

// ✅ DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit;
}

// ✅ Get posted data
$data = json_decode(file_get_contents("php://input"), true);
$id        = $data['id'] ?? null;
$fullName  = trim($data['full_name'] ?? '');
$type      = $data['type'] ?? '';
$email     = $data['email'] ?? '';
$phone     = $data['phone'] ?? '';
$notes     = $data['notes'] ?? '';
$action    = '';

// ✅ Validate required fields
if (empty($fullName) || empty($type)) {
    echo json_encode(["success" => false, "message" => "Full name and type are required"]);
    exit;
}

// ✅ Step 1: Insert or Update employee_directory
if ($id) {
    $stmt = $conn->prepare("UPDATE employee_directory SET full_name=?, type=?, email=?, phone=?, notes=? WHERE id=?");
    $stmt->bind_param("sssssi", $fullName, $type, $email, $phone, $notes, $id);
    $action = "Updated employee: $fullName (ID $id)";
} else {
    $stmt = $conn->prepare("INSERT INTO employee_directory (full_name, type, email, phone, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $fullName, $type, $email, $phone, $notes);
    $action = "Added new employee: $fullName";
}

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Save failed: " . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// ✅ Step 2: Log action in audit_logs
$user_name = $_SESSION['userName'] ?? 'Unknown User';
$role      = $_SESSION['role'] ?? 'Unknown Role';
$log = $conn->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
$log->bind_param("sss", $user_name, $role, $action);
$log->execute();
$log->close();

// ✅ Response
echo json_encode(["success" => true]);
$conn->close();
