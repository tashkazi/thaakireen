<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// ==== Input Data ====
$studentId  = isset($_POST['student_id']) ? (int)$_POST['student_id'] : null;
$teacherId  = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
$examinerId = isset($_POST['examiner_id']) && $_POST['examiner_id'] !== '' ? (int)$_POST['examiner_id'] : null;
$grade      = $_POST['grade'] ?? null;

$fiqh       = isset($_POST['fiqh_score']) ? round((float)$_POST['fiqh_score']) : null;
$aqaaid     = isset($_POST['aqaaid_score']) ? round((float)$_POST['aqaaid_score']) : null;
$akhlaaq    = isset($_POST['akhlaaq_adab_score']) ? round((float)$_POST['akhlaaq_adab_score']) : null;
$taareekh   = isset($_POST['taareekh_score']) ? round((float)$_POST['taareekh_score']) : null;

$quranRecite = isset($_POST['quran_recitation_score']) ? round((float)$_POST['quran_recitation_score']) : null;
$ahadeeth    = isset($_POST['ahadeeth_score']) ? round((float)$_POST['ahadeeth_score']) : null;

$duaScore    = isset($_POST['dua_score']) ? round((float)$_POST['dua_score']) : null;
$surahMemorization = isset($_POST['surah_memorization_score']) ? round((float)$_POST['surah_memorization_score']) : null;


$examDate = date("Y-m-d");
$month = (int)date("n");

if (!$studentId || !$teacherId) {
    echo json_encode(["success" => false, "message" => "Missing student or teacher ID"]);
    exit;
}

if ($month === 8) {
    echo json_encode(["success" => false, "message" => "Exam marking is locked during August. Please wait until September."]);
    exit;
}

$schoolYear = ($month >= 9) ? date('Y') . '/' . (date('Y') + 1) : (date('Y') - 1) . '/' . date('Y');

// 🔒 Archive check
$check = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$check->bind_param("s", $schoolYear);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "This school year is archived. Marks cannot be changed."]);
    exit;
}
$check->close();

// ========== WRITTEN ========== 
$stmt = $conn->prepare("SELECT id FROM written_exam_marks WHERE student_id = ? AND school_year = ?");
$stmt->bind_param("is", $studentId, $schoolYear);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id); $stmt->fetch(); $stmt->close();
    $stmt = $conn->prepare("UPDATE written_exam_marks 
        SET fiqh_score=?, aqaaid_score=?, akhlaaq_adab_score=?, taareekh_score=?, teacher_id=?, examiner_id=?, grade=?, exam_date=? 
        WHERE id=?");
    $stmt->bind_param("ddddisssi", $fiqh, $aqaaid, $akhlaaq, $taareekh, $teacherId, $examinerId, $grade, $examDate, $id);
} else {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO written_exam_marks 
        (student_id, teacher_id, examiner_id, grade, exam_date, school_year, fiqh_score, aqaaid_score, akhlaaq_adab_score, taareekh_score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisssdddd", $studentId, $teacherId, $examinerId, $grade, $examDate, $schoolYear, $fiqh, $aqaaid, $akhlaaq, $taareekh);
}
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Written marks failed", "error" => $stmt->error]);
    exit;
}
$stmt->close();

// ========== ORAL ==========
$oralAvg = ($quranRecite !== null && $ahadeeth !== null) 
    ? round(($quranRecite + $ahadeeth) / 2, 2) 
    : null;

$stmt = $conn->prepare("SELECT id FROM oral_exam_marks WHERE student_id = ? AND school_year = ?");
$stmt->bind_param("is", $studentId, $schoolYear);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id); $stmt->fetch(); $stmt->close();
    $stmt = $conn->prepare("UPDATE oral_exam_marks 
        SET quran_recitation_score=?, ahadeeth_score=?, final_oral_score=?, teacher_id=?, examiner_id=?, grade=?, exam_date=?
        WHERE id=?");
    $stmt->bind_param("dddisssi", $quranRecite, $ahadeeth, $oralAvg, $teacherId, $examinerId, $grade, $examDate, $id);
} else {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO oral_exam_marks 
        (student_id, teacher_id, examiner_id, grade, exam_date, school_year, quran_recitation_score, ahadeeth_score, final_oral_score)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisssddd", $studentId, $teacherId, $examinerId, $grade, $examDate, $schoolYear, $quranRecite, $ahadeeth, $oralAvg);
}
if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Oral marks failed", "error" => $stmt->error]);
    exit;
}
$stmt->close();

// ========== DUA ==========
if ($duaScore !== null) {
    $stmt = $conn->prepare("REPLACE INTO duaa_exam_summary 
        (student_id, exam_date, school_year, grade, final_score) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssd", $studentId, $examDate, $schoolYear, $grade, $duaScore);
    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Duaa save failed", "error" => $stmt->error]);
        exit;
    }
    $stmt->close();
}

// ========== QURAN FINAL SCORE ONLY ==========
if ($surahMemorization !== null) {
    $stmt = $conn->prepare("SELECT id FROM quran_exam_marks WHERE student_id = ? AND school_year = ?");
    $stmt->bind_param("is", $studentId, $schoolYear);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE quran_exam_marks SET final_score = ? WHERE id = ?");
        $stmt->bind_param("di", $surahMemorization, $id);
    } else {
        $stmt->close();

        // Insert dummy row with only required fields and final_score
        $stmt = $conn->prepare("
            INSERT INTO quran_exam_marks 
            (student_id, teacher_id, examiner_id, surah_name, total_ayahs, tajweed_errors, memorization_score, tajweed_score, weighted_score, final_score, exam_date, grade, school_year)
            VALUES (?, ?, ?, '', 0, 0, 0, 0, 0, ?, ?, ?, ?)
        ");

        // Allow nulls for optional fields
        $examinerIdParam = $examinerId !== null ? $examinerId : null;
        $examDateParam = !empty($examDate) ? $examDate : null;

     $stmt->bind_param("iiidsss", 
    $studentId, 
    $teacherId, 
    $examinerIdParam, 
    $surahMemorization, 
    $examDateParam, 
    $grade, 
    $schoolYear
);

    }

    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "message" => "Qur'an final score save failed", "error" => $stmt->error]);
        exit;
    }
    $stmt->close();
}


// ✅ Audit Log
$userName = $_SESSION['userName'] ?? 'Unknown';
$role = $_SESSION['role'] ?? 'Unknown';
$log = $conn->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
$action = "Saved exam marks for student ID $studentId ($schoolYear)";
$log->bind_param("sss", $userName, $role, $action);
$log->execute();
$log->close();

echo json_encode(["success" => true]);
$conn->close();
