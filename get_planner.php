<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["success" => false, "message" => "Not logged in"]);
  exit;
}

$user_id = $_SESSION['user_id'];
$weekStart = $_GET['week_start'] ?? '';

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(["success" => false]);
  exit;
}

$stmt = $conn->prepare("SELECT planner_json FROM weekly_planner WHERE user_id = ? AND week_start = ?");
$stmt->bind_param("is", $user_id, $weekStart);
$stmt->execute();
$stmt->bind_result($json);
$stmt->fetch();

if ($json) {
  echo json_encode(["success" => true, "planner" => json_decode($json, true)]);
} else {
  echo json_encode(["success" => false]);
}
