<?php
$host = "localhost";
$username = "root";
$password = "Tashreeka94_";
$database = "thaakireen";

$conn = new mysqli($host, $username, $password, $database);
header('Content-Type: application/json');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// Read JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

$student_id = isset($data['student_id']) ? intval($data['student_id']) : null;
$year = isset($data['year']) ? intval($data['year']) : null;

if (!$student_id || !$year) {
    echo json_encode(["success" => false, "error" => "Student ID and year are required."]);
    exit;
}

// Fetch exam records for student for the given year
$sql = "SELECT * FROM quran_exam_marks 
        WHERE student_id = ? AND YEAR(exam_date) = ?
        ORDER BY exam_date ASC, surah_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $student_id, $year);
$stmt->execute();
$result = $stmt->get_result();

$exams = [];
while ($row = $result->fetch_assoc()) {
    $exams[] = $row;
}

if (empty($exams)) {
    echo json_encode(["success" => true, "exams" => [], "message" => "No records found for selected year."]);
} else {
    echo json_encode(["success" => true, "exams" => $exams]);
}

$stmt->close();
$conn->close();
?>
