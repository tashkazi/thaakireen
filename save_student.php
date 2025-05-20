<?php
header('Content-Type: application/json');
ini_set('display_errors', 1); error_reporting(E_ALL);

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

session_start();
$userName = $_SESSION['userName'] ?? 'Unknown User';
$role = $_SESSION['role'] ?? 'Unknown Role';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

// Extract fields
$id = $data['id'] ?? null;
$firstName = $data['firstName'] ?? '';
$lastName = $data['lastName'] ?? '';
$school_grade = $data['school_grade'] ?? '';
$book_grade = $data['book_grade'] ?? '';
$assigned_teacher_id = !empty($data['assigned_teacher_id']) ? (int)$data['assigned_teacher_id'] : null;
$gender = $data['gender'] ?? '';
$date_of_birth = $data['date_of_birth'] ?? null;
$address = $data['address'] ?? '';
$quran_level = $data['quran_level'] ?? 'Beginner';
$parent1_name = $data['parent1_name'] ?? '';
$parent1_phone = $data['parent1_phone'] ?? '';
$parent1_email = $data['parent1_email'] ?? '';
$parent2_name = $data['parent2_name'] ?? '';
$parent2_phone = $data['parent2_phone'] ?? '';
$medical = $data['medical_conditions'] ?? '';
$allergies = $data['allergies'] ?? '';
$status = $data['status'] ?? 'Active';
$notes = $data['notes'] ?? '';
$readingMaterial = $data['readingMaterial'] ?? '';
$qaidaPage = !empty($data['qaidaPage']) ? (int)$data['qaidaPage'] : null;
$school_year = $data['school_year'] ?? '';
$last_memorized_surah_id = !empty($data['last_memorized_surah_id']) ? (int)$data['last_memorized_surah_id'] : null;
$last_memorized_dua_id = !empty($data['last_memorized_dua_id']) ? (int)$data['last_memorized_dua_id'] : null;

$recordId = null;

if ($id) {
    // UPDATE
    $stmt = $conn->prepare("
        UPDATE students SET
            firstName = ?, lastName = ?, school_grade = ?, book_grade = ?, assigned_teacher_id = ?,
            gender = ?, date_of_birth = ?, address = ?, quran_level = ?,
            parent1_name = ?, parent1_phone = ?, parent1_email = ?,
            parent2_name = ?, parent2_phone = ?, medical_conditions = ?, allergies = ?,
            status = ?, notes = ?, readingMaterial = ?, qaidaPage = ?, school_year = ?,
            last_memorized_surah_id = ?, last_memorized_dua_id = ?, updatedAt = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $stmt->bind_param("ssssisssssssssssssssiiii",
        $firstName, $lastName, $school_grade, $book_grade, $assigned_teacher_id,
        $gender, $date_of_birth, $address, $quran_level,
        $parent1_name, $parent1_phone, $parent1_email,
        $parent2_name, $parent2_phone, $medical, $allergies,
        $status, $notes, $readingMaterial, $qaidaPage, $school_year,
        $last_memorized_surah_id, $last_memorized_dua_id, $id
    );

    $recordId = $id;
    $success = $stmt->execute();
} else {
    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO students (
            firstName, lastName, school_grade, book_grade, assigned_teacher_id,
            gender, date_of_birth, address, quran_level,
            parent1_name, parent1_phone, parent1_email,
            parent2_name, parent2_phone, medical_conditions, allergies,
            status, notes, readingMaterial, qaidaPage, school_year,
            last_memorized_surah_id, last_memorized_dua_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("ssssissssssssssssssssii",
        $firstName, $lastName, $school_grade, $book_grade, $assigned_teacher_id,
        $gender, $date_of_birth, $address, $quran_level,
        $parent1_name, $parent1_phone, $parent1_email,
        $parent2_name, $parent2_phone, $medical, $allergies,
        $status, $notes, $readingMaterial, $qaidaPage, $school_year,
        $last_memorized_surah_id, $last_memorized_dua_id
    );

    $success = $stmt->execute();
    $recordId = $conn->insert_id;
}

// Check and log result
if (!$success) {
    echo json_encode(["success" => false, "message" => $stmt->error]);
} else {
    // ✅ Log action
    $studentFullName = trim("$firstName $lastName");

    $logAction = $id
        ? "Updated student: $studentFullName (ID: $recordId)"
        : "Created new student: $studentFullName";

    $logStmt = $conn->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
    $logStmt->bind_param("sss", $userName, $role, $logAction);
    $logStmt->execute();

    echo json_encode(["success" => true]);
}
?>
