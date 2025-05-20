<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 🟢 Get teacher names
    $teachers = $pdo->query("
        SELECT teacher_id, CONCAT(first_name, ' ', last_name) AS name
        FROM teachers
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    // 🟩 1. Completion by grade GOAL (per teacher)
    $stmt = $pdo->query("
        SELECT 
            t.teacher_id,
            COUNT(s.id) AS total_students,
            SUM(CASE WHEN s.last_memorized_surah_id >= g.goal_surah_id THEN 1 ELSE 0 END) AS completed_goal
        FROM students s
        JOIN grade_surah_goals g ON s.school_grade = g.school_grade
        JOIN teachers t ON s.assigned_teacher_id = t.teacher_id
        GROUP BY t.teacher_id
    ");
    $completionByTeacher = [];
    while ($row = $stmt->fetch()) {
        $name = $teachers[$row['teacher_id']] ?? 'Unknown';
        $percent = $row['total_students'] > 0 ? round(($row['completed_goal'] / $row['total_students']) * 100) : 0;
        $completionByTeacher[$name] = $percent;
    }

    // 🟦 2. Memorization Accuracy Breakdown (from Quran exams)
    $stmt = $pdo->query("
        SELECT 
            t.teacher_id,
            qm.weighted_score
        FROM quran_exam_marks qm
        JOIN students s ON qm.student_id = s.id
        JOIN teachers t ON s.assigned_teacher_id = t.teacher_id
    ");
    $scoreBuckets = []; // [teacher_name => ['above80'=>x, 'between60and79'=>y, 'below60'=>z]]
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = $teachers[$row['teacher_id']] ?? 'Unknown';
        $score = (float)$row['weighted_score'];

        if (!isset($scoreBuckets[$name])) {
            $scoreBuckets[$name] = ['above80' => 0, 'between60and79' => 0, 'below60' => 0];
        }

        if ($score >= 80) $scoreBuckets[$name]['above80']++;
        elseif ($score >= 60) $scoreBuckets[$name]['between60and79']++;
        else $scoreBuckets[$name]['below60']++;
    }

    // 🟥 3. Goal Alignment (Behind/On/Above) per teacher
    $stmt = $pdo->query("
        SELECT 
            t.teacher_id,
            s.school_grade,
            s.last_memorized_surah_id,
            g.goal_surah_id
        FROM students s
        JOIN grade_surah_goals g ON s.school_grade = g.school_grade
        JOIN teachers t ON s.assigned_teacher_id = t.teacher_id
    ");
    $goalAlignment = []; // [teacher => ['behind'=>x, 'on'=>y, 'above'=>z]]
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = $teachers[$row['teacher_id']] ?? 'Unknown';
        $studentSurah = (int)$row['last_memorized_surah_id'];
        $goalSurah = (int)$row['goal_surah_id'];

        if (!isset($goalAlignment[$name])) {
            $goalAlignment[$name] = ['behind' => 0, 'on' => 0, 'above' => 0];
        }

        if ($studentSurah < $goalSurah) {
            $goalAlignment[$name]['behind']++;
        } elseif ($studentSurah === $goalSurah) {
            $goalAlignment[$name]['on']++;
        } else {
            $goalAlignment[$name]['above']++;
        }
    }

    echo json_encode([
        "success" => true,
        "completionByTeacher" => $completionByTeacher,
        "scoreSummary" => $scoreBuckets,
        "goalAlignment" => $goalAlignment
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
