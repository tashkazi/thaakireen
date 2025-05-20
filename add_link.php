<?php
session_start();
if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

$title = trim($_POST['title']);
$url = trim($_POST['url']);

$conn = new mysqli("localhost", "root", "Tashreeka94_", "thaakireen");
$stmt = $conn->prepare("INSERT INTO teacher_resources (title, file_path, file_type) VALUES (?, ?, 'link')");
$stmt->bind_param("ss", $title, $url);
$stmt->execute();
echo "Link added successfully!";
