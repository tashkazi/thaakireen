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

$studentId = $_GET['id'] ?? null;

if (!$studentId) {
    http_response_code(400);
    echo json_encode(["error" => "Missing student ID"]);
    exit;
}

$query = "
  SELECT 
    id, firstName, lastName, school_grade, book_grade, assigned_teacher_id,
    gender, date_of_birth, address, quran_level,
    parent1_name, parent1_phone, parent1_email,
    parent2_name, parent2_phone,
    medical_conditions, allergies, notes, status
  FROM students
  WHERE id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($student = $result->fetch_assoc()) {
    echo json_encode($student);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Student not found"]);
}
?>
