<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

$userId = $_SESSION['user_id'];
$month = $_GET['month'] ?? null; // format: YYYY-MM

if (!$month) {
  echo json_encode(["success" => false, "message" => "Month required"]);
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
  WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
  ORDER BY date ASC
");
$stmt->bind_param("is", $userId, $month);
$stmt->execute();

$result = $stmt->get_result();
$entries = [];
while ($row = $result->fetch_assoc()) {
  $entries[] = $row;
}

echo json_encode(["success" => true, "entries" => $entries]);
