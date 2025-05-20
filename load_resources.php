<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

$result = $conn->query("SELECT id, title, file_path, file_type FROM teacher_resources ORDER BY created_at DESC");
$resources = [];

while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}

echo json_encode(["resources" => $resources]);
