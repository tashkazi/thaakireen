<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "DB connection failed"]);
    exit;
}

$data = [
    "total" => 0,
    "bySchoolGrade" => [],
    "byBookGrade" => [],
    "byYear" => []
];

// Total students
$result = $conn->query("SELECT COUNT(*) AS total FROM students");
if ($row = $result->fetch_assoc()) {
    $data["total"] = (int)$row["total"];
}

// Students by school grade
$result = $conn->query("SELECT school_grade, COUNT(*) AS count FROM students GROUP BY school_grade ORDER BY school_grade");
while ($row = $result->fetch_assoc()) {
    $data["bySchoolGrade"][$row["school_grade"]] = (int)$row["count"];
}

// Students by book grade
$result = $conn->query("SELECT book_grade, COUNT(*) AS count FROM students GROUP BY book_grade ORDER BY book_grade");
while ($row = $result->fetch_assoc()) {
    $data["byBookGrade"][$row["book_grade"]] = (int)$row["count"];
}

// Students by school year
$result = $conn->query("SELECT school_year, COUNT(*) AS count FROM students GROUP BY school_year ORDER BY school_year");
while ($row = $result->fetch_assoc()) {
    $data["byYear"][$row["school_year"]] = (int)$row["count"];
}

echo json_encode($data);
?>
