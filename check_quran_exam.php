<?php
header('Content-Type: application/json');
$studentId = $_GET['student_id'] ?? null;
$year = $_GET['year'] ?? null;

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error || !$studentId || !$year) {
    echo json_encode(["exists" => false]);
    exit;
}

// Get school year
$schoolYearQuery = $conn->prepare("SELECT school_year FROM students WHERE id = ?");
$schoolYearQuery->bind_param("i", $studentId);
$schoolYearQuery->execute();
$schoolYearQuery->bind_result($schoolYear);
$schoolYearQuery->fetch();
$schoolYearQuery->close();

// Archive check
$archiveCheck = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$archiveCheck->bind_param("s", $schoolYear);
$archiveCheck->execute();
$archiveCheck->store_result();
$isArchived = $archiveCheck->num_rows > 0;
$archiveCheck->close();

// Check if exam exists
$stmt = $conn->prepare("SELECT id FROM quran_exam_marks WHERE student_id = ? AND YEAR(exam_date) = ?");
$stmt->bind_param("ii", $studentId, $year);
$stmt->execute();
$stmt->store_result();

echo json_encode([
    "exists" => $stmt->num_rows > 0,
    "isArchived" => $isArchived
]);
