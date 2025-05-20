<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

$results = $conn->query("SELECT title, date as start FROM calendar_events");
$events = [];
while ($row = $results->fetch_assoc()) {
    $events[] = $row;
}
echo json_encode($events);
