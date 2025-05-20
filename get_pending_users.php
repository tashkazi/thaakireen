
<?php
header('Content-Type: application/json');

// Database connection
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

    // Fetch pending users
    $stmt = $pdo->query("SELECT id, firstName, lastName, email FROM users WHERE approval_status = 'pending'");
    $users = $stmt->fetchAll();

    echo json_encode(["users" => $users]);

} catch (PDOException $e) {
    echo json_encode([
        "users" => [],
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
?>
