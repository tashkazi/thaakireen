<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Fetch upcoming events (limit next 10)
$sql = "SELECT title, date FROM calendar_events WHERE date >= CURDATE() ORDER BY date ASC LIMIT 10";
$result = $conn->query($sql);

$events = [];
while ($row = $result->fetch_assoc()) {
    $formatted = date("M j", strtotime($row['date']));
    $events[] = $row['title'] . " – " . $formatted;
}

echo json_encode(["type" => "calendar-events", "events" => $events]);
?>
