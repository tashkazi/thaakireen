<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

$data = [
    "totalTeachers" => 0,
    "totalVolunteers" => 0,
    "totalStaff" => 0,
    "studentsPerTeacher" => [],
    "teacherStudentRatio" => "0:0"
];

// Total approved teachers
$result = $conn->query("SELECT COUNT(*) AS count FROM users WHERE isTeacher = 1 AND isApproved = 1");
$data["totalTeachers"] = ($row = $result->fetch_assoc()) ? (int)$row["count"] : 0;

// Total approved volunteers
$result = $conn->query("SELECT COUNT(*) AS count FROM users WHERE isVolunteer = 1 AND isApproved = 1");
$data["totalVolunteers"] = ($row = $result->fetch_assoc()) ? (int)$row["count"] : 0;

// Total staff with any leadership role
$result = $conn->query("
  SELECT COUNT(*) AS count FROM users 
  WHERE isApproved = 1 AND (isTeacher = 1 OR isAdmin = 1 OR isPrincipal = 1 OR isCoordinator = 1 OR isSupervisor = 1 OR isVolunteer = 1)
");
$data["totalStaff"] = ($row = $result->fetch_assoc()) ? (int)$row["count"] : 0;

// Students per teacher
$result = $conn->query("
  SELECT CONCAT(t.first_name, ' ', t.last_name) AS teacher_name, COUNT(s.id) AS student_count
  FROM teachers t
  LEFT JOIN students s ON s.assigned_teacher_id = t.teacher_id
  GROUP BY t.teacher_id
  ORDER BY student_count DESC
");

$totalStudents = 0;
while ($row = $result->fetch_assoc()) {
    $data["studentsPerTeacher"][] = [
        "name" => $row["teacher_name"],
        "count" => (int)$row["student_count"]
    ];
    $totalStudents += (int)$row["student_count"];
}

// Ratio
$teacherCount = max($data["totalTeachers"], 1); // prevent /0
$data["teacherStudentRatio"] = "$totalStudents:$teacherCount";

echo json_encode($data);
?>
