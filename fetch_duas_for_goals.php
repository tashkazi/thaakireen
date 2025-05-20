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

    // Adjust column name if your table uses something other than 'grade'
    $stmt = $pdo->query("SELECT id, grade, dua_name FROM duas ORDER BY grade, id");
    $duas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['duas' => $duas]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    http_response_code(500);
}
?>
