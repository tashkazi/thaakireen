<?php
$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$stmt = $pdo->query("SELECT DISTINCT class_label FROM students WHERE class_label IS NOT NULL AND class_label != ''");
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['classes' => $classes]);
?>
