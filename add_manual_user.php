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
    'isVolunteer', 'isSummerCampTeacher', 'isParent' // ✅ Added
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
    str_repeat("s", 5) . str_repeat("i", count($roles)),
    ...$params
);

if ($stmt->execute()) {
    $newUserId = $conn->insert_id;

    // ✅ Add to summer_camp_teachers if applicable
    if (!empty($data['isSummerCampTeacher'])) {
        $campStmt = $conn->prepare("INSERT INTO summer_camp_teachers (user_id, first_name, last_name, email) VALUES (?, ?, ?, ?)");
        $campStmt->bind_param("isss", $newUserId, $first, $last, $email);
        $campStmt->execute();
        $campStmt->close();
    }

    // ✅ Auto-link parent to registration_requests if applicable
    if (!empty($data['isParent'])) {
        $linkStmt = $conn->prepare("UPDATE registration_requests SET parent_user_id = ? WHERE parent1_email = ?");
        $linkStmt->bind_param("is", $newUserId, $email);
        $linkStmt->execute();
        $linkStmt->close();
    }

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
