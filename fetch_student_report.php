<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$studentId = $_GET['student_id'] ?? null;

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error || !$studentId) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed or student ID missing."]);
    exit;
}

// 🔄 Determine current school year (e.g., "2024/2025")
$month = (int)date("n");
$year = (int)date("Y");
$schoolYear = ($month >= 9)
    ? $year . '/' . ($year + 1)
    : ($year - 1) . '/' . $year;

// 1. Get student + teacher name
$infoQuery = "
    SELECT s.firstName, s.lastName, t.first_name AS teacherFirst, t.last_name AS teacherLast
    FROM students s
    JOIN teachers t ON s.assigned_teacher_id = t.teacher_id
    WHERE s.id = ?
";
$stmt = $conn->prepare($infoQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$infoResult = $stmt->get_result();
$info = $infoResult->fetch_assoc();
if (!$info) {
    echo json_encode(["error" => "Student not found"]);
    exit;
}

// 2. Get final_score from quran_exam_marks (memorization score)
$quranMemorizationQuery = "
    SELECT final_score
    FROM quran_exam_marks 
    WHERE student_id = ? AND YEAR(exam_date) = ?
    ORDER BY exam_date DESC LIMIT 1
";
$stmt = $conn->prepare($quranMemorizationQuery);
$stmt->bind_param("ii", $studentId, $year);
$stmt->execute();
$quranResult = $stmt->get_result()->fetch_assoc() ?? [];
$quranMemorization = isset($quranResult['final_score']) ? floatval($quranResult['final_score']) : null;

// 3. Get Oral Exam marks (Quran recitation & Ahadeeth)
$oralQuery = "
    SELECT quran_recitation_score, ahadeeth_score
    FROM oral_exam_marks 
    WHERE student_id = ? AND school_year = ?
    ORDER BY exam_date DESC LIMIT 1
";
$stmt = $conn->prepare($oralQuery);
$stmt->bind_param("is", $studentId, $schoolYear);
$stmt->execute();
$oralResult = $stmt->get_result()->fetch_assoc() ?? [];
$quranRecitation = isset($oralResult['quran_recitation_score']) ? floatval($oralResult['quran_recitation_score']) : null;
$ahadeethScore = isset($oralResult['ahadeeth_score']) ? floatval($oralResult['ahadeeth_score']) : null;

// 4. Get Duaa exam score
$duaaQuery = "
    SELECT final_score 
    FROM duaa_exam_summary 
    WHERE student_id = ? AND YEAR(exam_date) = ?
    ORDER BY exam_date DESC LIMIT 1
";
$stmt = $conn->prepare($duaaQuery);
$stmt->bind_param("ii", $studentId, $year);
$stmt->execute();
$duaaResult = $stmt->get_result()->fetch_assoc() ?? [];
$duaScore = isset($duaaResult['final_score']) ? floatval($duaaResult['final_score']) : null;

// 5. Get Written exam marks
$writtenQuery = "
    SELECT aqaaid_score, akhlaaq_adab_score, taareekh_score
    FROM written_exam_marks 
    WHERE student_id = ? AND school_year = ?
    ORDER BY exam_date DESC LIMIT 1
";
$stmt = $conn->prepare($writtenQuery);
$stmt->bind_param("is", $studentId, $schoolYear);
$stmt->execute();
$writtenResult = $stmt->get_result()->fetch_assoc() ?? [];

// ✅ Final response structure
$response = [
    "studentName" => $info['firstName'] . ' ' . $info['lastName'],
    "teacherName" => $info['teacherFirst'] . ' ' . $info['teacherLast'],
    "quran" => [
        "recitation_score" => $quranRecitation,
        "memorization_score" => $quranMemorization,
        "comment" => "Good fluency",
        "memorization_comment" => "Memorized well"
    ],
    "dua" => [
        "score" => $duaScore,
        "max" => 100,
        "comment" => "Completed all required duas"
    ],
    "fiqh" => [
        "score" => isset($writtenResult['aqaaid_score']) ? floatval($writtenResult['aqaaid_score']) : null,
        "comment" => ""
    ],
    "aqaaid" => [
        "score" => isset($writtenResult['aqaaid_score']) ? floatval($writtenResult['aqaaid_score']) : null,
        "comment" => ""
    ],
    "akhlaaq" => [
        "score" => isset($writtenResult['akhlaaq_adab_score']) ? floatval($writtenResult['akhlaaq_adab_score']) : null,
        "comment" => ""
    ],
    "ahadeeth" => [
        "score" => $ahadeethScore,
        "comment" => ""
    ],
    "taareekh" => [
        "score" => isset($writtenResult['taareekh_score']) ? floatval($writtenResult['taareekh_score']) : null,
        "comment" => ""
    ]
];

echo json_encode($response);
$conn->close();
