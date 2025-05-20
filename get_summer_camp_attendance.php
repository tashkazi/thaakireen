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
    "totalCampTeachers" => 0,
    "totalCampStudents" => 0,
    "attendanceRecords" => 0,
    "studentsPerTeacher" => [],
    "teacherStudentRatio" => "0:0"
];

// Total summer camp teachers (from summer_camp_teachers table)
$result = $conn->query("SELECT COUNT(*) AS count FROM summer_camp_teachers");
$data["totalCampTeachers"] = ($row = $result->fetch_assoc()) ? (int)$row["count"] : 0;

// Total summer camp students
$result = $conn->query("SELECT COUNT(*) AS count FROM summer_camp_students");
$data["totalCampStudents"] = ($row = $result->fetch_assoc()) ? (int)$row["count"] : 0;

// Total attendance records
$result = $conn->query("SELECT COUNT(*) AS count FROM summer_camp_attendance");
$data["attendanceRecords"] = ($row = $result->fetch_assoc()) ? (int)$row["count"] : 0;

// Students per camp teacher
$result = $conn->query("
    SELECT CONCAT(sct.first_name, ' ', sct.last_name) AS teacher_name, COUNT(scs.id) AS student_count
    FROM summer_camp_teachers sct
    LEFT JOIN summer_camp_students scs ON scs.assigned_teacher_id = sct.user_id
    GROUP BY sct.user_id
    ORDER BY student_count DESC
");

$totalStudents = 0;
while ($row = $result->fetch_assoc()) {
    $count = (int)$row["student_count"];
    $data["studentsPerTeacher"][] = [
        "name" => $row["teacher_name"],
        "count" => $count
    ];
    $totalStudents += $count;
}

// Ratio
$teacherCount = max($data["totalCampTeachers"], 1); // prevent /0
$data["teacherStudentRatio"] = "$totalStudents:$teacherCount";

echo json_encode(["success" => true] + $data);
?>
