<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['week_start']) || !is_array($data['entries'])) {
  echo json_encode(["success" => false, "message" => "Invalid request data"]);
  exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "DB connection failed"]);
  exit;
}

// Clean existing records for this user/week
$stmt = $conn->prepare("DELETE FROM teacher_timesheets WHERE user_id = ? AND week_start = ?");
$stmt->bind_param("is", $userId, $data['week_start']);
$stmt->execute();
$stmt->close();

// Insert updated entries
$stmt = $conn->prepare("
  INSERT INTO teacher_timesheets 
    (user_id, date, day, start_time, end_time, hours_worked, week_start, is_absent) 
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($data['entries'] as $entry) {
  $date       = $entry['date'] ?? null;
  $day        = $entry['day'] ?? null;
  $start      = $entry['start_time'] ?? null;
  $end        = $entry['end_time'] ?? null;
  $hours      = is_numeric($entry['hours_worked']) ? $entry['hours_worked'] : 0;
  $weekStart  = $data['week_start'];
  $isAbsent   = !empty($entry['is_absent']) ? 1 : 0;

  if ($date && $day) {
    $stmt->bind_param("issssdsi", $userId, $date, $day, $start, $end, $hours, $weekStart, $isAbsent);
    $stmt->execute();
  }
}

$stmt->close();
$conn->close();

echo json_encode(["success" => true]);
