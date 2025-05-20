<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$statusFilter = $_GET['status'] ?? null; // Accepts: 'Pending', 'Waitlisted'

$query = "SELECT * FROM registration_requests WHERE approved = 0";
$params = [];
$types = "";

if ($statusFilter && in_array($statusFilter, ['Pending', 'Waitlisted'])) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$query .= " ORDER BY submitted_at ASC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode([
    "success" => true,
    "students" => $students,
    "count" => count($students)
]);
