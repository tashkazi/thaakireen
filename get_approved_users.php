<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->query("
        SELECT 
            id, 
            firstName, 
            lastName, 
            title,            -- ✅ include title
            email, 
            isAdmin, 
            isTeacher, 
            isExaminer, 
            isPrincipal, 
            isCoordinator, 
            isSupervisor, 
            isVolunteer,
            isSummerCampTeacher
        FROM users 
        WHERE approval_status = 'approved'
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["users" => $users]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB error: " . $e->getMessage()]);
}
