<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "DB connection failed"]);
  exit;
}

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  echo json_encode(["success" => false, "message" => "Invalid or missing student ID"]);
  exit;
}

$stmt = $conn->prepare("UPDATE students SET status = 'Left', updatedAt = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  echo json_encode(["success" => true, "updatedRows" => $stmt->affected_rows]);
} else {
  echo json_encode(["success" => false, "message" => $stmt->error]);
}
?>
