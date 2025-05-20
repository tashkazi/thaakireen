<?php
header('Content-Type: application/json');

$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$data = json_decode(file_get_contents("php://input"), true);

try {
    $stmt = $pdo->prepare("
        UPDATE users SET
            status = :status,
            activated_at = :activated_at,
            exited_at = :exited_at,
            notes = :notes
        WHERE id = :user_id
    ");
    $stmt->execute([
        ':status' => $data['status'] ?? null,
        ':activated_at' => $data['activated_at'] ?: null,
        ':exited_at' => $data['exited_at'] ?: null,
        ':notes' => $data['notes'] ?? '',
        ':user_id' => $data['user_id']
    ]);

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
