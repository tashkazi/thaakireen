<?php
header('Content-Type: application/json');
ini_set('display_errors', 1); error_reporting(E_ALL);

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Get input data
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received"]);
    exit;
}

// Auto-determine school year based on current month
$currentMonth = (int)date('n');
$currentYear = (int)date('Y');
$school_year = ($currentMonth >= 7)
    ? "$currentYear/" . ($currentYear + 1)
    : ($currentYear - 1) . "/$currentYear";

// Clean input
$firstName       = $data['firstName'] ?? '';
$lastName        = $data['lastName'] ?? '';
$school_grade    = $data['school_grade'] ?? '';
$gender          = $data['gender'] ?? '';
$date_of_birth   = $data['date_of_birth'] ?? null;
$address         = $data['address'] ?? '';
$parent1_name    = $data['parent1_name'] ?? '';
$parent1_phone   = $data['parent1_phone'] ?? '';
$parent1_email   = $data['parent1_email'] ?? '';
$medical         = $data['medical_conditions'] ?? '';
$allergies       = $data['allergies'] ?? '';

// Check how many have already registered for this grade and year
$countStmt = $conn->prepare("
    SELECT COUNT(*) FROM registration_requests 
    WHERE school_grade = ? AND school_year = ? AND status IN ('Pending', 'Waitlisted', 'Approved')
");
$countStmt->bind_param("ss", $school_grade, $school_year);
$countStmt->execute();
$countStmt->bind_result($existingCount);
$countStmt->fetch();
$countStmt->close();

// Decide status: Pending (first 15) or Waitlisted
$status = ($existingCount >= 15) ? 'Waitlisted' : 'Pending';

// Insert into registration_requests
$stmt = $conn->prepare("
    INSERT INTO registration_requests (
      firstName, lastName, school_grade, gender, date_of_birth, address,
      parent1_name, parent1_phone, parent1_email,
      medical_conditions, allergies, school_year, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
  "sssssssssssss",
  $firstName, $lastName, $school_grade, $gender, $date_of_birth, $address,
  $parent1_name, $parent1_phone, $parent1_email,
  $medical, $allergies, $school_year, $status
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "status" => $status]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}
?>
