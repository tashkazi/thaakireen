<?php
$host = "localhost";
$username = "root";
$password = "Tashreeka94_";
$database = "thaakireen";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

header('Content-Type: application/json');

$studentId = $_GET['student_id'] ?? null;
$year = date('Y');

if (!$studentId) {
    echo json_encode(["error" => "Missing student ID"]);
    exit;
}

// Get latest duaa exam score for this year
$duaaQuery = "
    SELECT final_score 
    FROM duaa_exam_summary 
    WHERE student_id = ? AND YEAR(exam_date) = ? 
    ORDER BY exam_date DESC 
    LIMIT 1
";
$duaaStmt = $conn->prepare($duaaQuery);
$duaaStmt->bind_param("ii", $studentId, $year);
$duaaStmt->execute();
$duaaResult = $duaaStmt->get_result();
$duaaScore = $duaaResult->fetch_assoc()['final_score'] ?? null;

// Get latest Quran memorization score for this year
$quranQuery = "
    SELECT memorization_score 
    FROM quran_exam_marks 
    WHERE student_id = ? AND YEAR(exam_date) = ? AND not_reached = 0
    ORDER BY exam_date DESC 
    LIMIT 1
";
$quranStmt = $conn->prepare($quranQuery);
$quranStmt->bind_param("ii", $studentId, $year);
$quranStmt->execute();
$quranResult = $quranStmt->get_result();
$surahScore = $quranResult->fetch_assoc()['memorization_score'] ?? null;

// Output clean integers
echo json_encode([
    "dua_score" => $duaaScore !== null ? intval($duaaScore) : '',
    "surah_score" => $surahScore !== null ? intval($surahScore) : ''
]);
?>
