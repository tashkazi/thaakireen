<?php
header("Content-Type: application/json");
$conn = new mysqli("localhost", "root", "YOUR_PASSWORD", "thaakireen");

$data = json_decode(file_get_contents("php://input"), true);
$studentId = $data["studentId"];
$teacherId = $data["teacherId"];

$stmt = $conn->prepare("UPDATE students SET assigned_teacher_id = ? WHERE id = ?");
$stmt->bind_param("ii", $teacherId, $studentId);
$success = $stmt->execute();

echo json_encode(["success" => $success]);
