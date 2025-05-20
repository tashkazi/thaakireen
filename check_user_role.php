<?php
session_start();
header('Content-Type: application/json');

// Safely return user role and ID details
echo json_encode([
    'userId'                => $_SESSION['user_id']               ?? null,
    'isAdmin'               => $_SESSION['isAdmin']               ?? false,
    'isExaminer'            => $_SESSION['isExaminer']            ?? false,
    'isTeacher'             => $_SESSION['isTeacher']             ?? false,
    'teacherId'             => $_SESSION['teacher_id']            ?? null,
    'isSummerCampTeacher'   => $_SESSION['isSummerCampTeacher']   ?? false,
    'summerCampTeacherId'   => $_SESSION['summer_camp_teacher_id'] ?? null
]);
?>
