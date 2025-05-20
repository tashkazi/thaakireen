<?php
$host = 'localhost';
$db   = 'thaakireen';
$user = 'root';
$pass = 'Tashreeka94_';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $firstName  = trim($_POST['firstName'] ?? '');
        $lastName   = trim($_POST['lastName'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');
        $password   = $_POST['password'] ?? '';

        // Validate required fields
        if (!$firstName || !$lastName || !$email || !$password) {
            echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
            exit;
        }

        // Check if email is already registered
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->rowCount() > 0) {
            echo "<script>alert('This email is already registered. Please log in instead.'); window.location.href='login.html';</script>";
            exit;
        }

        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                firstName, lastName, email, phone, notes, password,
                isApproved, isTeacher, isAdmin, isExaminer, isPrincipal, isVolunteer, createdAt
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, NOW())
        ");
        $stmt->execute([
            $firstName, $lastName, $email, $phone, $notes, $hashedPassword
        ]);

        echo "<script>
            alert('Registration successful! You can log in once your profile is approved by the admin.');
            window.location.href = 'login.html';
        </script>";
    }
} catch (PDOException $e) {
    $error = htmlspecialchars($e->getMessage(), ENT_QUOTES);
    echo "<script>alert('Registration error: {$error}'); window.history.back();</script>";
}
