<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

$result = $conn->query("SELECT reminder FROM weekly_reminders ORDER BY created_at DESC LIMIT 10");

$reminders = [];
while ($row = $result->fetch_assoc()) {
  $reminders[] = $row['reminder'];
}

echo json_encode(["reminders" => $reminders]);
?>
