<?php
header('Content-Type: application/json');

$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$stmt = $pdo->query("SELECT school_year, archived_at FROM archived_years ORDER BY archived_at DESC");
$years = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["years" => $years]);