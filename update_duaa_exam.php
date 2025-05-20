<?php
header('Content-Type: application/json');
session_start(); // Required for audit logging session access

$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$data = json_decode(file_get_contents("php://input"), true);

$student_id   = (int)$data["student_id"];
$exam_date    = $data["exam_date"];
$grade        = $data["grade"];
$teacher_id   = (int)$data["teacher_id"];
$examiner_id  = (int)$data["examiner_id"];
$final_score  = (float)$data["final_score"];
$bonus        = isset($data["bonus"]) ? (float)$data["bonus"] : 0;
$deductions   = isset($data["deductions"]) ? (float)$data["deductions"] : 0;
$duas         = $data["duas"] ?? [];

// 🗓️ Block updates in August
$month = (int)date('n');
if ($month === 8) {
    echo json_encode([
        "success" => false,
        "error" => "Exam updates are locked during August. Please wait until the new school year begins in September."
    ]);
    exit;
}

// 📘 Determine current school year
if ($month >= 9) {
    $school_year = date('Y') . '/' . (date('Y') + 1);
} else {
    $school_year = (date('Y') - 1) . '/' . date('Y');
}

// ✅ Archive check
$check = $pdo->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$check->execute([$school_year]);
if ($check->fetchColumn()) {
    echo json_encode([
        "success" => false,
        "error" => "This school year is archived. Exam cannot be updated."
    ]);
    exit;
}

// ✅ Step 1: Get the existing exam_id for this student + year
$stmt = $pdo->prepare("SELECT id FROM duaa_exam_summary WHERE student_id = ? AND YEAR(exam_date) = YEAR(?)");
$stmt->execute([$student_id, $exam_date]);
$exam_id = $stmt->fetchColumn();

if (!$exam_id) {
    echo json_encode(["success" => false, "error" => "No existing exam found to update."]);
    exit;
}

// ✅ Step 2: Delete old details
$pdo->prepare("DELETE FROM duaa_exam_details WHERE exam_id = ?")->execute([$exam_id]);

// ✅ Step 3: Update exam summary
$stmt = $pdo->prepare("UPDATE duaa_exam_summary SET 
    teacher_id = ?, 
    examiner_id = ?, 
    grade = ?, 
    exam_date = ?, 
    final_score = ?, 
    bonus = ?, 
    deductions = ?
    WHERE id = ?");
$stmt->execute([
    $teacher_id,
    $examiner_id,
    $grade,
    $exam_date,
    $final_score,
    $bonus,
    $deductions,
    $exam_id
]);

// ✅ Step 4: Re-insert exam details
$stmtDetail = $pdo->prepare("INSERT INTO duaa_exam_details 
    (exam_id, duaa_id, duaa_title, arabic_max, translation_max, arabic_correct, translation_correct, not_reached)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($duas as $d) {
    $duaa_id            = (int)($d["duaa_id"] ?? 0);
    $title              = isset($d["title"]) && $d["title"] !== '' ? $d["title"] : 'Untitled';
    $arabic_max         = (int)($d["arabic_max"] ?? 0);
    $translation_max    = (int)($d["translation_max"] ?? 0);
    $arabic_correct     = (int)($d["arabic_correct"] ?? 0);
    $translation_correct= (int)($d["translation_correct"] ?? 0);
    $not_reached        = isset($d["not_reached"]) && $d["not_reached"] ? 1 : 0;

    $stmtDetail->execute([
        $exam_id,
        $duaa_id,
        $title,
        $arabic_max,
        $translation_max,
        $arabic_correct,
        $translation_correct,
        $not_reached
    ]);
}

// ✅ Fetch student name for audit log
$studentName = "Unknown Student";
$stmt = $pdo->prepare("SELECT CONCAT(firstName, ' ', lastName) AS fullName FROM students WHERE id = ?");
$stmt->execute([$student_id]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $studentName = $row['fullName'];
}

// ✅ Audit Logging
$user_name = $_SESSION['userName'] ?? 'Unknown User';
$role = $_SESSION['role'] ?? 'Unknown Role';
$action = "Updated Duaa exam for student $studentName for $school_year";

$log = $pdo->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
$log->execute([$user_name, $role, $action]);

// ✅ Final Response
echo json_encode([
    "success" => true,
    "message" => "Duaa exam updated successfully.",
    "exam_id" => $exam_id,
    "final_score" => $final_score,
    "bonus" => $bonus,
    "deductions" => $deductions
]);
