<?php
header('Content-Type: application/json');
session_start();

$host = "localhost";
$username = "root";
$password = "Tashreeka94_";
$database = "thaakireen";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Inputs
$studentId = $_POST['student_id'] ?? null;
$aqaaid = $_POST['aqaaid_score'] ?? null;
$akhlaaq = $_POST['akhlaaq_adab_score'] ?? null;
$ahadeeth = $_POST['ahadeeth_score'] ?? null;
$taareekh = $_POST['taareekh_score'] ?? null;
$quran = $_POST['quran_score'] ?? null;

$examDate = date("Y-m-d");
$month = (int)date("n");

// 🗓️ Block August submissions
if ($month === 8) {
    echo json_encode(["success" => false, "message" => "Written exams are locked during August. Please wait until September."]);
    exit;
}

// 📘 Determine current school year
$schoolYear = ($month >= 9)
    ? date('Y') . '/' . (date('Y') + 1)
    : (date('Y') - 1) . '/' . date('Y');

if (!$studentId) {
    echo json_encode(["success" => false, "message" => "Missing student ID"]);
    exit;
}

// 🔒 Archive check
$check = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$check->bind_param("s", $schoolYear);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "This school year is archived. Exam cannot be submitted."]);
    $check->close();
    exit;
}
$check->close();

// 🔁 Check if record exists
$checkQuery = "SELECT id FROM written_exam_marks WHERE student_id = ? AND school_year = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("is", $studentId, $schoolYear);
$stmt->execute();
$stmt->store_result();

$isUpdate = false;

if ($stmt->num_rows > 0) {
    $stmt->bind_result($existingId);
    $stmt->fetch();
    $stmt->close();

    $update = "
        UPDATE written_exam_marks
        SET aqaaid_score = ?, akhlaaq_adab_score = ?, ahadeeth_score = ?, taareekh_score = ?,
            quran_score = ?, exam_date = ?
        WHERE id = ?
    ";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("ddddssi", $aqaaid, $akhlaaq, $ahadeeth, $taareekh, $quran, $examDate, $existingId);
    $isUpdate = true;
} else {
    $insert = "
        INSERT INTO written_exam_marks 
        (student_id, exam_date, school_year, aqaaid_score, akhlaaq_adab_score, ahadeeth_score, taareekh_score, quran_score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($insert);
    $stmt->bind_param("issddddd", $studentId, $examDate, $schoolYear, $aqaaid, $akhlaaq, $ahadeeth, $taareekh, $quran);
}

if ($stmt->execute()) {
    // ✅ Audit Logging
    $user_name = $_SESSION['userName'] ?? 'Unknown User';
    $role = $_SESSION['role'] ?? 'Unknown Role';
    $action = $isUpdate
        ? "Updated written exam for student ID $studentId for $schoolYear"
        : "Submitted written exam for student ID $studentId for $schoolYear";

    $log = $conn->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
    $log->bind_param("sss", $user_name, $role, $action);
    $log->execute();
    $log->close();

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to save"]);
}

$stmt->close();
$conn->close();
