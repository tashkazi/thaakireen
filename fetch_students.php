<?php
header('Content-Type: application/json');

// Connect to DB
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$teacherId = $_GET['teacher_id'] ?? null;
$isFriday = isset($_GET['friday']) && $_GET['friday'] == 1;

$sql = "
    SELECT 
        s.id,
        s.firstName,
        s.lastName,
        s.gender,
        s.date_of_birth,
        s.address,
        s.school_grade,
        s.book_grade,
        s.quran_level,
        s.parent1_name,
        s.parent1_phone,
        s.parent1_email,
        s.parent2_name,
        s.parent2_phone,
        s.medical_conditions,
        s.allergies,
        s.status,
        s.approved,
        s.assigned_teacher_id,
        s.friday_teacher_id,
        CONCAT(wt.first_name, ' ', wt.last_name) AS teacherName,
        CONCAT(ft.first_name, ' ', ft.last_name) AS fridayTeacherName
    FROM students s
    LEFT JOIN teachers wt ON s.assigned_teacher_id = wt.teacher_id
    LEFT JOIN teachers ft ON s.friday_teacher_id = ft.teacher_id
";

// Optional filtering by teacher
if (!empty($teacherId)) {
    if ($isFriday) {
        $sql .= " WHERE s.friday_teacher_id = ?";
    } else {
        $sql .= " WHERE s.assigned_teacher_id = ?";
    }
}

$sql .= " ORDER BY s.firstName, s.lastName";

$stmt = $conn->prepare($sql);
if (!empty($teacherId)) {
    $stmt->bind_param("i", $teacherId);
}
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);
?>
