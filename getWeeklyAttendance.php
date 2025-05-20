<?php
header('Content-Type: application/json');

if (!isset($_GET['teacher_id'], $_GET['week_start'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$teacherId = intval($_GET['teacher_id']);
$weekStart = $_GET['week_start'];
$weekEnd = date('Y-m-d', strtotime($weekStart . ' +4 days'));

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$stmt = $conn->prepare("
    SELECT student_id, date, status
    FROM weekly_attendance
    WHERE teacher_id = ? AND date BETWEEN ? AND ?
");

$stmt->bind_param("iss", $teacherId, $weekStart, $weekEnd);
$stmt->execute();
$result = $stmt->get_result();

$attendance = [];
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}

echo json_encode(['success' => true, 'data' => $attendance]);
