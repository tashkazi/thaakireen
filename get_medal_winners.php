<?php
// Connect to the database
$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Get school year from query
$year = $_GET['year'] ?? '2024/2025';

// Prepare query to calculate total score
$sql = "
SELECT 
  s.id AS student_id,
  CONCAT(s.firstName, ' ', s.lastName) AS student_name,
  s.school_grade,
  COALESCE(SUM(q.final_score), 0) AS quran_score,
  COALESCE(SUM(d.final_score), 0) AS duaa_score,
  COALESCE(SUM(o.final_oral_score), 0) AS oral_score,
  COALESCE(SUM(w.final_written_score), 0) AS written_score,
  ROUND(
    COALESCE(SUM(q.final_score), 0) + 
    COALESCE(SUM(d.final_score), 0) + 
    COALESCE(SUM(o.final_oral_score), 0) + 
    COALESCE(SUM(w.final_written_score), 0), 
    2
  ) AS total_score
FROM students s
LEFT JOIN quran_exam_marks q ON s.id = q.student_id AND q.school_year = :year
LEFT JOIN duaa_exam_summary d ON s.id = d.student_id AND d.school_year = :year
LEFT JOIN oral_exam_marks o ON s.id = o.student_id AND o.school_year = :year
LEFT JOIN written_exam_marks w ON s.id = w.student_id AND w.school_year = :year
GROUP BY s.id, s.school_grade
ORDER BY s.school_grade ASC, total_score DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['year' => $year]);

// Group results by grade
$studentsByGrade = [];
while ($row = $stmt->fetch()) {
    $grade = $row['school_grade'];
    if (!isset($studentsByGrade[$grade])) {
        $studentsByGrade[$grade] = [];
    }
    $studentsByGrade[$grade][] = $row;
}

// Extract top 3 per grade
$topWinners = [];
foreach ($studentsByGrade as $grade => $students) {
    $topWinners[$grade] = array_slice($students, 0, 3); // top 3
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($topWinners);
