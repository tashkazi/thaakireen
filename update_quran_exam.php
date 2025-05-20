<?php
header('Content-Type: application/json');
session_start(); // Required for audit log user/role tracking
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required input
    if (
        !$data ||
        !isset($data['student_id'], $data['exam_date'], $data['surahs']) ||
        !is_array($data['surahs']) || count($data['surahs']) === 0
    ) {
        throw new Exception("Invalid input");
    }

    $student_id   = (int) $data["student_id"];
    $teacher_id   = (int) ($data["teacher_id"] ?? 0);
    $examiner_id  = (int) ($data["examiner_id"] ?? 0);
    $grade        = $data["grade"] ?? null;
    $exam_date    = $data["exam_date"];
    $bonus        = (float) ($data["bonus"] ?? 0);
    $deductions   = (float) ($data["deductions"] ?? 0);
    $surahs       = $data["surahs"];

    // 🗓️ Block updates in August
    $month = (int)date('n');
    if ($month === 8) {
        echo json_encode([
            "success" => false,
            "message" => "Exam updates are locked during August. Please wait until the new school year begins in September."
        ]);
        exit;
    }

    // Determine current school year
    if ($month >= 9) {
        $school_year = date('Y') . '/' . (date('Y') + 1);
    } else {
        $school_year = (date('Y') - 1) . '/' . date('Y');
    }

    // Archive check
    $check = $pdo->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
    $check->execute([$school_year]);
    if ($check->fetchColumn()) {
        echo json_encode([
            "success" => false,
            "message" => "This school year is archived. Exam data cannot be updated."
        ]);
        exit;
    }

    $total_weight = 0;
    $count = 0;

    // Delete existing rows for this student/date
    $pdo->prepare("DELETE FROM quran_exam_marks WHERE student_id = ? AND exam_date = ?")
        ->execute([$student_id, $exam_date]);

    // Prepare insert statement
    $insert = $pdo->prepare("INSERT INTO quran_exam_marks (
        student_id, teacher_id, examiner_id, surah_name, total_ayahs,
        ayahs_forgotten, stuck_errors, tajweed_errors, fluency_score,
        memorization_score, tajweed_score, weighted_score,
        exam_date, grade, not_reached, skipped, bonus, deductions, final_score, school_year
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($surahs as $s) {
        $name          = $s["surah_name"] ?? '';
        $total         = (int)($s["total_ayahs"] ?? 0);
        $forgotten     = (int)($s["ayahs_forgotten"] ?? 0);
        $stuck         = (int)($s["stuck_errors"] ?? 0);
        $tajweed       = (int)($s["tajweed_errors"] ?? 0);
        $fluency       = (float)($s["fluency_score"] ?? 0);
        $not_reached   = !empty($s["not_reached"]) ? 1 : 0;
        $skipped       = !empty($s["skipped"]) ? 1 : 0;

        $should_score = !$not_reached && !$skipped;

        $memorization_score = ($total > 0 && $should_score) ? (($total - $forgotten) / $total) * 100 : 0;
        $tajweed_score      = $should_score ? max(0, 100 - ($tajweed * 2)) : 0;
        $stuck_score        = $should_score ? max(0, 100 - ($stuck * 2)) : 0;
        $fluency_score      = $should_score ? max(0, min(100, $fluency)) : 0;

        $weighted_score = $should_score
            ? round(($memorization_score * 0.6) + ($tajweed_score * 0.2) + ($stuck_score * 0.1) + ($fluency_score * 0.1), 2)
            : 0;

        $final_score = $weighted_score;

        $insert->execute([
            $student_id, $teacher_id, $examiner_id, $name, $total,
            $forgotten, $stuck, $tajweed, $fluency_score,
            $memorization_score, $tajweed_score, $weighted_score,
            $exam_date, $grade, $not_reached, $skipped, 0, 0, $final_score, $school_year
        ]);

        if ($should_score) {
            $total_weight += $weighted_score;
            $count++;
        }
    }

    // Final average score
    $final_score = $count > 0 ? round(($total_weight / $count) + $bonus - $deductions, 2) : 0;
    $final_score = max(0, min(100, $final_score));

    $pdo->prepare("UPDATE quran_exam_marks
        SET bonus = ?, deductions = ?, final_score = ?
        WHERE student_id = ? AND exam_date = ?")
        ->execute([$bonus, $deductions, $final_score, $student_id, $exam_date]);

    // ✅ Audit Logging
    $user_name = $_SESSION['userName'] ?? 'Unknown User';
    $role = $_SESSION['role'] ?? 'Unknown Role';
    $action = "Updated Quran exam for student ID $student_id for $school_year";

    $log = $pdo->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
    $log->execute([$user_name, $role, $action]);

    echo json_encode([
        "success" => true,
        "message" => "Quran exam updated successfully.",
        "final_score" => $final_score
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
