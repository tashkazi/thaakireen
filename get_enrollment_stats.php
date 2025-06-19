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
    "byYear" => [],
    "retentionStats" => [] // NEW
];

// ✅ Total students
$result = $conn->query("SELECT COUNT(*) AS total FROM students WHERE approved = 1");
if ($row = $result->fetch_assoc()) {
    $data["total"] = (int)$row["total"];
}

// 📘 By school grade
$result = $conn->query("SELECT school_grade, COUNT(*) AS count FROM students WHERE approved = 1 GROUP BY school_grade ORDER BY school_grade");
while ($row = $result->fetch_assoc()) {
    $data["bySchoolGrade"][$row["school_grade"]] = (int)$row["count"];
}

// 📙 By book grade
$result = $conn->query("SELECT book_grade, COUNT(*) AS count FROM students WHERE approved = 1 GROUP BY book_grade ORDER BY book_grade");
while ($row = $result->fetch_assoc()) {
    $data["byBookGrade"][$row["book_grade"]] = (int)$row["count"];
}

// 📅 By year
$result = $conn->query("SELECT school_year, COUNT(*) AS count FROM students WHERE approved = 1 GROUP BY school_year ORDER BY school_year");
while ($row = $result->fetch_assoc()) {
    $data["byYear"][$row["school_year"]] = (int)$row["count"];
}

// 📉 Retention vs. Left (NEW section)
$result = $conn->query("
    SELECT school_year,
           SUM(status = 'Active') AS active,
           SUM(status = 'Left') AS left_count
    FROM students
    WHERE approved = 1
    GROUP BY school_year
    ORDER BY school_year
");

while ($row = $result->fetch_assoc()) {
    $year = $row["school_year"];
    $data["retentionStats"][$year] = [
        "active" => (int)$row["active"],
        "left" => (int)$row["left_count"]
    ];
}

echo json_encode($data);
?>
