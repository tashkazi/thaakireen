<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$studentId = $_GET['student_id'] ?? null;
$currentYear = date('Y');

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error || !$studentId) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed or student ID missing."]);
    exit;
}

// ------------------ Fetch Student + Teacher Name ------------------
$infoQuery = "
    SELECT s.firstName, s.lastName, t.first_name AS teacherFirst, t.last_name AS teacherLast, s.school_year
    FROM students s
    JOIN teachers t ON s.assigned_teacher_id = t.teacher_id
    WHERE s.id = ?
";
$stmt = $conn->prepare($infoQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$infoResult = $stmt->get_result();
$info = $infoResult->fetch_assoc();
$stmt->close();
if (!$info) {
    echo json_encode(["error" => "Student not found"]);
    exit;
}

$schoolYear = $info['school_year'];

// Archive check
$archived = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$archived->bind_param("s", $schoolYear);
$archived->execute();
$archived->store_result();
$isArchived = $archived->num_rows > 0;
$archived->close();

// ------------------ Fetch Written Exam ------------------
$writtenQuery = "
    SELECT aqaaid_score, akhlaaq_adab_score, taareekh_score
    FROM written_exam_marks 
    WHERE student_id = ? AND school_year = ?
    ORDER BY exam_date DESC LIMIT 1
";
$writtenStmt = $conn->prepare($writtenQuery);
$writtenStmt->bind_param("is", $studentId, $schoolYear);
$writtenStmt->execute();
$writtenResult = $writtenStmt->get_result()->fetch_assoc() ?? [];
$writtenStmt->close();

// ------------------ Fetch Oral Exam ------------------
$oralQuery = "
    SELECT quran_recitation_score, ahadeeth_score
    FROM oral_exam_marks 
    WHERE student_id = ? AND school_year = ?
    ORDER BY exam_date DESC LIMIT 1
";
$oralStmt = $conn->prepare($oralQuery);
$oralStmt->bind_param("is", $studentId, $schoolYear);
$oralStmt->execute();
$oralResult = $oralStmt->get_result()->fetch_assoc() ?? [];
$oralStmt->close();

// ------------------ Fetch Surah Memorization Final Score ------------------
$quranQuery = "
    SELECT final_score AS surah_memorization_score
    FROM quran_exam_marks 
    WHERE student_id = ? AND YEAR(exam_date) = ?
    ORDER BY exam_date DESC LIMIT 1
";
$quranStmt = $conn->prepare($quranQuery);
$quranStmt->bind_param("ii", $studentId, $currentYear);
$quranStmt->execute();
$quranResult = $quranStmt->get_result();
$quran = $quranResult->fetch_assoc();
$quranStmt->close();

// ------------------ Fetch Duaa Score ------------------
$duaaQuery = "
    SELECT final_score AS dua_score
    FROM duaa_exam_summary 
    WHERE student_id = ? AND YEAR(exam_date) = ?
";
$duaaStmt = $conn->prepare($duaaQuery);
$duaaStmt->bind_param("ii", $studentId, $currentYear);
$duaaStmt->execute();
$duaaResult = $duaaStmt->get_result();
$duaa = $duaaResult->fetch_assoc();
$duaaStmt->close();

// ------------------ Build Unified Response ------------------
$response = [
    "studentName" => $info['firstName'] . ' ' . $info['lastName'],
    "teacherName" => $info['teacherFirst'] . ' ' . $info['teacherLast'],
    "isArchived" => $isArchived,
    "aqaaid_score"               => isset($writtenResult['aqaaid_score']) ? round($writtenResult['aqaaid_score']) : null,
    "akhlaaq_adab_score"         => isset($writtenResult['akhlaaq_adab_score']) ? round($writtenResult['akhlaaq_adab_score']) : null,
    "taareekh_score"             => isset($writtenResult['taareekh_score']) ? round($writtenResult['taareekh_score']) : null,
    "quran_recitation_score"     => isset($oralResult['quran_recitation_score']) ? round($oralResult['quran_recitation_score']) : null,
    "ahadeeth_score"             => isset($oralResult['ahadeeth_score']) ? round($oralResult['ahadeeth_score']) : null,
    "surah_memorization_score"   => isset($quran['surah_memorization_score']) ? round($quran['surah_memorization_score']) : null,
    "dua_score"                  => isset($duaa['dua_score']) ? round($duaa['dua_score']) : null
];

echo json_encode($response);
