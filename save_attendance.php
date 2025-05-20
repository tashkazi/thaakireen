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

$stmt = $conn->prepare("
    INSERT INTO weekly_attendance (student_id, teacher_id, date, status, recorded_by)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE status = VALUES(status), recorded_by = VALUES(recorded_by)
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
    $recorded_by = $_SESSION['user_id'] ?? null;

    if (!$student_id || !$teacher_id || !$date || !$status || !$recorded_by) continue;

    $stmt->bind_param("iissi", $student_id, $teacher_id, $date, $status, $recorded_by);
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Attendance saved successfully.']);
