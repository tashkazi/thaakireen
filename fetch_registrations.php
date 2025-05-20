<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$sql = "SELECT id, first_name, last_name, age, school_grade, contact_name, phone, medical FROM summer_camp_students ORDER BY age, last_name";
$result = $conn->query($sql);

$registrations = [];
while ($row = $result->fetch_assoc()) {
    $registrations[] = $row;
}

echo json_encode($registrations);
$conn->close();
