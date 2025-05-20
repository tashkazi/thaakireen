<?php
$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$class_label = $_GET['class_label'] ?? '';
if (!$class_label) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, CONCAT(firstName, ' ', lastName) as name FROM students WHERE class_label = ?");
$stmt->execute([$class_label]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
