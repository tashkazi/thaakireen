<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $id = $_GET['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        throw new Exception("Missing or invalid student ID.");
    }

    // Step 1: Get registration record
    $select = $conn->prepare("SELECT * FROM registration_requests WHERE id = ?");
    $select->bind_param("i", $id);
    $select->execute();
    $result = $select->get_result();
    $registration = $result->fetch_assoc();

    if (!$registration) {
        throw new Exception("Registration not found.");
    }

    // Sanitize nullable fields
    $date_of_birth = !empty($registration['date_of_birth']) ? $registration['date_of_birth'] : null;
    $school_year = $registration['school_year'] ?? "2024/2025"; // default if missing

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
        $date_of_birth,
        $registration['address'],
        $registration['parent1_name'],
        $registration['parent1_phone'],
        $registration['parent1_email'],
        $registration['medical_conditions'],
        $registration['allergies'],
        $school_year
    );

    if (!$insert->execute()) {
        throw new Exception("Insert into students failed: " . $insert->error);
    }

    $studentId = $conn->insert_id;

    // Step 3: If parent exists, link to student
    $parentEmail = $registration['parent1_email'];
    $parent_id = null;

    $parentQuery = $conn->prepare("SELECT id FROM users WHERE email = ? AND isParent = 1");
    $parentQuery->bind_param("s", $parentEmail);
    $parentQuery->execute();
    $parentResult = $parentQuery->get_result();
    $parent = $parentResult->fetch_assoc();

    if ($parent) {
        $parent_id = $parent['id'];
        $link = $conn->prepare("INSERT IGNORE INTO parent_students (parent_id, student_id) VALUES (?, ?)");
        $link->bind_param("ii", $parent_id, $studentId);
        $link->execute();
    }

    // Step 4: Update registration_requests
    $update = $conn->prepare("UPDATE registration_requests SET status = 'Approved', approved = 1 WHERE id = ?");
    $update->bind_param("i", $id);
    $update->execute();

    echo json_encode([
        "success" => true,
        "student_id" => $studentId,
        "parent_linked" => $parent_id !== null
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
