<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$teacherId = $data['teacherId'] ?? null;
$studentIds = $data['studentIds'] ?? [];
$unassignedIds = $data['unassignedIds'] ?? [];

if (!$teacherId) {
    echo json_encode(["success" => false, "message" => "Missing teacher ID"]);
    exit;
}

// Determine current school year
$now = new DateTime();
$yearStart = $now->format('Y');
$schoolYear = ($now->format('n') >= 8) 
    ? $yearStart . '/' . ($yearStart + 1)
    : ($yearStart - 1) . '/' . $yearStart;

// Optional: log school year if needed in future
// You could store assignments with school_year if your table supports it

// Assign selected students to the teacher
if (!empty($studentIds)) {
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $types = str_repeat('i', count($studentIds)) . 'i';

    $stmt = $conn->prepare("UPDATE students SET assigned_teacher_id = ? WHERE id IN ($placeholders)");
    $params = array_merge([$teacherId], $studentIds);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
}

// Unassign previously assigned students if unchecked
if (!empty($unassignedIds)) {
    $placeholders = implode(',', array_fill(0, count($unassignedIds), '?'));
    $types = str_repeat('i', count($unassignedIds));

    $stmt = $conn->prepare("UPDATE students SET assigned_teacher_id = NULL WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$unassignedIds);
    $stmt->execute();
}

echo json_encode(["success" => true, "school_year" => $schoolYear]);
$conn->close();
?>
