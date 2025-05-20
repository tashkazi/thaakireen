<?php
header('Content-Type: application/json');

// Debug mode ON temporarily (remove for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$month = $_GET['month'] ?? null;
if (!$month) {
    echo json_encode(["success" => false, "message" => "No month specified"]);
    exit;
}

$like = $month . '%'; // e.g., "2025-05%"

$sql = "
    SELECT 
        CONCAT(u.firstName, ' ', u.lastName) AS name,
        SUM(IFNULL(t.hours_worked, 0)) AS total_hours,
        SUM(CASE 
            WHEN t.is_absent = 0 AND t.hours_worked > 0 THEN 1 
            ELSE 0 
        END) AS days_present,
        SUM(CASE 
            WHEN t.is_absent = 1 THEN 1 
            ELSE 0 
        END) AS days_absent
    FROM teacher_timesheets t
    JOIN users u ON u.id = t.user_id
    WHERE t.date LIKE ?
    GROUP BY t.user_id
    ORDER BY name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();

$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = [
        "name" => $row['name'],
        "total_hours" => floatval($row['total_hours']),
        "days_present" => intval($row['days_present']),
        "days_absent" => intval($row['days_absent'])
    ];
}

echo json_encode(["success" => true, "records" => $records]);
?>
