<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

$data = json_decode(file_get_contents("php://input"), true);
$text = trim($data["reminder"] ?? "");

if ($text === "") {
  echo json_encode(["success" => false, "message" => "Empty reminder"]);
  exit;
}

$stmt = $conn->prepare("INSERT INTO weekly_reminders (reminder) VALUES (?)");
$stmt->bind_param("s", $text);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Reminder added"]);
?>
