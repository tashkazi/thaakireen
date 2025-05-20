<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Access denied"]);
    exit;
}

$type = $_POST['type'] ?? '';
$allowedTypes = ['planner', 'syllabus', 'activity'];

if (!in_array($type, $allowedTypes)) {
    echo json_encode(["success" => false, "message" => "Invalid resource type"]);
    exit;
}

if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "message" => "File upload error"]);
    exit;
}

$targetDir = "uploads/";
$filename = basename($_FILES["file"]["name"]);
$timestampedFilename = time() . "_" . $filename;
$targetFile = $targetDir . $timestampedFilename;

if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
    echo json_encode(["success" => false, "message" => "Failed to move uploaded file"]);
    exit;
}

// Save resource metadata in DB
$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO teacher_resources (title, file_path, file_type) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $filename, $targetFile, $type);
$success = $stmt->execute();

if ($success) {
    echo json_encode(["success" => true, "message" => "File uploaded successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "DB insert failed"]);
}

$stmt->close();
$conn->close();
