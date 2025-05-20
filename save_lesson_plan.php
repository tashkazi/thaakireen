<?php
session_start();
header('Content-Type: application/json');

// ✅ Allow all logged-in users
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Access denied. Login required.']);
  exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

// ✅ Validate input
if (!$data || !isset($data['date'], $data['subject'], $data['grade'])) {
  echo json_encode(['success' => false, 'message' => 'Missing required data']);
  exit;
}

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

// ✅ Get teacher_id for this user
$result = $conn->query("SELECT teacher_id FROM teachers WHERE user_id = $user_id");
if ($result->num_rows === 0) {
  echo json_encode(['success' => false, 'message' => 'You are not assigned as a teacher.']);
  exit;
}
$teacher_id = $result->fetch_assoc()['teacher_id'];

// ✅ Insert or update the lesson plan
$stmt = $conn->prepare("
  INSERT INTO single_lesson_plans
    (teacher_id, date, subject, grade, topic, objectives, materials, hook, structure, closure)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
    grade = VALUES(grade),
    topic = VALUES(topic),
    objectives = VALUES(objectives),
    materials = VALUES(materials),
    hook = VALUES(hook),
    structure = VALUES(structure),
    closure = VALUES(closure)
");

if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
  exit;
}

$stmt->bind_param("isssssssss",
  $teacher_id,
  $data['date'],
  $data['subject'],
  $data['grade'],
  $data['topic'],
  $data['objectives'],
  $data['materials'],
  $data['hook'],
  $data['structure'],
  $data['closure']
);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}
