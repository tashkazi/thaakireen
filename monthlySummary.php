<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug: catch whitespace/encoding problems
if (headers_sent()) {
    echo json_encode(['success' => false, 'message' => 'Headers already sent.']);
    exit;
}

// Debug: is session set?
if (!isset($_SESSION['isAdmin']) || !isset($_SESSION['teacher_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session missing. You may be logged out.',
        'session' => $_SESSION // include current state
    ]);
    exit;
}

$teacherId = $_GET['teacher_id'] ?? null;
$month = $_GET['month'] ?? null;
$isAdmin = $_SESSION['isAdmin'];
$sessionTeacherId = $_SESSION['teacher_id'];

// Validate access
if (!$isAdmin && (int)$teacherId !== (int)$sessionTeacherId) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if (!$teacherId || !$month) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$stmt = $conn->prepare("
    SELECT
        s.id AS student_id,
        CONCAT(s.firstName, ' ', s.lastName) AS student_name,
        SUM(wa.status = 'Present') AS total_present,
        SUM(wa.status = 'Absent') AS total_absent,
        SUM(wa.status = 'Late') AS total_late,
        SUM(wa.status = 'Excused') AS total_excused
    FROM weekly_attendance wa
    JOIN students s ON s.id = wa.student_id
    WHERE wa.teacher_id = ? AND DATE_FORMAT(wa.date, '%Y-%m') = ?
    GROUP BY s.id
    ORDER BY s.firstName ASC
");


if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("is", $teacherId, $month);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Query execution failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Result fetch failed.']);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(['success' => true, 'data' => $data]);
exit;
