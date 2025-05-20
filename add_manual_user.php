<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$first = trim($data['firstName']);
$last = trim($data['lastName']);
$title = trim($data['title'] ?? '');
$email = trim($data['email']);
$phone = trim($data['phone'] ?? '');

$roles = [
    'isTeacher', 'isAdmin', 'isExaminer',
    'isPrincipal', 'isCoordinator', 'isSupervisor',
    'isVolunteer', 'isSummerCampTeacher'
];

if (!$first || !$last || !$email) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// Add title into the query
$roleFields = implode(", ", $roles);
$placeholders = implode(", ", array_fill(0, count($roles), "?"));

$stmt = $conn->prepare("INSERT INTO users (firstName, lastName, title, email, phone, $roleFields, isApproved) VALUES (?, ?, ?, ?, ?, $placeholders, 1)");

$params = [$first, $last, $title, $email, $phone];
foreach ($roles as $role) {
    $params[] = $data[$role] ?? 0;
}

$stmt->bind_param(
    str_repeat("s", 5) . str_repeat("i", count($roles)), // s = 5 string fields (first, last, title, email, phone)
    ...$params
);

if ($stmt->execute()) {
    $newUserId = $conn->insert_id;

    if (!empty($data['isSummerCampTeacher'])) {
        $campStmt = $conn->prepare("INSERT INTO summer_camp_teachers (user_id, first_name, last_name, email) VALUES (?, ?, ?, ?)");
        $campStmt->bind_param("isss", $newUserId, $first, $last, $email);
        $campStmt->execute();
        $campStmt->close();
    }

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
