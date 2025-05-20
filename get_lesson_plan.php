<?php
session_start();
header('Content-Type: application/json');

// ✅ Allow access to all logged-in users
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Access denied. Login required.']);
  exit;
}

$user_id = $_SESSION['user_id'];
$subject = $_GET['subject'] ?? '';
$date = $_GET['date'] ?? '';

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'Database error']);
  exit;
}

// ✅ Look up teacher_id for this user (even if user is Admin/Examiner too)
$teacherResult = $conn->query("SELECT teacher_id FROM teachers WHERE user_id = $user_id");
if ($teacherResult->num_rows === 0) {
  echo json_encode(['success' => false, 'message' => 'You are not assigned as a teacher.']);
  exit;
}
$teacher_id = $teacherResult->fetch_assoc()['teacher_id'];

// ✅ Load the lesson plan for this teacher
$stmt = $conn->prepare("
  SELECT grade, topic, objectives, materials, hook, structure, closure
  FROM single_lesson_plans
  WHERE teacher_id = ? AND subject = ? AND date = ?
");
$stmt->bind_param("iss", $teacher_id, $subject, $date);
$stmt->execute();
$result = $stmt->get_result();
$lesson = $result->fetch_assoc();

echo $lesson
  ? json_encode(['success' => true, 'lesson' => $lesson])
  : json_encode(['success' => false, 'message' => 'Not found']);
