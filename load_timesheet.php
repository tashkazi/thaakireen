<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

$userId = $_SESSION['user_id'];
$weekStart = $_GET['week_start'] ?? null;

if (!$weekStart) {
  echo json_encode(["success" => false, "message" => "Missing week_start"]);
  exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "DB connection failed"]);
  exit;
}

$stmt = $conn->prepare("
  SELECT date, day, start_time, end_time, hours_worked, is_absent 
  FROM teacher_timesheets 
  WHERE user_id = ? AND week_start = ?
");
$stmt->bind_param("is", $userId, $weekStart);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
  $entries[] = $row;
}

echo json_encode(["success" => true, "entries" => $entries]);
