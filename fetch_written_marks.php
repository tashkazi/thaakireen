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
$currentYear = date('Y');

if (!$studentId) {
    echo json_encode(["error" => "Missing student ID"]);
    exit;
}

// Fetch written exam marks
$writtenQuery = "
    SELECT aqaaid_score, akhlaaq_adab_score, taareekh_score 
    FROM written_exam_marks 
    WHERE student_id = ? AND school_year = ?
";
$writtenStmt = $conn->prepare($writtenQuery);
$writtenStmt->bind_param("is", $studentId, $currentYear);
$writtenStmt->execute();
$writtenResult = $writtenStmt->get_result();
$written = $writtenResult->fetch_assoc();

// Fetch oral exam marks
$oralQuery = "
    SELECT quran_score, ahadeeth_score, surah_score, dua_score 
    FROM oral_exam_marks 
    WHERE student_id = ? AND school_year = ?
";
$oralStmt = $conn->prepare($oralQuery);
$oralStmt->bind_param("is", $studentId, $currentYear);
$oralStmt->execute();
$oralResult = $oralStmt->get_result();
$oral = $oralResult->fetch_assoc();

// Combine results
$response = [
    "aqaaid_score"       => isset($written['aqaaid_score']) ? round($written['aqaaid_score']) : null,
    "akhlaaq_adab_score" => isset($written['akhlaaq_adab_score']) ? round($written['akhlaaq_adab_score']) : null,
    "taareekh_score"     => isset($written['taareekh_score']) ? round($written['taareekh_score']) : null,
    "quran_score"        => isset($oral['quran_score']) ? round($oral['quran_score']) : null,
    "ahadeeth_score"     => isset($oral['ahadeeth_score']) ? round($oral['ahadeeth_score']) : null,
    "surah_score"        => isset($oral['surah_score']) ? round($oral['surah_score']) : null,
    "dua_score"          => isset($oral['dua_score']) ? round($oral['dua_score']) : null
];

echo json_encode($response);
