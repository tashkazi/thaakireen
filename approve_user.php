<?php
session_start();
header('Content-Type: application/json');

// DB config
$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$data = json_decode(file_get_contents("php://input"), true);
$userId = $data["userId"] ?? null;

if (!$userId) {
    echo json_encode(["success" => false, "message" => "Missing user ID"]);
    exit;
}

// ✅ Include all roles including isParent
$roles = [
    'isAdmin', 'isTeacher', 'isExaminer', 'isPrincipal',
    'isCoordinator', 'isSupervisor', 'isVolunteer',
    'isSummerCampTeacher', 'isParent'
];

$setParts = [];
$params = [];

foreach ($roles as $role) {
    $setParts[] = "$role = ?";
    $params[] = !empty($data[$role]) ? 1 : 0;
}
$params[] = $userId;

// ✅ Update user approval and roles
$sql = "UPDATE users SET approval_status = 'approved', isApproved = 1, " . implode(", ", $setParts) . " WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// ✅ Fetch user info
$userStmt = $pdo->prepare("SELECT firstName, lastName, email FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

$fullName = $user['firstName'] . ' ' . $user['lastName'];
$email = $user['email'];
$isParent = !empty($data['isParent']);

// ✅ Insert into employee_directory only if NOT a parent
if (!$isParent) {
    $checkEmp = $pdo->prepare("SELECT id FROM employee_directory WHERE email = ?");
    $checkEmp->execute([$email]);

    if ($checkEmp->rowCount() === 0) {
        $type = !empty($data['isVolunteer']) ? 'Volunteer' :
                (!empty($data['isSummerCampTeacher']) ? 'Summer Camp Teacher' :
                (!empty($data['isTeacher']) ? 'Teacher' : 'Staff'));

        $insertEmp = $pdo->prepare("INSERT INTO employee_directory (full_name, position, type, email) VALUES (?, ?, ?, ?)");
        $insertEmp->execute([$fullName, $type, $type, $email]);
    }
}

// ✅ Log approval action
$granted = [];
foreach ($roles as $i => $role) {
    if ($params[$i] === 1) {
        $granted[] = $role;
    }
}
$grantedStr = empty($granted) ? "No roles assigned" : "Roles: " . implode(', ', $granted);

$performedBy = $_SESSION['userName'] ?? 'Unknown User';
$role = $_SESSION['role'] ?? 'Unknown Role';
$action = "Approved account and updated roles for $fullName (User ID: $userId). $grantedStr";

$logStmt = $pdo->prepare("INSERT INTO audit_logs (user_name, role, action) VALUES (?, ?, ?)");
$logStmt->execute([$performedBy, $role, $action]);

// ✅ Auto-link parent to any matching registration_requests
if ($isParent) {
    $linkStmt = $pdo->prepare("UPDATE registration_requests SET parent_user_id = ? WHERE parent1_email = ?");
    $linkStmt->execute([$userId, $email]);
}

echo json_encode(["success" => true]);
