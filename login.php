<?php
session_start();

$pdo = new PDO("mysql:host=localhost;dbname=thaakireen", "root", "Tashreeka94_", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_var($_POST["email"] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"] ?? '';

    // Find user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ((int)$user['isApproved'] === 1) {
            // Store session details
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['firstName'] . ' ' . $user['lastName'];
            $_SESSION['userName'] = $_SESSION['name']; // ✅ For audit logs

            // Store all role flags
            $_SESSION['isAdmin']              = (int)($user['isAdmin'] ?? 0);
            $_SESSION['isTeacher']            = (int)($user['isTeacher'] ?? 0);
            $_SESSION['isPrincipal']          = (int)($user['isPrincipal'] ?? 0);
            $_SESSION['isCoordinator']        = (int)($user['isCoordinator'] ?? 0);
            $_SESSION['isSupervisor']         = (int)($user['isSupervisor'] ?? 0);
            $_SESSION['isExaminer']           = (int)($user['isExaminer'] ?? 0);
            $_SESSION['isVolunteer']          = (int)($user['isVolunteer'] ?? 0);
            $_SESSION['isSummerCampTeacher']  = (int)($user['isSummerCampTeacher'] ?? 0);

            // Store teacher_id from teachers table
            if ($_SESSION['isTeacher']) {
                $stmt2 = $pdo->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
                $stmt2->execute([$user['id']]);
                $teacher = $stmt2->fetch(PDO::FETCH_ASSOC);
                $_SESSION['teacher_id'] = $teacher['teacher_id'] ?? null;
            }

            // Store summer_camp_teacher_id from summer_camp_teachers table
            if ($_SESSION['isSummerCampTeacher']) {
                $stmt3 = $pdo->prepare("SELECT id FROM summer_camp_teachers WHERE user_id = ?");
                $stmt3->execute([$user['id']]);
                $campTeacher = $stmt3->fetch(PDO::FETCH_ASSOC);
                $_SESSION['summer_camp_teacher_id'] = $campTeacher['id'] ?? null;
            }

            // Optional: label user type for UI
            $_SESSION['userType'] = 'user';

            // ✅ Add human-readable role for audit logging
            if ($_SESSION['isAdmin']) {
                $_SESSION['role'] = 'Admin';
            } elseif ($_SESSION['isExaminer']) {
                $_SESSION['role'] = 'Examiner';
            } elseif ($_SESSION['isTeacher']) {
                $_SESSION['role'] = 'Teacher';
            } elseif ($_SESSION['isSummerCampTeacher']) {
                $_SESSION['role'] = 'Summer Camp Teacher';
            } elseif ($_SESSION['isSupervisor']) {
                $_SESSION['role'] = 'Supervisor';
            } elseif ($_SESSION['isCoordinator']) {
                $_SESSION['role'] = 'Coordinator';
            } elseif ($_SESSION['isPrincipal']) {
                $_SESSION['role'] = 'Principal';
            } elseif ($_SESSION['isVolunteer']) {
                $_SESSION['role'] = 'Volunteer';
            } else {
                $_SESSION['role'] = 'User';
            }

            // Redirect to homepage
            if (!headers_sent()) {
                header("Location: Homepage.html");
            } else {
                echo "<script>window.location.href='Homepage.html';</script>";
            }
            exit();
        } else {
            echo "<script>alert('Your account is not yet approved. Please wait for admin approval.'); window.location.href='login.html';</script>";
        }
    } else {
        echo "<script>alert('Invalid email or password.'); window.location.href='login.html';</script>";
    }
}
?>
