<?php
$host = "localhost";
$username = "root";
$password = "Tashreeka94_";
$database = "thaakireen";

// Connect to database
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

header('Content-Type: application/json');

$teacherId = $_GET['teacher_id'] ?? null;

$sql = "
    SELECT s.id, s.first_name, s.last_name, s.school_grade, s.age,
           t.id AS teacher_id, CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
    FROM summer_camp_students s
    LEFT JOIN summer_camp_teachers t ON s.assigned_teacher_id = t.id
";

if (!empty($teacherId)) {
    $sql .= " WHERE s.assigned_teacher_id = ?";
}

$sql .= " ORDER BY s.first_name, s.last_name";

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
