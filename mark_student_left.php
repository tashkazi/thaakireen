<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "Database connection failed"]);
  exit;
}

// Get student ID from query
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
  echo json_encode(["success" => false, "message" => "Invalid or missing student ID"]);
  exit;
}

// Mark the student as "Left"
$stmt = $conn->prepare("UPDATE students SET status = 'Left', updatedAt = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "Failed to update status"]);
}
?>
