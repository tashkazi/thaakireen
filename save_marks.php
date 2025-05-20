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

// ==== Input Data ====
$studentId       = isset($_POST['student_id']) ? (int)$_POST['student_id'] : null;
$teacherId       = isset($_POST['teacher_id']) && $_POST['teacher_id'] !== '' ? (int)$_POST['teacher_id'] : null;
$examinerId      = isset($_POST['examiner_id']) && $_POST['examiner_id'] !== '' ? (int)$_POST['examiner_id'] : null;
$grade           = $_POST['grade'] ?? null;

// Written
$aqaaid          = isset($_POST['aqaaid_score']) ? round((float)$_POST['aqaaid_score']) : null;
$akhlaaq         = isset($_POST['akhlaaq_adab_score']) ? round((float)$_POST['akhlaaq_adab_score']) : null;
$taareekh        = isset($_POST['taareekh_score']) ? round((float)$_POST['taareekh_score']) : null;

// Oral
$quranRecitation = isset($_POST['quran_recitation_score']) ? round((float)$_POST['quran_recitation_score'], 2) : null;
$ahadeeth        = isset($_POST['ahadeeth_score']) ? round((float)$_POST['ahadeeth_score'], 2) : null;

$examDate = date("Y-m-d");
$month = (int)date("n");

// 🛑 August block
if ($month === 8) {
    echo json_encode(["success" => false, "message" => "Exam marking is locked during August. Please wait until September."]);
    exit;
}

// 📘 Determine school year
$schoolYear = ($month >= 9)
    ? date('Y') . '/' . (date('Y') + 1)
    : (date('Y') - 1) . '/' . date('Y');

if (!$studentId) {
    echo json_encode(["success" => false, "message" => "Missing student ID"]);
    exit;
}

// 🔒 Archive year check
$archivedCheck = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$archivedCheck->bind_param("s", $schoolYear);
$archivedCheck->execute();
$archivedCheck->store_result();
if ($archivedCheck->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "This school year is archived. Marks cannot be changed."]);
    $archivedCheck->close();
    exit;
}
$archivedCheck->close();

$isUpdateWritten = false;
$isUpdateOral = false;

// ==== WRITTEN EXAM MARKS ====
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
        SET aqaaid_score = ?, akhlaaq_adab_score = ?, taareekh_score = ?, exam_date = ?, 
            teacher_id = ?, examiner_id = ?, grade = ?
        WHERE id = ?
    ");
    $stmt->bind_param("dddssisi", $aqaaid, $akhlaaq, $taareekh, $examDate, $teacherId, $examinerId, $grade, $writtenId);
    $isUpdateWritten = true;
} else {
    $stmt->close();
    $stmt = $conn->prepare("
        INSERT INTO written_exam_marks
        (student_id, teacher_id, examiner_id, grade, exam_date, school_year, aqaaid_score, akhlaaq_adab_score, taareekh_score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisssddd", $studentId, $teacherId, $examinerId, $grade, $examDate, $schoolYear, $aqaaid, $akhlaaq, $taareekh);
}
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to save written marks", "error" => $stmt->error]);
    exit;
}
$stmt->close();

// ==== FINAL ORAL SCORE ====
$oralParts = [$quranRecitation, $ahadeeth];
$validParts = array_filter($oralParts, fn($val) => $val !== null);
$finalOralScore = count($validParts) > 0 ? round(array_sum($validParts) / count($validParts), 2) : null;

// ==== ORAL EXAM MARKS ====
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
        SET quran_recitation_score = ?, ahadeeth_score = ?, final_oral_score = ?, 
            exam_date = ?, teacher_id = ?, examiner_id = ?, grade = ?
        WHERE id = ?
    ");
    $stmt->bind_param("dddssssi", 
        $quranRecitation, $ahadeeth, $finalOralScore,
        $examDate, $teacherId, $examinerId, $grade, $oralId
    );
    $isUpdateOral = true;
} else {
    $stmt->close();
    $stmt = $conn->prepare("
        INSERT INTO oral_exam_marks 
        (student_id, teacher_id, examiner_id, exam_date, school_year, grade, 
         quran_recitation_score, ahadeeth_score, final_oral_score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisssddd", 
        $studentId, $teacherId, $examinerId, $examDate, $schoolYear, $grade,
        $quranRecitation, $ahadeeth, $finalOralScore
    );
}
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to save oral marks", "error" => $stmt->error]);
    exit;
}
$stmt->close();

// ✅ Audit Logging
$user_name = $_SESSION['userName'] ?? 'Unknown User';
$role = $_SESSION['role'] ?? 'Unknown Role';

if ($isUpdateWritten || $isUpdateOral) {
    $action = "Updated exam marks for student ID $studentId for $schoolYear";
} else {
    $action = "Submitted new exam marks for student ID $studentId for $schoolYear";
}

$log = $conn->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
$log->bind_param("sss", $user_name, $role, $action);
$log->execute();
$log->close();

echo json_encode(["success" => true]);
$conn->close();
