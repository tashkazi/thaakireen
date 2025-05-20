<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "Database connection failed"]);
  exit;
}

if (!empty($data['deleteAll'])) {
  $conn->query("DELETE FROM weekly_reminders");
  echo json_encode(["success" => true]);
  exit;
}

if (empty($data['reminder'])) {
  echo json_encode(["success" => false, "message" => "Reminder text missing"]);
  exit;
}

$stmt = $conn->prepare("DELETE FROM weekly_reminders WHERE reminder_text = ?");
$stmt->bind_param("s", $data['reminder']);
$stmt->execute();

echo json_encode(["success" => true]);
?>
