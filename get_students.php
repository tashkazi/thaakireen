<?php
header('Content-Type: application/json');

// Optional: check authentication
if (!isset($_GET['teacher_id']) || !is_numeric($_GET['teacher_id']) || !isset($_GET['school_year'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$teacherId = intval($_GET['teacher_id']);
$schoolYear = $_GET['school_year'];

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, CONCAT(firstName, ' ', lastName) AS name
    FROM students
    WHERE assigned_teacher_id = ? AND school_year = ?
    ORDER BY firstName ASC
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    exit;
}

$stmt->bind_param("is", $teacherId, $schoolYear);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode(['success' => true, 'students' => $students]);
