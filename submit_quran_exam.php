<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 🔗 Connect to DB
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed", "error" => $conn->connect_error]);
    exit;
}

// 📨 Get POST data
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['surahs']) || !is_array($data['surahs'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

// 🔁 Mode: submit or update
$mode = $data['mode'] ?? 'submit';

$student_id = (int) ($data['student_id'] ?? 0);
$teacher_id = (int) ($data['teacher_id'] ?? 0);
$examiner_id = (int) ($data['examiner_id'] ?? 0);
$exam_date = $data['exam_date'] ?? '';
$grade = $data['grade'] ?? '';
$bonus = (float) ($data['bonus'] ?? 0);
$deductions = (float) ($data['deductions'] ?? 0);
$final_score = (float) ($data['final_score'] ?? 0);

// 🗓️ Determine school year
$month = (int)date('n');
if ($month === 8) {
    echo json_encode(["success" => false, "message" => "Quran exams are locked during August. Please wait until September."]);
    exit;
}
$school_year = ($month >= 9) ? date('Y') . '/' . (date('Y') + 1) : (date('Y') - 1) . '/' . date('Y');

// 🔒 Archive year check
$archiveCheck = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$archiveCheck->bind_param("s", $school_year);
$archiveCheck->execute();
$archiveCheck->store_result();
if ($archiveCheck->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "This school year is archived. Exam cannot be modified."]);
    $archiveCheck->close();
    exit;
}
$archiveCheck->close();

// ✅ Validate required inputs
if (!$student_id || !$teacher_id || !$examiner_id || !$exam_date || empty($school_year)) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// 🗑 If update, delete old records first
if ($mode === 'update') {
    $delete = $conn->prepare("DELETE FROM quran_exam_marks WHERE student_id = ? AND school_year = ?");
    $delete->bind_param("is", $student_id, $school_year);
    $delete->execute();
    $delete->close();
}

// ✅ Prepare INSERT
$stmt = $conn->prepare("
    INSERT INTO quran_exam_marks (
        student_id, teacher_id, examiner_id,
        surah_name, total_ayahs, ayahs_forgotten, stuck_errors, tajweed_errors,
        memorization_score, tajweed_score, fluency_score, weighted_score,
        exam_date, grade, bonus, deductions, final_score,
        not_reached, skipped, school_year
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Insert prepare failed", "error" => $conn->error]);
    exit;
}

// Loop through surahs
foreach ($data['surahs'] as $surah) {
    $surah_name = $surah['surah_name'] ?? '';
    $total_ayahs = (int) ($surah['total_ayahs'] ?? 0);
    $ayahs_forgotten = (int) ($surah['ayahs_forgotten'] ?? 0);
    $stuck_errors = (int) ($surah['stuck_errors'] ?? 0);
    $tajweed_errors = (int) ($surah['tajweed_errors'] ?? 0);
    $memorization_score = (float) ($surah['memorization_score'] ?? 0);
    $tajweed_score = (float) ($surah['tajweed_score'] ?? 0);
    $fluency_score = (float) ($surah['fluency_score'] ?? 0);
    $weighted_score = (float) ($surah['weighted_score'] ?? 0);
    $not_reached = (int) ($surah['not_reached'] ?? 0);
    $skipped = (int) ($surah['skipped'] ?? 0);

    $stmt->bind_param(
        "iiisiiiidddssdddiiss",
        $student_id,
        $teacher_id,
        $examiner_id,
        $surah_name,
        $total_ayahs,
        $ayahs_forgotten,
        $stuck_errors,
        $tajweed_errors,
        $memorization_score,
        $tajweed_score,
        $fluency_score,
        $weighted_score,
        $exam_date,
        $grade,
        $bonus,
        $deductions,
        $final_score,
        $not_reached,
        $skipped,
        $school_year
    );

    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Insert failed", "error" => $stmt->error]);
        $stmt->close();
        $conn->close();
        exit;
    }
}

// ✅ Done
$stmt->close();

// ✅ Audit Logging
$logStmt = $conn->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
if ($logStmt) {
    $user_name = $_SESSION['userName'] ?? 'Unknown User';
    $role = $_SESSION['role'] ?? 'Unknown Role';
    $action = ($mode === 'update')
        ? "Updated Quran exam for student ID $student_id for $school_year"
        : "Submitted new Quran exam for student ID $student_id for $school_year";

    $logStmt->bind_param("sss", $user_name, $role, $action);
    $logStmt->execute();
    $logStmt->close();
}

$conn->close();

echo json_encode(["success" => true, "message" => "Exam saved", "final_score" => $final_score]);
