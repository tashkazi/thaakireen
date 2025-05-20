<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
  echo json_encode(['success' => false, 'message' => 'Access denied. Teachers only.']);
  exit;
}

$teacher_id = $_SESSION['user_id'];
?>
