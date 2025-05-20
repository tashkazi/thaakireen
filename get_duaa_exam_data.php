<?php
$host = "localhost";
$username = "root";
$password = "Tashreeka94_";
$database = "thaakireen";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

header('Content-Type: application/json');

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;

if (!$studentId || !$year) {
    echo json_encode(["error" => "Missing student_id or year"]);
    exit;
}

// Fetch student fallback info
$studentQuery = $conn->prepare("
    SELECT school_grade, assigned_teacher_id, last_memorized_dua_id
    FROM students
    WHERE id = ?
");
$studentQuery->bind_param("i", $studentId);
$studentQuery->execute();
$studentQuery->bind_result($schoolGrade, $assignedTeacherId, $lastMemorizedDuaId);
$studentQuery->fetch();
$studentQuery->close();

$studentInfo = [
    "school_grade" => $schoolGrade,
    "assigned_teacher_id" => $assignedTeacherId,
    "last_memorized_dua_id" => $lastMemorizedDuaId
];

// Fetch summary
$query = "
    SELECT *
    FROM duaa_exam_summary
    WHERE student_id = ? AND YEAR(exam_date) = ?
    LIMIT 1
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $studentId, $year);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "exists" => false,
        "student" => $studentInfo
    ]);
    exit;
}

$exam = $result->fetch_assoc();
$stmt->close();

// Check if year is archived
$archiveCheck = $conn->prepare("
    SELECT 1 FROM archived_years
    WHERE school_year = (SELECT school_year FROM students WHERE id = ?)
");
$archiveCheck->bind_param("i", $studentId);
$archiveCheck->execute();
$archiveCheck->store_result();
$isArchived = $archiveCheck->num_rows > 0;
$archiveCheck->close();

// Fetch details + dua grade
$examId = $exam["id"];
$details = $conn->prepare("
    SELECT 
        e.duaa_id,
        e.arabic_correct,
        e.translation_correct,
        e.not_reached,
        e.skip_arabic,
        e.skip_translation,
        d.dua_name AS duaa_title,
        d.arabic_marks AS arabic_max,
        d.translation_marks AS translation_max,
        d.grade AS grade
    FROM duaa_exam_details e
    JOIN duas d ON d.id = e.duaa_id
    WHERE e.exam_id = ?
");
$details->bind_param("i", $examId);
$details->execute();
$duasResult = $details->get_result();

$duas = [];
while ($row = $duasResult->fetch_assoc()) {
    $arabicMax = (int)$row['arabic_max'];
    $translationMax = (int)$row['translation_max'];
    $arabicCorrect = (int)$row['arabic_correct'];
    $translationCorrect = (int)$row['translation_correct'];
    $score = 0;

    if (in_array($exam['grade'], ['Kindergarten', 'Grade 1', 'Grade 2'])) {
        $score = $arabicMax > 0 ? ($arabicCorrect / $arabicMax) * 100 : 0;
    } else {
        if ($arabicMax > 0) $score += ($arabicCorrect / $arabicMax) * 85;
        if ($translationMax > 0) $score += ($translationCorrect / $translationMax) * 15;
    }

    $row['score'] = round($score, 2);
    $duas[] = $row;
}

$details->close();
$conn->close();

echo json_encode([
    "exists" => true,
    "exam" => [
        "exam_date" => $exam["exam_date"],
        "teacher_id" => $exam["teacher_id"],
        "examiner_id" => $exam["examiner_id"],
        "grade" => $exam["grade"],
        "final_score" => $exam["final_score"],
        "bonus" => $exam["bonus"],
        "deductions" => $exam["deductions"],
        "duas" => $duas,
        "isArchived" => $isArchived
    ],
    "student" => $studentInfo
]);
?>
