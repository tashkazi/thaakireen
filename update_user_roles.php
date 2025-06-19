<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$userId = $data['userId'] ?? null;
$firstName = $data['firstName'] ?? '';
$lastName = $data['lastName'] ?? '';
$email = $data['email'] ?? '';
$title = $data['title'] ?? '';

if (!$userId || empty($firstName) || empty($lastName) || empty($email)) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // ✅ Include isParent
    $roles = [
        'isAdmin', 'isTeacher', 'isExaminer',
        'isPrincipal', 'isCoordinator', 'isSupervisor',
        'isVolunteer', 'isSummerCampTeacher', 'isParent' // ✅ Added
    ];

    $setParts = [
        "firstName = ?",
        "lastName = ?",
        "email = ?",
        "title = ?"
    ];

    $params = [$firstName, $lastName, $email, $title];

    foreach ($roles as $role) {
        $setParts[] = "$role = ?";
        $params[] = isset($data[$role]) && $data[$role] == 1 ? 1 : 0;
    }

    $params[] = $userId;

    $sql = "UPDATE users SET " . implode(", ", $setParts) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // ✅ Sync summer_camp_teachers table
    $isCamp = isset($data['isSummerCampTeacher']) && $data['isSummerCampTeacher'] == 1;

    if ($isCamp) {
        $check = $pdo->prepare("SELECT * FROM summer_camp_teachers WHERE user_id = ?");
        $check->execute([$userId]);

        if ($check->rowCount() === 0) {
            $insert = $pdo->prepare("INSERT INTO summer_camp_teachers (user_id, first_name, last_name, email) VALUES (?, ?, ?, ?)");
            $insert->execute([$userId, $firstName, $lastName, $email]);
        } else {
            $update = $pdo->prepare("UPDATE summer_camp_teachers SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?");
            $update->execute([$firstName, $lastName, $email, $userId]);
        }
    } else {
        $pdo->prepare("DELETE FROM summer_camp_teachers WHERE user_id = ?")->execute([$userId]);
    }

    // ✅ Auto-link parent to registration_requests if applicable
    $isParent = isset($data['isParent']) && $data['isParent'] == 1;
    if ($isParent) {
        $linkStmt = $pdo->prepare("UPDATE registration_requests SET parent_user_id = ? WHERE parent1_email = ?");
        $linkStmt->execute([$userId, $email]);
    }

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
