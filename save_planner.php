<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Not logged in"]);
  exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$weekStart = $data['week_start'] ?? '';
$planner = json_encode($data['planner'] ?? []);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(["success" => false]);
  exit;
}

$stmt = $conn->prepare("INSERT INTO weekly_planner (user_id, week_start, planner_json)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE planner_json = ?");
$stmt->bind_param("isss", $user_id, $weekStart, $planner, $planner);
$success = $stmt->execute();

echo json_encode(["success" => $success]);
