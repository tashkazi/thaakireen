<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

// Get file path before deleting
$stmt = $conn->prepare("SELECT file_path FROM teacher_resources WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();

// Delete from DB
$stmt = $conn->prepare("DELETE FROM teacher_resources WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// Delete the file if it's not a link
if ($file && $file['file_path'] && !filter_var($file['file_path'], FILTER_VALIDATE_URL)) {
    @unlink($file['file_path']);
}

echo json_encode(['success' => true]);
