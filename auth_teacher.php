<?php
session_start();
header('Content-Type: application/json');

// ✅ Only allow users who are logged in and marked as teacher
if (!isset($_SESSION['user_id']) || empty($_SESSION['isTeacher'])) {
  echo json_encode(['success' => false, 'message' => 'Access denied. Teachers only.']);
  exit;
}

$teacher_id = $_SESSION['user_id'];
