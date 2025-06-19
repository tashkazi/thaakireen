<?php
session_start();
header('Content-Type: application/json');

// 1. Check login
if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

$userId = $_SESSION['user_id'];
$weekStart = $_GET['week_start'] ?? null;

// 2. Check if week_start provided
if (!$weekStart) {
  echo json_encode(["success" => false, "message" => "Missing week_start"]);
  exit;
}

// 3. Connect to DB
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "DB connection failed"]);
  exit;
}

// 4. Calculate range: Monday to Friday
$start = new DateTime($weekStart);
$end = clone $start;
$end->modify('+4 days');

$startStr = $start->format('Y-m-d');
$endStr = $end->format('Y-m-d');

// 5. Fetch all 5 days
$stmt = $conn->prepare("
  SELECT date, day, start_time, end_time, hours_worked, is_absent 
  FROM teacher_timesheets 
  WHERE user_id = ? AND date BETWEEN ? AND ?
  ORDER BY date ASC
");
$stmt->bind_param("iss", $userId, $startStr, $endStr);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
  $entries[] = $row;
}

echo json_encode(["success" => true, "entries" => $entries]);
exit;
