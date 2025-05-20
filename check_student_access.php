<?php
session_start();
header('Content-Type: application/json');

$response = [
    "allowed" => false,
    "showFilter" => false,
    "teacherId" => null,
    "userId" => $_SESSION['user_id'] ?? null,
    "isAdmin" => false,
    "isExaminer" => false,
    "isTeacher" => false,
    "isSummerCampTeacher" => false
];

if (!isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit;
}

$response["isAdmin"] = !empty($_SESSION['isAdmin']);
$response["isExaminer"] = !empty($_SESSION['isExaminer']);
$response["isTeacher"] = !empty($_SESSION['isTeacher']);
$response["isSummerCampTeacher"] = !empty($_SESSION['isSummerCampTeacher']);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($response["isAdmin"]) {
    $response["allowed"] = true;
    $response["showFilter"] = true;

} elseif ($response["isTeacher"]) {
    $response["allowed"] = true;

    if (!$conn->connect_error) {
        $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $response["teacherId"] = $result['teacher_id'];
        }
        $stmt->close();
    }

} elseif ($response["isSummerCampTeacher"]) {
    $response["allowed"] = true;

    if (!$conn->connect_error) {
        $stmt = $conn->prepare("SELECT id FROM summer_camp_teachers WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            $response["teacherId"] = $result['id'];
        }
        $stmt->close();
    }
}

$conn->close();
echo json_encode($response);
