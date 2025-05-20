<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$db   = 'thaakireen';
$user = 'root';
$pass = 'Tashreeka94_';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
    exit;
}

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$year = isset($_GET['year']) ? $_GET['year'] : null;

if (!$studentId || !$year) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    // ✅ Fetch student info
    $stmtStudent = $pdo->prepare("
        SELECT school_grade, assigned_teacher_id, last_memorized_surah_id
        FROM students
        WHERE id = ?
    ");
    $stmtStudent->execute([$studentId]);
    $studentInfo = $stmtStudent->fetch();

    if (!$studentInfo) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    // ✅ Fetch one exam summary row with examiner name
    $stmtSummary = $pdo->prepare("
        SELECT 
            q.*, 
            CONCAT(u.firstName, ' ', u.lastName) AS examiner_name
        FROM quran_exam_marks q
        LEFT JOIN users u ON u.id = q.examiner_id
        WHERE q.student_id = ? AND q.school_year = ?
        LIMIT 1
    ");
    $stmtSummary->execute([$studentId, $year]);
    $summary = $stmtSummary->fetch();

    if (!$summary) {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'student' => $studentInfo,
            'records' => []
        ]);
        exit;
    }

    // ✅ Fetch all surah exam rows for the same student/year
    $stmtDetails = $pdo->prepare("
        SELECT 
            surah_name,
            ayahs_forgotten,
            stuck_errors AS stuck,
            tajweed_errors,
            fluency_score AS fluency,
            weighted_score,
            not_reached,
            skipped
        FROM quran_exam_marks
        WHERE student_id = ? AND school_year = ?
    ");
    $stmtDetails->execute([$studentId, $year]);
    $records = $stmtDetails->fetchAll();

    foreach ($records as &$r) {
        $r['ayahs_forgotten'] = (int)($r['ayahs_forgotten'] ?? 0);
        $r['stuck'] = (int)($r['stuck'] ?? 0);
        $r['tajweed_errors'] = (int)($r['tajweed_errors'] ?? 0);
        $r['fluency'] = (float)($r['fluency'] ?? 0);
        $r['weighted_score'] = (float)($r['weighted_score'] ?? 0);
        $r['not_reached'] = (int)($r['not_reached'] ?? 0);
        $r['skipped'] = (int)($r['skipped'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'exists' => true,
        'student' => $studentInfo,
        'exam' => [
            'exam_date' => $summary['exam_date'],
            'examiner_id' => $summary['examiner_id'],
            'examiner_name' => $summary['examiner_name'] ?? '',
            'bonus' => (float)($summary['bonus'] ?? 0),
            'deductions' => (float)($summary['deductions'] ?? 0),
            'final_score' => (float)($summary['final_score'] ?? 0),
            'school_grade' => $summary['grade'] ?? $studentInfo['school_grade'],
            'school_year' => $summary['school_year']
        ],
        'records' => $records
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
}
