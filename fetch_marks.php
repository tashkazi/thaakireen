<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if (!$studentId) {
    echo json_encode(["success" => false, "message" => "Missing student ID"]);
    exit;
}

// --- Determine school year ---
$month = (int)date('n');
$schoolYear = ($month >= 9)
    ? date('Y') . '/' . (date('Y') + 1)
    : (date('Y') - 1) . '/' . date('Y');

// 1. Written exam
$writtenStmt = $conn->prepare("SELECT * FROM written_exam_marks WHERE student_id = ? AND school_year = ?");
$writtenStmt->bind_param("is", $studentId, $schoolYear);
$writtenStmt->execute();
$written = $writtenStmt->get_result()->fetch_assoc();
$writtenStmt->close();

// 2. Oral exam (recitation + hadith)
$oralStmt = $conn->prepare("SELECT * FROM oral_exam_marks WHERE student_id = ? AND school_year = ?");
$oralStmt->bind_param("is", $studentId, $schoolYear);
$oralStmt->execute();
$oral = $oralStmt->get_result()->fetch_assoc();
$oralStmt->close();

// 3. Duaa summary
$duaaStmt = $conn->prepare("SELECT final_score FROM duaa_exam_summary WHERE student_id = ? AND school_year = ?");
$duaaStmt->bind_param("is", $studentId, $schoolYear);
$duaaStmt->execute();
$duaa = $duaaStmt->get_result()->fetch_assoc();
$duaaStmt->close();

// 4. Latest Quran memorization score (with date)
$quranStmt = $conn->prepare("
    SELECT final_score, exam_date 
    FROM quran_exam_marks 
    WHERE student_id = ? AND school_year = ? 
    ORDER BY exam_date DESC 
    LIMIT 1
");
$quranStmt->bind_param("is", $studentId, $schoolYear);
$quranStmt->execute();
$quran = $quranStmt->get_result()->fetch_assoc();
$quranStmt->close();

// Build response
$response = [
    "teacher_id" => $written['teacher_id'] ?? $oral['teacher_id'] ?? null,
    "examiner_id" => $written['examiner_id'] ?? $oral['examiner_id'] ?? null,
    "grade" => $written['grade'] ?? $oral['grade'] ?? null,

    "fiqh_score" => $written['fiqh_score'] ?? null,
    "aqaaid_score" => $written['aqaaid_score'] ?? null,
    "akhlaaq_adab_score" => $written['akhlaaq_adab_score'] ?? null,
    "taareekh_score" => $written['taareekh_score'] ?? null,

    "quran_recitation_score" => $oral['quran_recitation_score'] ?? null,
    "surah_memorization_score" => $quran['final_score'] ?? null,
    "quran_exam_date" => $quran['exam_date'] ?? null,

    "dua_score" => $duaa['final_score'] ?? null,
    "ahadeeth_score" => $oral['ahadeeth_score'] ?? null
];

echo json_encode($response);
$conn->close();
