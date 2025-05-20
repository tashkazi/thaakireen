<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->query("SELECT school_grade, goal_surah_id FROM grade_surah_goals ORDER BY school_grade");
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['goals' => $goals]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    http_response_code(500);
}
?>
