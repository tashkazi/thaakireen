<?php
header("Content-Type: application/json");
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "Connection failed"]);
  exit;
}

$id = $_POST['id'] ?? null;
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$school_grade = $_POST['school_grade'] ?? '';
$age = $_POST['age'] ?? null;
$notes = $_POST['notes'] ?? '';
$teacher_id = $_POST['assigned_teacher_id'] ?? null;

if ($id) {
  // ✅ Update existing student
  $stmt = $conn->prepare("UPDATE summer_camp_students SET first_name=?, last_name=?, school_grade=?, age=?, notes=?, assigned_teacher_id=? WHERE id=?");
  $stmt->bind_param("sssissi", $first_name, $last_name, $school_grade, $age, $notes, $teacher_id, $id);
} else {
  // ✅ Insert new student
  $stmt = $conn->prepare("INSERT INTO summer_camp_students (first_name, last_name, school_grade, age, notes, assigned_teacher_id) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("sssisi", $first_name, $last_name, $school_grade, $age, $notes, $teacher_id);
}

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => "DB Error"]);
}

$stmt->close();
$conn->close();
?>
