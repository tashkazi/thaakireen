<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$userId     = $_SESSION['user_id'] ?? null;
$isAdmin    = $_SESSION['isAdmin'] ?? 0;
$isExaminer = $_SESSION['isExaminer'] ?? 0;
$isTeacher  = $_SESSION['isTeacher'] ?? 0;

if (!$userId) {
    echo json_encode(["error" => "Login required"]);
    exit;
}

$teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
$schoolYear = $_GET['school_year'] ?? '';

if (!$teacherId || !$schoolYear) {
    echo json_encode(["error" => "Missing teacher ID or school year"]);
    exit;
}

// ✅ Enforce access for teachers
if (!$isAdmin && !$isExaminer) {
    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $teacherRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$teacherRow || (int)$teacherRow['teacher_id'] !== $teacherId) {
        echo json_encode(["error" => "Unauthorized access"]);
        exit;
    }
}

// 🔍 Archived check
$archived = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$archived->bind_param("s", $schoolYear);
$archived->execute();
$archived->store_result();
$isArchived = $archived->num_rows > 0;
$archived->close();

// ✅ Get all students for this teacher + year
$query = "
    SELECT 
        s.id, s.firstName, s.lastName, s.readingMaterial, s.qaidaPage,
        s.book_grade AS book_grade_id, s.school_grade AS school_grade_id,
        s.last_memorized_surah_id, sur.name AS lastMemorizedSurah,
        s.last_memorized_dua_id, d.dua_name AS lastMemorizedDua,
        s.surah_complete, s.updatedAt, s.notes,
        s.assigned_teacher_id, t.user_id AS teacher_user_id,

        sg.goal_surah_id, sg_surah.name AS surah_goal,
        dg.goal_dua_id, dg_dua.dua_name AS dua_goal

    FROM students s
    JOIN teachers t ON s.assigned_teacher_id = t.teacher_id
    LEFT JOIN grade_surah_goals sg ON s.school_grade = sg.school_grade
    LEFT JOIN surahs sg_surah ON sg.goal_surah_id = sg_surah.id
    LEFT JOIN duaa_grade_goals dg ON dg.school_grade = s.school_grade
    LEFT JOIN duas dg_dua ON dg.goal_dua_id = dg_dua.id
    LEFT JOIN surahs sur ON s.last_memorized_surah_id = sur.id
    LEFT JOIN duas d ON s.last_memorized_dua_id = d.id
    WHERE s.school_year = ? AND s.assigned_teacher_id = ?
    ORDER BY s.firstName, s.lastName
";

$stmt = $conn->prepare($query);
$stmt->bind_param("si", $schoolYear, $teacherId);
$stmt->execute();
$result = $stmt->get_result();

$students = [];

while ($row = $result->fetch_assoc()) {
    $surahGoalMet = false;
    if ((int)$row['school_grade_id'] > 7) {
        $surahGoalMet = (int)$row['surah_complete'] === 1;
    } elseif (!empty($row['goal_surah_id']) && !empty($row['last_memorized_surah_id'])) {
        $surahGoalMet = ((int)$row['last_memorized_surah_id'] >= (int)$row['goal_surah_id']);
    }

    $duaGoalMet = false;
    if (!empty($row['goal_dua_id']) && !empty($row['last_memorized_dua_id'])) {
        $duaGoalMet = ((int)$row['last_memorized_dua_id'] >= (int)$row['goal_dua_id']);
    }

    $students[] = [
        "id" => $row['id'],
        "firstName" => $row['firstName'],
        "lastName" => $row['lastName'],
        "readingMaterial" => $row['readingMaterial'],
        "qaidaPage" => $row['qaidaPage'],
        "last_memorized_surah_id" => $row['last_memorized_surah_id'],
        "last_memorized_dua_id" => $row['last_memorized_dua_id'],
        "lastMemorizedSurah" => $row['lastMemorizedSurah'],
        "lastMemorizedDua" => $row['lastMemorizedDua'],
        "book_grade_id" => $row['book_grade_id'],
        "school_grade_id" => $row['school_grade_id'],
        "notes" => $row['notes'],
        "updatedAt" => $row['updatedAt'],
        "teacher_user_id" => $row['teacher_user_id'],
        "goal_surah_id" => $row['goal_surah_id'],
        "surah_goal" => $row['surah_goal'],
        "goal_dua_id" => $row['goal_dua_id'],
        "dua_goal" => $row['dua_goal'],
        "surahGoalMet" => $surahGoalMet,
        "duaGoalMet" => $duaGoalMet,
        "surahStatus" => $surahGoalMet ? "Goal Met" : "Not Yet",
        "duaStatus" => $duaGoalMet ? "Goal Met" : "Not Yet",
        "isArchived" => $isArchived
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    "students" => $students,
    "total" => count($students),
    "archived" => $isArchived
]);
