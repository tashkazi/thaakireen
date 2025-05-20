<?php
session_start();
header('Content-Type: application/json');

$teacherId = $_GET['teacher_id'] ?? null;
$month = $_GET['month'] ?? null; // Format: YYYY-MM

// Role-based protection
$isAdmin = $_SESSION['isAdmin'] ?? false;
$sessionTeacherId = $_SESSION['teacher_id'] ?? null;

// Block access if neither admin nor matching teacher
if (!$isAdmin && (int)$teacherId !== (int)$sessionTeacherId) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if (!$teacherId || !$month) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt = $conn->prepare("
    SELECT
        s.id AS student_id,
        CONCAT(s.firstName, ' ', s.lastName) AS student_name,
        SUM(status = 'Present') AS total_present,
        SUM(status = 'Absent') AS total_absent,
        SUM(status = 'Late') AS total_late,
        SUM(status = 'Excused') AS total_excused
    FROM weekly_attendance wa
    JOIN students s ON s.id = wa.student_id
    WHERE wa.teacher_id = ? AND DATE_FORMAT(wa.date, '%Y-%m') = ?
    GROUP BY s.id
    ORDER BY s.firstName ASC
");

$stmt->bind_param("is", $teacherId, $month);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(['success' => true, 'data' => $data]);
