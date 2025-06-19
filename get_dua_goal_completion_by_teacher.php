<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// Get goal dua per grade
$goalQuery = $conn->query("SELECT school_grade, goal_dua_id FROM duaa_grade_goals");
$goalMap = [];
while ($row = $goalQuery->fetch_assoc()) {
    $goalMap[$row['school_grade']] = (int)$row['goal_dua_id'];
}

// Get students with teachers and last memorized dua
$sql = "
    SELECT 
        CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
        s.school_grade,
        s.last_memorized_dua_id
    FROM students s
    JOIN teachers t ON s.assigned_teacher_id = t.teacher_id
    WHERE s.status = 'Active'
";

$result = $conn->query($sql);
$stats = [];

while ($row = $result->fetch_assoc()) {
    $teacher = $row['teacher_name'];
    $grade = $row['school_grade'];
    $lastDua = (int)$row['last_memorized_dua_id'];

    if (!isset($goalMap[$grade])) continue;

    if (!isset($stats[$teacher])) {
        $stats[$teacher] = [
            "total" => 0,
            "completed" => 0
        ];
    }

    $stats[$teacher]["total"]++;

    if ($lastDua >= $goalMap[$grade]) {
        $stats[$teacher]["completed"]++;
    }
}

echo json_encode([
    "success" => true,
    "goalCompletionByTeacher" => $stats
]);

$conn->close();
