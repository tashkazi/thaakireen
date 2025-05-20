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

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("DELETE FROM archived_years WHERE school_year = ?");
    $stmt->execute([$year]);

    echo json_encode(["message" => "Year $year successfully unarchived."]);
} catch (Exception $e) {
    echo json_encode(["error" => "Failed to unarchive year."]);
}
