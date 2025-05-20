<?php
session_start();
header('Content-Type: application/json');

$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$year = $_GET['year'] ?? '';
$teacherId = $_GET['teacher_id'] ?? '';
$studentName = $_GET['student_name'] ?? '';

if (!$year) {
    echo json_encode(["error" => "Missing school year"]);
    exit;
}

// Check if year is archived
$check = $pdo->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$check->execute([$year]);
if (!$check->fetch()) {
    echo json_encode(["error" => "Year not archived"]);
    exit;
}

// Fetch student info
$query = "
SELECT s.id AS student_id, s.firstName, s.lastName, s.school_year, 
       CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
FROM students s
JOIN teachers t ON s.assigned_teacher_id = t.teacher_id
WHERE s.school_year = ?
";

$params = [$year];

if ($teacherId) {
    $query .= " AND t.teacher_id = ?";
    $params[] = $teacherId;
}

if ($studentName) {
    $query .= " AND CONCAT(s.firstName, ' ', s.lastName) LIKE ?";
    $params[] = "%$studentName%";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$records = [];

foreach ($students as $s) {
    $studentId = $s['student_id'];

    // Quran
    $q = $pdo->prepare("SELECT final_score FROM quran_exam_marks WHERE student_id = ? AND school_year = ?");
    $q->execute([$studentId, $year]);
    $quran = $q->fetchColumn() ?? '-';

    // Duaa
    $d = $pdo->prepare("SELECT final_score FROM duaa_exam_summary WHERE student_id = ? AND school_year = ?");
    $d->execute([$studentId, $year]);
    $duaa = $d->fetchColumn() ?? '-';

    // Written
    $w = $pdo->prepare("SELECT final_written_score FROM written_exam_marks WHERE student_id = ? AND school_year = ?");
    $w->execute([$studentId, $year]);
    $written = $w->fetchColumn() ?? '-';

    // Calculate final avg if all numeric
    $final = '-';
    if (is_numeric($quran) && is_numeric($duaa) && is_numeric($written)) {
        $final = round(($quran + $duaa + $written) / 3, 2);
    }

    $records[] = [
        "student" => $s['firstName'] . ' ' . $s['lastName'],
        "year" => $year,
        "teacher" => $s['teacher_name'],
        "quran" => $quran,
        "duaa" => $duaa,
        "written" => $written,
        "final" => $final
    ];
}

echo json_encode(["records" => $records]);
