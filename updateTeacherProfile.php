<?php
session_start();
header('Content-Type: application/json');

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Get and sanitize input
$data = $_POST;

$user_id    = isset($data['user_id']) ? intval($data['user_id']) : null;
$firstName  = trim($data['first_name'] ?? '');
$lastName   = trim($data['last_name'] ?? '');
$email      = trim($data['email'] ?? '');
$phone      = trim($data['phone'] ?? '');
$password   = trim($data['password'] ?? '');

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user ID']);
    exit;
}

// Update with or without password
if (!empty($password)) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET firstName = ?, lastName = ?, email = ?, phone = ?, password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $firstName, $lastName, $email, $phone, $hashedPassword, $user_id);
} else {
    $sql = "UPDATE users SET firstName = ?, lastName = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $user_id);
}

// Execute and respond
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
