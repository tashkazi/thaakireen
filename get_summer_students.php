<?php
header('Content-Type: application/json');

$teacherId = $_GET['teacher_id'] ?? null;
$weekStart = $_GET['week_start'] ?? null;

if (!$teacherId || !$weekStart) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// 1. Fetch students assigned to this summer camp teacher
$students = [];
$stmt = $conn->prepare("
    SELECT id, CONCAT(first_name, ' ', last_name) AS name
    FROM summer_camp_students
    WHERE assigned_teacher_id = ?
    ORDER BY first_name ASC
");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// 2. Fetch attendance for the week
$endDate = date('Y-m-d', strtotime("$weekStart +4 days"));
$attendance = [];

$stmt2 = $conn->prepare("
    SELECT student_id, date, status
    FROM summer_camp_attendance
    WHERE teacher_id = ? AND date BETWEEN ? AND ?
");
$stmt2->bind_param("iss", $teacherId, $weekStart, $endDate);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $attendance[] = $row;
}

echo json_encode([
    'success' => true,
    'students' => $students,
    'attendance' => $attendance
]);
