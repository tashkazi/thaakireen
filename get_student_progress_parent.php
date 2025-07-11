<?php
session_start();

$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

if (!isset($_GET['studentId'])) {
  echo json_encode(['error' => 'Missing student ID']);
  exit;
}

$studentId = intval($_GET['studentId']);

$stmt = $pdo->prepare("
  SELECT 
    s.firstName, 
    s.lastName,
    s.school_grade,
    s.book_grade,
    s.readingMaterial,
    s.qaidaPage,
    s.quran_level,
    s.notes,
    surahs.name AS last_memorized_surah,
    duas.dua_name AS last_memorized_dua
  FROM students s
  LEFT JOIN surahs ON s.last_memorized_surah_id = surahs.id
  LEFT JOIN duas ON s.last_memorized_dua_id = duas.id
  WHERE s.id = ?
");

$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
  echo json_encode(['error' => 'Student not found']);
  exit;
}

echo json_encode($student);
