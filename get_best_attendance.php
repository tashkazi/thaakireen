<?php
$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$schoolYear = $_GET['year'] ?? '2024/2025';
[$startYear, $endYear] = explode('/', $schoolYear);

// Build allowed month range
$months = [];
for ($m = 9; $m <= 12; $m++) {
    $months[] = "$startYear-" . str_pad($m, 2, '0', STR_PAD_LEFT);
}
for ($m = 1; $m <= 6; $m++) {
    $months[] = "$endYear-" . str_pad($m, 2, '0', STR_PAD_LEFT);
}

$placeholders = implode(',', array_fill(0, count($months), '?'));

$sql = "
SELECT 
  s.id AS student_id,
  CONCAT(s.firstName, ' ', s.lastName) AS name,
  SUM(m.total_present) AS total_present
FROM monthly_attendance_summary m
JOIN students s ON s.id = m.student_id
WHERE m.month_year IN ($placeholders)
GROUP BY s.id
HAVING total_present >= 152
ORDER BY total_present DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($months);
$results = $stmt->fetchAll();

$response = [];
foreach ($results as $row) {
    $response[] = [
        'name' => $row['name'],
        'days' => (int)$row['total_present'],
        'total_days' => 169
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
