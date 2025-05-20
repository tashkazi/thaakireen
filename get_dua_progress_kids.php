<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$year = date("Y");

// 1. Get total students per teacher who have expected_dua_grade
$studentsSql = "
    SELECT 
        t.teacher_id,
        CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
        COUNT(s.id) AS total_students
    FROM students s
    JOIN teachers t ON s.assigned_teacher_id = t.teacher_id
    WHERE s.expected_dua_grade IS NOT NULL
    GROUP BY t.teacher_id
";

$studentsRes = $conn->query($studentsSql);
$teacherStats = [];

while ($row = $studentsRes->fetch_assoc()) {
    $teacherStats[$row['teacher_name']] = [
        "total" => (int)$row['total_students'],
        "completed" => 0,
        "above85" => 0,
        "between60and84" => 0,
        "below60" => 0
    ];
}

// 2. Fetch dua exam scores for current year
$examSql = "
    SELECT 
        d.student_id,
        d.final_score,
        CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
    FROM duaa_exam_summary d
    JOIN students s ON d.student_id = s.id
    JOIN teachers t ON d.teacher_id = t.teacher_id
    WHERE d.school_year = CONCAT(YEAR(CURDATE()), '/', YEAR(CURDATE()) + 1)
      AND d.is_archived = 0
";

$examRes = $conn->query($examSql);

while ($row = $examRes->fetch_assoc()) {
    $teacher = $row['teacher_name'];
    $score = (float)$row['final_score'];

    if (!isset($teacherStats[$teacher])) continue;

    $teacherStats[$teacher]["completed"]++;

    if ($score >= 85) {
        $teacherStats[$teacher]["above85"]++;
    } elseif ($score >= 60) {
        $teacherStats[$teacher]["between60and84"]++;
    } else {
        $teacherStats[$teacher]["below60"]++;
    }
}

echo json_encode([
    "success" => true,
    "progressByTeacher" => $teacherStats
]);

$conn->close();
