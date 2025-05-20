<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}

// Example: Fetch logs from `audit_logs` table
$sql = "SELECT timestamp, user_name, role, action FROM audit_logs ORDER BY timestamp DESC";
$result = $conn->query($sql);

$logs = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

echo json_encode(["success" => true, "logs" => $logs]);
?>
