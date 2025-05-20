<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing data.']);
    exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Use the correct summer camp table
$stmt = $conn->prepare("
    INSERT INTO summer_camp_attendance (student_id, teacher_id, date, status)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE status = VALUES(status)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    exit;
}

foreach ($data as $entry) {
    $student_id = intval($entry['student_id']);
    $teacher_id = intval($entry['teacher_id']);
    $date       = $entry['date'];
    $status     = $entry['status'];

    // Only valid enum values
    if (!in_array($status, ['Present', 'Absent', 'Late', 'Excused'])) continue;

    if (!$student_id || !$teacher_id || !$date) continue;

    $stmt->bind_param("iiss", $student_id, $teacher_id, $date, $status);
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Summer camp attendance saved successfully.']);
