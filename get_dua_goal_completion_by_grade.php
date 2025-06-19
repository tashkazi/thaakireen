<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Fetch goal completion stats by school grade
$sql = "
    SELECT 
        s.school_grade,
        COUNT(*) AS total_students,
        SUM(CASE 
              WHEN s.last_memorized_dua_id IS NOT NULL 
                   AND s.last_memorized_dua_id >= g.goal_dua_id THEN 1 
              ELSE 0 
            END) AS completed_goal
    FROM students s
    JOIN duaa_grade_goals g ON s.school_grade = g.school_grade
    WHERE s.status = 'Active'
    GROUP BY s.school_grade
    ORDER BY 
      FIELD(s.school_grade, 'Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12')
";

$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    $grade = $row['school_grade'];
    $data[$grade] = [
        "total" => (int)$row['total_students'],
        "completed" => (int)$row['completed_goal']
    ];
}

echo json_encode([
    "success" => true,
    "goalCompletionByGrade" => $data
]);
