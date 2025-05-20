<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "DB connection failed"]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$title = trim($data["title"] ?? "");
$date = trim($data["date"] ?? "");

if ($title === "" || $date === "") {
  echo json_encode(["success" => false, "message" => "Missing title or date"]);
  exit;
}

$stmt = $conn->prepare("DELETE FROM calendar_events WHERE title = ? AND date = ?");
$stmt->bind_param("ss", $title, $date);
$success = $stmt->execute();

echo json_encode(["success" => $success]);
?>
