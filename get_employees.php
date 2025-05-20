<?php
header('Content-Type: application/json');

$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

try {
    $query = $pdo->query("
        SELECT 
            u.id AS user_id,
            CONCAT(u.firstName, ' ', u.lastName) AS full_name,
            u.email,
            u.phone,
            u.status,
            u.activated_at,
            u.exited_at,
            u.notes,
            CASE
                WHEN u.isAdmin = 1 THEN 'Admin'
                WHEN u.isPrincipal = 1 THEN 'Principal'
                WHEN u.isCoordinator = 1 THEN 'Coordinator'
                WHEN u.isSupervisor = 1 THEN 'Supervisor'
                WHEN u.isExaminer = 1 THEN 'Examiner'
                WHEN u.isTeacher = 1 THEN 'Teacher'
                WHEN u.isVolunteer = 1 THEN 'Volunteer'
                ELSE 'Staff'
            END AS type
        FROM users u
        WHERE u.isApproved = 1
    ");
    
    $employees = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "employees" => $employees]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
