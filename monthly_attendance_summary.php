<?php
session_start();
header('Content-Type: application/json');

if (!($_SESSION['isAdmin'] ?? false)) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$teacherId = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
$monthYear = $_GET['month'] ?? ''; // Format: YYYY-MM

if (!$teacherId || !$monthYear) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        s.id AS student_id,
        CONCAT(s.firstName, ' ', s.lastName) AS student_name,
        SUM(CASE WHEN wa.status = 'Present' THEN 1 ELSE 0 END) AS total_present,
        SUM(CASE WHEN wa.status = 'Absent' THEN 1 ELSE 0 END) AS total_absent,
        SUM(CASE WHEN wa.status = 'Late' THEN 1 ELSE 0 END) AS total_late,
        SUM(CASE WHEN wa.status = 'Excused' THEN 1 ELSE 0 END) AS total_excused
    FROM weekly_attendance wa
    JOIN students s ON wa.student_id = s.id
    WHERE wa.teacher_id = ? AND DATE_FORMAT(wa.date, '%Y-%m') = ?
    GROUP BY s.id
    ORDER BY s.firstName ASC
");

$stmt->bind_param("is", $teacherId, $monthYear);
$stmt->execute();
$result = $stmt->get_result();

$summary = [];
while ($row = $result->fetch_assoc()) {
    $summary[] = $row;
}

echo json_encode(['success' => true, 'data' => $summary]);
