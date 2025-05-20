<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed"]);
    exit;
}

// ============ Students ============
$students = [];
$studentQuery = "
    SELECT 
        students.id, 
        students.firstName, 
        students.lastName,
        students.assigned_teacher_id AS teacher_id,
        students.friday_teacher_id,
        students.school_grade,
        students.book_grade,
        students.gender,
        students.expected_dua_grade,
        students.last_memorized_dua_id,
        students.last_memorized_surah_id,
        surahs.name AS last_memorized_surah_name,
        CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
        CONCAT(ft.first_name, ' ', ft.last_name) AS friday_teacher_name
    FROM students
    LEFT JOIN surahs ON students.last_memorized_surah_id = surahs.id
    LEFT JOIN teachers t ON students.assigned_teacher_id = t.teacher_id
    LEFT JOIN teachers ft ON students.friday_teacher_id = ft.teacher_id
    ORDER BY students.firstName
";
$result = $conn->query($studentQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            "id" => $row["id"],
            "firstName" => $row["firstName"],
            "lastName" => $row["lastName"],
            "teacher_id" => $row["teacher_id"],
            "friday_teacher_id" => $row["friday_teacher_id"],
            "teacherName" => $row["teacher_name"] ?? "",
            "fridayTeacherName" => $row["friday_teacher_name"] ?? "",
            "school_grade" => $row["school_grade"],
            "book_grade" => $row["book_grade"],
            "gender" => $row["gender"],
            "expected_dua_grade" => $row["expected_dua_grade"],
            "last_memorized_dua_id" => $row["last_memorized_dua_id"],
            "last_memorized_surah_id" => $row["last_memorized_surah_id"],
            "last_memorized_surah_name" => $row["last_memorized_surah_name"]
        ];
    }
}

// ============ Teachers ============
$teachers = [];
$teacherQuery = "
    SELECT 
        t.teacher_id, 
        t.first_name, 
        t.last_name, 
        t.book_grade_assigned,
        t.gender,
        u.title
    FROM teachers t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.first_name
";
$result = $conn->query($teacherQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = [
            "id" => $row["teacher_id"],
            "first_name" => $row["first_name"],
            "last_name" => $row["last_name"],
            "book_grade_assigned" => $row["book_grade_assigned"],
            "gender" => $row["gender"],
            "title" => $row["title"] ?? "",
            "name" => trim(($row["title"] ?? '') . " " . $row["first_name"] . " " . $row["last_name"])
        ];
    }
}

// ============ Summer Camp Teachers ============
$summerCampTeachers = [];
$result = $conn->query("SELECT id, first_name, last_name FROM summer_camp_teachers ORDER BY first_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $summerCampTeachers[] = [
            "id" => $row["id"],
            "name" => $row["first_name"] . " " . $row["last_name"]
        ];
    }
}

// ============ Examiners ============
$examiners = [];
$result = $conn->query("SELECT examiner_id, first_name, last_name FROM examiners ORDER BY first_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $examiners[] = [
            "id" => $row["examiner_id"],
            "name" => $row["first_name"] . " " . $row["last_name"]
        ];
    }
}

// ============ Surahs ============
$surahs = [];
$result = $conn->query("SELECT id, name FROM surahs ORDER BY surah_number DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $surahs[] = [
            "id" => $row["id"],
            "name" => $row["name"]
        ];
    }
}

// ============ Duas ============
$duas = [];
$result = $conn->query("SELECT id, dua_name FROM duas ORDER BY grade");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $duas[] = [
            "id" => $row["id"],
            "name" => $row["dua_name"]
        ];
    }
}

// ============ Grades ============
$grades = ["Kindergarten"];
for ($i = 1; $i <= 12; $i++) {
    $grades[] = "Grade $i";
}

// ============ Final Output ============
echo json_encode([
    "students" => $students,
    "teachers" => $teachers,
    "summerCampTeachers" => $summerCampTeachers,
    "examiners" => $examiners,
    "surahs" => $surahs,
    "duas" => $duas,
    "grades" => $grades
]);

$conn->close();
?>
