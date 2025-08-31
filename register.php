<?php
session_start();
require_once "includes/db.php";
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = strtolower(trim($_POST['role'] ?? 'employee')); // Role from form

    $allowed_roles = ['employee', 'hr_manager', 'schedule_officer', 'benefits_officer', 'admin'];
    if (!$fullname || !$username || !$password) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!in_array($role, $allowed_roles)) {
        $error = "Invalid role selected.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already taken.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO employees (fullname, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullname, $username, $hash, $role]);
            $success = "Registration successful! You can now <a href='login.php'>login</a>.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - ViaHale TNVS HR3</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fafbfc; font-family: 'QuickSand', 'Poppins', Arial, sans-serif; }
        .register-container { max-width: 400px; margin: 5% auto 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 16px rgba(100,80,170,0.08); padding: 2.5rem 2rem 2rem 2rem; }
        .register-logo { text-align: center; margin-bottom: 1.2rem; }
        .register-logo img { height: 60px; }
        .register-title { font-weight: 700; font-size: 1.5rem; color: #4311a5; text-align: center; margin-bottom: 1rem; }
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
    <div class="register-container">
        <div class="register-logo">
            <img src="assets/images/image.png" alt="Logo">
        </div>
        <div class="register-title">ViaHale TNVS HR3 Registration</div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label for="fullname" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullname" name="fullname" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input autocomplete="username" type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input autocomplete="new-password" type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input autocomplete="new-password" type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="employee">Employee</option>
                    <option value="hr_manager">HR Manager</option>
                    <option value="schedule_officer">Schedule Officer</option>
                    <option value="benefits_officer">Benefits Officer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2">Register</button>
        </form>
        <a href="login.php" class="auth-link">Already have an account? Login here</a>
    </div>
</body>
</html>pl