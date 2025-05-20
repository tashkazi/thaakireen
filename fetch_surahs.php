<?php
$host = "localhost";
$username = "root";
$password = "Tashreeka94_";
$database = "thaakireen";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}

header('Content-Type: application/json');

$result = $conn->query("SELECT id, name, total_ayahs FROM surahs WHERE surah_number BETWEEN 18 AND 114 ORDER BY surah_number DESC");

$surahs = [];
while ($row = $result->fetch_assoc()) {
    $surahs[] = $row;
}

echo json_encode(["surahs" => $surahs]);

$conn->close();
?>
