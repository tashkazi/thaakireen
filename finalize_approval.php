<?php
ob_start(); // prevent accidental whitespace output
header('Content-Type: application/json');
ini_set('display_errors', 0); // turn off visible errors
ini_set('log_errors', 1);     // log errors instead
error_reporting(E_ALL);

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(["success" => false, "message" => "Missing ID"]);
    exit;
}

// Step 1: Fetch registration record
$select = $conn->prepare("SELECT * FROM registration_requests WHERE id = ?");
$select->bind_param("i", $id);
$select->execute();
$result = $select->get_result();
$registration = $result->fetch_assoc();

if (!$registration) {
    echo json_encode(["success" => false, "message" => "Registration not found"]);
    exit;
}

// Step 2: Insert into students table
$insert = $conn->prepare("
    INSERT INTO students (
      firstName, lastName, school_grade, gender, date_of_birth, address,
      parent1_name, parent1_phone, parent1_email,
      medical_conditions, allergies, school_year, approved, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'Active')
");

$insert->bind_param(
    "ssssssssssss",
    $registration['firstName'],
    $registration['lastName'],
    $registration['school_grade'],
    $registration['gender'],
    $registration['date_of_birth'],
    $registration['address'],
    $registration['parent1_name'],
    $registration['parent1_phone'],
    $registration['parent1_email'],
    $registration['medical_conditions'],
    $registration['allergies'],
    $registration['school_year']
);

if (!$insert->execute()) {
    echo json_encode(["success" => false, "message" => "Insert failed: " . $insert->error]);
    exit;
}

// Step 3: Update registration_requests
$update = $conn->prepare("UPDATE registration_requests SET status = 'Approved', approved = 1 WHERE id = ?");
$update->bind_param("i", $id);
$update->execute();

echo json_encode(["success" => true]);
?>
