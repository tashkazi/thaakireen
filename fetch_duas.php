<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->query("SELECT id, grade, dua_name, arabic_marks, translation_marks FROM duas ORDER BY grade, id");
    $duas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [];
    foreach ($duas as $dua) {
        $grade = $dua['grade'];
        if (!isset($grouped[$grade])) {
            $grouped[$grade] = [];
        }
        $grouped[$grade][] = $dua;
    }

    echo json_encode(['duasByGrade' => $grouped]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    http_response_code(500);
}
?>
