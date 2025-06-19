<?php
header('Content-Type: application/json');

// DB connection
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Fetch upcoming events (limit 10)
$sql = "SELECT title, date FROM calendar_events WHERE date >= CURDATE() ORDER BY date ASC LIMIT 10";
$result = $conn->query($sql);

$events = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $formattedDate = date("M j", strtotime($row['date']));
        $events[] = $row['title'] . " – " . $formattedDate;
    }
    echo json_encode(["type" => "calendar-events", "events" => $events]);
} else {
    echo json_encode(["success" => false, "message" => "Query failed"]);
}
?>
