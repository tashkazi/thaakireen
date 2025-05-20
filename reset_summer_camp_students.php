<?php
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($conn->connect_error) {
    echo json_encode(["reset" => false, "message" => "DB connection failed: " . $conn->connect_error]);
    exit;
}

$now = date("Y-m-d");
$resetDate = date("Y") . "-08-31";

if ($now >= $resetDate) {
    $copy = $conn->query("INSERT INTO summer_camp_students_archive (first_name, last_name, school_grade, assigned_teacher_id, age, contact_name, phone, medical)
                          SELECT first_name, last_name, school_grade, assigned_teacher_id, age, contact_name, phone, medical FROM summer_camp_students");

    if (!$copy) {
        echo json_encode(["reset" => false, "message" => "Copy failed: " . $conn->error]);
        exit;
    }

    $delete = $conn->query("DELETE FROM summer_camp_students");
    if (!$delete) {
        echo json_encode(["reset" => false, "message" => "Delete failed: " . $conn->error]);
        exit;
    }

    echo json_encode(["reset" => true, "message" => "✅ Summer camp students archived and reset."]);
} else {
    echo json_encode(["reset" => false, "message" => "🕒 Too early to reset. Try after August 31."]);
}
?>
