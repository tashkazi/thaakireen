<?php
session_start();
header('Content-Type: application/json');

$isAdmin = !empty($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1;

echo json_encode(['isAdmin' => $isAdmin]);
?>
