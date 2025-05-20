<?php
ob_start(); // prevent any accidental output
header('Content-Type: application/json');
ini_set('display_errors', 0); // prevent raw error output in JSON
ini_set('log_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(["success" => false, "message" => "Missing ID"]);
    exit;
}

// Approve the student
$stmt = $conn->prepare("UPDATE registration_requests SET approved = 1, status = 'Approved' WHERE id = ?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();

echo json_encode(["success" => $success]);
?>
