<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $parentId = $_GET['parentId'] ?? null;

    if (!$parentId) {
        echo json_encode(["success" => false, "message" => "Missing parent ID"]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT s.id, s.firstName, s.lastName, s.school_grade
        FROM parent_students ps
        JOIN students s ON ps.student_id = s.id
        WHERE ps.parent_id = ?
    ");
    $stmt->execute([$parentId]);
    $students = $stmt->fetchAll();

    echo json_encode(["success" => true, "students" => $students]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
