<?php
header('Content-Type: application/json');

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Sanitize and validate input
$firstName     = trim($_POST['first_name'] ?? '');
$lastName      = trim($_POST['last_name'] ?? '');
$age           = intval($_POST['age'] ?? 0);
$schoolGrade   = trim($_POST['school_grade'] ?? '');
$contactName   = trim($_POST['contact_name'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$medical       = trim($_POST['medical'] ?? '');

// Basic required validation
if (!$firstName || !$lastName || !$age || !$schoolGrade || !$contactName || !$phone) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Insert into DB
$stmt = $conn->prepare("
    INSERT INTO summer_camp_students 
        (first_name, last_name, age, school_grade, contact_name, phone, medical)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ssissss", $firstName, $lastName, $age, $schoolGrade, $contactName, $phone, $medical);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Insert failed"]);
}

$stmt->close();
$conn->close();
?>
