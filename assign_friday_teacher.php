<?php
header('Content-Type: application/json');

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
$bookGradeKey = $data['bookGrade'] ?? null;
$teacherId = $data['teacherId'] ?? null;

if (!$bookGradeKey || !$teacherId) {
    echo json_encode(["success" => false, "message" => "Missing book grade or teacher ID"]);
    exit;
}

$parts = explode('-', $bookGradeKey);
$bookGrade = trim($parts[0]);
$genderRaw = isset($parts[1]) ? strtolower(trim($parts[1])) : null;

if ($genderRaw === 'female' || $genderRaw === 'girls' || $genderRaw === 'girl') {
    $gender = 'Female';
} elseif ($genderRaw === 'male' || $genderRaw === 'boys' || $genderRaw === 'boy') {
    $gender = 'Male';
} else {
    echo json_encode(["success" => false, "message" => "Invalid gender format"]);
    exit;
}

// ✅ Update all students in this group
$studentStmt = $conn->prepare("
    UPDATE students 
    SET friday_teacher_id = ? 
    WHERE book_grade = ? AND gender = ?
");
$studentStmt->bind_param("iss", $teacherId, $bookGrade, $gender);
$studentSuccess = $studentStmt->execute();
$studentStmt->close();

// ✅ Overwrite the teacher’s assigned group info
$teacherStmt = $conn->prepare("
    UPDATE teachers 
    SET book_grade_assigned = ?, gender = ?
    WHERE teacher_id = ?
");
$teacherStmt->bind_param("ssi", $bookGrade, $gender, $teacherId);
$teacherSuccess = $teacherStmt->execute();
$teacherStmt->close();

echo json_encode([
    "success" => $studentSuccess && $teacherSuccess,
    "updatedGroup" => "$bookGrade - $gender"
]);
