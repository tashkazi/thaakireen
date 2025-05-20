<?php
header('Content-Type: application/json');
session_start(); // Needed to access $_SESSION for audit log

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    echo json_encode(["success" => false, "error" => "Invalid input."]);
    exit;
}

$student_id   = (int)$input['student_id'];
$teacher_id   = (int)$input['teacher_id'];
$examiner_id  = (int)$input['examiner_id'];
$exam_date    = $input['exam_date'];
$grade        = $input['grade'];
$final_score  = (float)$input['final_score'];
$bonus        = isset($input['bonus']) ? (float)$input['bonus'] : 0;
$deductions   = isset($input['deductions']) ? (float)$input['deductions'] : 0;
$duas         = $input['duas'] ?? [];

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed."]);
    exit;
}

// 🗓️ Determine current school year
$month = (int)date('n');
if ($month === 8) {
    echo json_encode(["success" => false, "error" => "Exam submissions are locked during August. Please wait until September."]);
    exit;
}
$school_year = ($month >= 9) ? date('Y') . '/' . (date('Y') + 1) : (date('Y') - 1) . '/' . date('Y');

// 🔒 Archive lock check
$archived = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$archived->bind_param("s", $school_year);
$archived->execute();
$archived->store_result();
if ($archived->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "This school year is archived. Exam cannot be submitted."]);
    exit;
}
$archived->close();

// ❌ Prevent duplicate exam in same year
$year = date('Y', strtotime($exam_date));
$checkQuery = $conn->prepare("SELECT id FROM duaa_exam_summary WHERE student_id = ? AND YEAR(exam_date) = ?");
$checkQuery->bind_param("ii", $student_id, $year);
$checkQuery->execute();
$checkQuery->store_result();
if ($checkQuery->num_rows > 0) {
    echo json_encode(["success" => false, "error" => "Exam already exists for this student in $year."]);
    exit;
}
$checkQuery->close();

// ✅ Insert into summary table
$insertSummary = $conn->prepare("
    INSERT INTO duaa_exam_summary 
    (student_id, teacher_id, examiner_id, exam_date, grade, final_score, bonus, deductions, school_year) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$insertSummary->bind_param(
    "iiissddds",
    $student_id,
    $teacher_id,
    $examiner_id,
    $exam_date,
    $grade,
    $final_score,
    $bonus,
    $deductions,
    $school_year
);
$insertSummary->execute();

if ($insertSummary->error) {
    echo json_encode(["success" => false, "error" => "Summary insert error: " . $insertSummary->error]);
    exit;
}

$exam_id = $insertSummary->insert_id;
$insertSummary->close();

// ✅ Insert individual dua marks
$insertDetail = $conn->prepare("
    INSERT INTO duaa_exam_details 
    (exam_id, duaa_id, duaa_title, arabic_max, translation_max, arabic_correct, translation_correct, not_reached) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($duas as $duaa) {
    $duaa_id             = (int)($duaa['duaa_id'] ?? 0);
    $title               = trim($duaa['title'] ?? 'Untitled');
    $arabic_max          = (int)($duaa['arabic_max'] ?? 0);
    $translation_max     = (int)($duaa['translation_max'] ?? 0);
    $arabic_correct      = (int)($duaa['arabic_correct'] ?? 0);
    $translation_correct = (int)($duaa['translation_correct'] ?? 0);
    $not_reached         = isset($duaa['not_reached']) && $duaa['not_reached'] ? 1 : 0;

    $insertDetail->bind_param(
        "iisiiiii",
        $exam_id,
        $duaa_id,
        $title,
        $arabic_max,
        $translation_max,
        $arabic_correct,
        $translation_correct,
        $not_reached
    );

    $insertDetail->execute();

    if ($insertDetail->error) {
        echo json_encode(["success" => false, "error" => "Detail insert error: " . $insertDetail->error]);
        exit;
    }
}
$insertDetail->close();

// ✅ Audit Logging
$user_name = $_SESSION['userName'] ?? 'Unknown User';
$role = $_SESSION['role'] ?? 'Unknown Role';
$action = "Submitted Duaa exam for student ID $student_id for $school_year";

$logStmt = $conn->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
if ($logStmt) {
    $logStmt->bind_param("sss", $user_name, $role, $action);
    $logStmt->execute();
    $logStmt->close();
}

$conn->close();

echo json_encode([
    "success" => true,
    "message" => "Duaa exam submitted successfully.",
    "exam_id" => $exam_id,
    "final_score" => $final_score,
    "bonus" => $bonus,
    "deductions" => $deductions
]);
