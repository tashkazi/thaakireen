<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Inputs
$studentId = $_POST['student_id'] ?? null;
$aqaaid    = $_POST['aqaaid_score'] ?? null;
$akhlaaq   = $_POST['akhlaaq_adab_score'] ?? null;
$taareekh  = $_POST['taareekh_score'] ?? null;
$ahadeeth  = $_POST['ahadeeth_score'] ?? null;
$quran     = $_POST['quran_score'] ?? null;
$surah     = $_POST['surah_score'] ?? null;
$dua       = $_POST['dua_score'] ?? null;

$examDate = date("Y-m-d");
$month = (int)date("n");

// 🛑 Block August
if ($month === 8) {
    echo json_encode(["success" => false, "message" => "Report card entry is locked during August. Wait until September."]);
    exit;
}

// 🧠 Determine school year
$schoolYear = ($month >= 9)
    ? date('Y') . '/' . (date('Y') + 1)
    : (date('Y') - 1) . '/' . date('Y');

if (!$studentId) {
    echo json_encode(["success" => false, "message" => "Missing student ID"]);
    exit;
}

// 🔒 Archive year check
$check = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$check->bind_param("s", $schoolYear);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "This school year is archived. No changes allowed."]);
    $check->close();
    exit;
}
$check->close();

$isUpdateWritten = false;
$isUpdateOral = false;

// ---------- WRITTEN MARKS ----------
$stmt = $conn->prepare("SELECT id FROM written_exam_marks WHERE student_id = ? AND school_year = ?");
$stmt->bind_param("is", $studentId, $schoolYear);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($writtenId);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE written_exam_marks
        SET aqaaid_score = ?, akhlaaq_adab_score = ?, taareekh_score = ?, exam_date = ?
        WHERE id = ?
    ");
    $stmt->bind_param("dddsi", $aqaaid, $akhlaaq, $taareekh, $examDate, $writtenId);
    $isUpdateWritten = true;
} else {
    $stmt->close();
    $stmt = $conn->prepare("
        INSERT INTO written_exam_marks 
        (student_id, exam_date, school_year, aqaaid_score, akhlaaq_adab_score, taareekh_score)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issddd", $studentId, $examDate, $schoolYear, $aqaaid, $akhlaaq, $taareekh);
}
$writtenSaved = $stmt->execute();
$stmt->close();

// ---------- ORAL MARKS ----------
$stmt = $conn->prepare("SELECT id FROM oral_exam_marks WHERE student_id = ? AND school_year = ?");
$stmt->bind_param("is", $studentId, $schoolYear);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($oralId);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE oral_exam_marks
        SET ahadeeth_score = ?, quran_score = ?, surah_score = ?, dua_score = ?, exam_date = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ddddsi", $ahadeeth, $quran, $surah, $dua, $examDate, $oralId);
    $isUpdateOral = true;
} else {
    $stmt->close();
    $stmt = $conn->prepare("
        INSERT INTO oral_exam_marks 
        (student_id, exam_date, school_year, ahadeeth_score, quran_score, surah_score, dua_score)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdddd", $studentId, $examDate, $schoolYear, $ahadeeth, $quran, $surah, $dua);
}
$oralSaved = $stmt->execute();
$stmt->close();

// ---------- AUDIT LOG ----------
$user_name = $_SESSION['userName'] ?? 'Unknown User';
$role = $_SESSION['role'] ?? 'Unknown Role';

$action = ($isUpdateWritten || $isUpdateOral)
    ? "Updated report card marks for student ID $studentId for $schoolYear"
    : "Submitted report card marks for student ID $studentId for $schoolYear";

$log = $conn->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
$log->bind_param("sss", $user_name, $role, $action);
$log->execute();
$log->close();

// ---------- Final Response ----------
if ($writtenSaved && $oralSaved) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to save one or more records"]);
}

$conn->close();
