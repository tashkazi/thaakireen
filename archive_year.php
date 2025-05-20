<?php
session_start();
header('Content-Type: application/json');

if (!$_SESSION['isAdmin']) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$year = $_GET['year'] ?? '';
if (!$year) {
    echo json_encode(["error" => "Year not provided"]);
    exit;
}

$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$stmt = $pdo->prepare("INSERT IGNORE INTO archived_years (school_year) VALUES (?)");
$stmt->execute([$year]);

echo json_encode(["message" => "School year $year successfully archived."]);
