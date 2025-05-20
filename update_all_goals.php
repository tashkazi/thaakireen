<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$conn = new mysqli("localhost", "root", "", "thaakireen");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$success = true;
$errors = [];

// ✅ Process Surah Goals
if (isset($data['surahs']) && is_array($data['surahs'])) {
    foreach ($data['surahs'] as $s) {
        $grade = $conn->real_escape_string($s['grade']);
        $sid = (int) $s['surah_id'];

        // Validate surah ID exists
        $surahCheck = $conn->query("SELECT id FROM surahs WHERE id = $sid");
        if (!$surahCheck || $surahCheck->num_rows === 0) {
            $success = false;
            $errors[] = "Invalid Surah ID ($sid) for Grade $grade";
            continue;
        }

        // Update or insert goal
        $check = $conn->query("SELECT 1 FROM grade_surah_goals WHERE school_grade = '$grade'");
        if ($check && $check->num_rows > 0) {
            $update = $conn->query("UPDATE grade_surah_goals SET goal_surah_id = $sid WHERE school_grade = '$grade'");
            if (!$update) {
                $success = false;
                $errors[] = "Failed to update Surah goal for Grade $grade";
            }
        } else {
            $insert = $conn->query("INSERT INTO grade_surah_goals (school_grade, goal_surah_id) VALUES ('$grade', $sid)");
            if (!$insert) {
                $success = false;
                $errors[] = "Failed to insert Surah goal for Grade $grade";
            }
        }
    }
}

// ✅ Process Dua Goals
if (isset($data['duas']) && is_array($data['duas'])) {
    foreach ($data['duas'] as $d) {
        $grade = $conn->real_escape_string($d['grade']);
        $did = (int) $d['dua_id'];

        // Validate dua ID exists
        $duaCheck = $conn->query("SELECT id FROM duas WHERE id = $did");
        if (!$duaCheck || $duaCheck->num_rows === 0) {
            $success = false;
            $errors[] = "Invalid Dua ID ($did) for Grade $grade";
            continue;
        }

        // Update or insert goal
        $check = $conn->query("SELECT 1 FROM duaa_grade_goals WHERE school_grade = '$grade'");
        if ($check && $check->num_rows > 0) {
            $update = $conn->query("UPDATE duaa_grade_goals SET goal_dua_id = $did WHERE school_grade = '$grade'");
            if (!$update) {
                $success = false;
                $errors[] = "Failed to update Dua goal for Grade $grade";
            }
        } else {
            $insert = $conn->query("INSERT INTO duaa_grade_goals (school_grade, goal_dua_id) VALUES ('$grade', $did)");
            if (!$insert) {
                $success = false;
                $errors[] = "Failed to insert Dua goal for Grade $grade";
            }
        }
    }
}

// ✅ Return final JSON response
echo json_encode([
    "success" => $success,
    "message" => $success ? "Goals updated successfully." : "Some updates failed.",
    "errors" => $errors
]);
?>
