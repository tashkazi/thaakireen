<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Unauthorized"]);
  exit;
}

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['entries']) || !is_array($data['entries'])) {
  echo json_encode(["success" => false, "message" => "Invalid payload"]);
  exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "DB connection failed"]);
  exit;
}

$insertStmt = $conn->prepare("
  INSERT INTO teacher_timesheets (user_id, date, day, start_time, end_time, hours_worked, is_absent, week_start)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
    start_time = VALUES(start_time),
    end_time = VALUES(end_time),
    hours_worked = VALUES(hours_worked),
    is_absent = VALUES(is_absent),
    day = VALUES(day),
    week_start = VALUES(week_start)
");

foreach ($data['entries'] as $entry) {
  $date        = $entry['date'] ?? '';
  $day         = $entry['day'] ?? '';
  $start       = $entry['start'] ?: null;
  $end         = $entry['end'] ?: null;
  $hours       = is_numeric($entry['hours']) ? $entry['hours'] : 0;
  $absent      = intval($entry['absent'] ?? 0);
  $weekStart   = $entry['weekStart'] ?? $date;

  $insertStmt->bind_param("issssdss", $userId, $date, $day, $start, $end, $hours, $absent, $weekStart);
  $insertStmt->execute();
}

echo json_encode(["success" => true, "message" => "Timesheet saved successfully"]);
exit;
