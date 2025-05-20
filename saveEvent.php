<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

$title = $conn->real_escape_string($data['title']);
$date = $conn->real_escape_string($data['date']);

$sql = "INSERT INTO calendar_events (title, date) VALUES ('$title', '$date')";
echo json_encode(["success" => $conn->query($sql)]);
