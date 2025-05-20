<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->query("SELECT school_grade, goal_dua_id FROM duaa_grade_goals ORDER BY CAST(school_grade AS UNSIGNED)");
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['goals' => $goals]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    http_response_code(500);
}
?>
