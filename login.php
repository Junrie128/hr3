<?php
session_start();
require_once "includes/db.php";
$error = '';

if (isset($_SESSION['employee_id'])) {
    // Redirect user based on stored role
    switch ($_SESSION['role'] ?? '') {
        case 'hr_manager':
            header("Location: /manager_dashboard.php");
            break;
        case 'schedule_officer':
            header("Location: /schedule_officer_dashboard.php");
            break;
        case 'benefits_officer':
            header("Location: /benefits_officer_dashboard.php");
            break;
        case 'admin':
            header("Location: /admin_dashboard.php");
            break;
        default:
            header("Location: /employee/employee_dashboard.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['employee_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        switch ($user['role']) {
            case 'hr_manager':
                header("Location: ./manager/manager_dashboard.php");
                break;
            case 'schedule_officer':
                header("Location: ./scheduler/schedule_officer_dashboard.php");
                break;
            case 'benefits_officer':
                header("Location: ./benefits/benefits_officer_dashboard.php");
                break;
            case 'admin':
                header("Location: ./admin/admin_dashboard.php");
                break;
            default:
                header("Location: ./employee/employee_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - ViaHale TNVS HR3</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fafbfc; font-family: 'QuickSand', 'Poppins', Arial, sans-serif; }
        .login-container { max-width: 400px; margin: 6% auto 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 16px rgba(100,80,170,0.08); padding: 2.5rem 2rem 2rem 2rem; }
        .login-logo { text-align: center; margin-bottom: 1.2rem; }
        .login-logo img { height: 60px; }
        .login-title { font-weight: 700; font-size: 1.5rem; color: #4311a5; text-align: center; margin-bottom: 1rem; }
        .form-label { font-weight: 600; color: #444; }
        .form-control { border-radius: 10px; }
        .btn-primary { background: linear-gradient(90deg, #9A66ff 0%, #4311a5 100%); border: none; border-radius: 10px; font-weight: 600; }
        .btn-primary:hover { background: linear-gradient(90deg, #4311a5 0%, #9A66ff 100%); }
        .auth-link { display: block; text-align: center; margin-top: 1rem; color: #4311a5; text-decoration: none; }
        .auth-link:hover { text-decoration: underline; color: #9A66ff; }
        .alert { font-size: 0.97rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="assets/images/image.png" alt="Logo">
        </div>
        <div class="login-title">ViaHale TNVS HR3 Login</div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input autocomplete="username" type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input autocomplete="current-password" type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2">Login</button>
        </form>
        <a href="register.php" class="auth-link">Don't have an account? Register here</a>
    </div>
</body>
</html>