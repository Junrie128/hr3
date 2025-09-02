<?php
session_start();
require_once("../includes/db.php");

$fullname = $_SESSION['fullname'] ?? 'Schedule Officer';
$role = $_SESSION['role'] ?? 'schedule_officer';

// Handle form submission
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role_emp = $_POST['role'] ?? 'employee';
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($name && $username && $email && $password) {
        $stmt = $pdo->prepare("INSERT INTO employees (fullname, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT), $role_emp]);
        $msg = "Employee added successfully!";
    } else {
        $msg = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Employee - Schedule Officer | ViaHale TNVS HR3</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        body { font-family: 'QuickSand', 'Poppins', Arial, sans-serif; background: #fafbfc; color: #22223b; font-size: 16px; }
        .sidebar { background: #181818ff; color: #fff; min-height: 100vh; border: none; width: 220px; position: fixed; left: 0; top: 0; z-index: 1040; transition: left 0.3s; overflow-y: auto; padding: 1rem 0.3rem 1rem 0.3rem; scrollbar-width: none; }
        .sidebar::-webkit-scrollbar { display: none; width: 0px; background: transparent; }
        .sidebar a, .sidebar button { color: #bfc7d1; background: none; border: none; font-size: 0.95rem; padding: 0.45rem 0.7rem; border-radius: 8px; display: flex; align-items: center; gap: 0.7rem; margin-bottom: 0.1rem; transition: background 0.2s, color 0.2s; width: 100%; text-align: left; }
        .sidebar a.active, .sidebar a:hover, .sidebar button.active, .sidebar button:hover { background: linear-gradient(90deg, #9A66ff 0%, #4311a5 100%); color: #fff; }
        .topbar { padding: 0.7rem 1.2rem 0.7rem 1.2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .dashboard-title { font-family: 'QuickSand', 'Poppins', Arial, sans-serif; font-size: 1.7rem; font-weight: 700; margin-bottom: 1.2rem; color: #22223b; }
        .breadcrumbs { color: #9A66ff; font-size: 0.98rem; text-align: right; }
        .profile { display: flex; align-items: center; gap: 1.2rem; }
        .profile-img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; margin-right: 0.7rem; border: 2px solid #e0e7ff; }
        .profile-info strong { font-size: 1.08rem; font-weight: 600; color: #22223b; }
        .profile-info small { color: #6c757d; font-size: 0.93rem; }
        .dashboard-col { background: #fff; border-radius: 18px; box-shadow: 0 2px 8px rgba(140,140,200,0.07); padding: 2rem 1.2rem 1rem 1.2rem; margin: 2rem auto 1rem auto; max-width: 500px; border: 1px solid #f0f0f0; }
        label { font-weight: 500; margin-top:0.7rem; }
        .form-control, .form-select { border-radius: 8px; font-size: 1.05rem; }
        .btn-primary { background: linear-gradient(90deg, #9A66ff 0%, #4311a5 100%); border: none; }
        .alert-info { border-radius: 10px; }
        @media (max-width:900px) { .dashboard-col { padding: 1rem 0.2rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
  <div class="row g-0">
    <div class="sidenav col-auto p-0">
      <!-- Sidebar START -->
      <div class="sidebar d-flex flex-column justify-content-between shadow-sm border-end">
        <div>
          <div class="d-flex justify-content-center align-items-center mb-5 mt-3">
            <img src="../assets/images/image.png" class="img-fluid me-2" style="height: 55px;" alt="Logo">
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase mb-2">Dashboard</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="schedule_officer_dashboard.php"><ion-icon name="home-outline"></ion-icon>Dashboard</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Employee Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link active" href="add_employee.php"><ion-icon name="person-add-outline"></ion-icon>Add Employee</a>
              <a class="nav-link" href="employee_list.php"><ion-icon name="people-outline"></ion-icon>Employee List</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Shift & Schedule Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="#"><ion-icon name="calendar-outline"></ion-icon>Shift Scheduling</a>
              <a class="nav-link" href="#"><ion-icon name="pencil-outline"></ion-icon>Edit/Update Schedules</a>
              <a class="nav-link" href="#"><ion-icon name="swap-horizontal-outline"></ion-icon>Shift Swap Requests</a>
              <a class="nav-link" href="#"><ion-icon name="people-outline"></ion-icon>Employee Availability</a>
              <a class="nav-link" href="#"><ion-icon name="reader-outline"></ion-icon>Schedule Logs</a>
              <a class="nav-link" href="#"><ion-icon name="calendar-outline"></ion-icon>Company Calendar</a>
              <a class="nav-link" href="#"><ion-icon name="document-text-outline"></ion-icon>Schedule Reports</a>
              <a class="nav-link" href="#"><ion-icon name="settings-outline"></ion-icon>Scheduling Rules/Policies</a>
            </nav>
          </div>
        </div>
        <div class="p-3 border-top mb-2">
          <a class="nav-link text-danger" href="../logout.php"><ion-icon name="log-out-outline"></ion-icon>Logout</a>
        </div>
      </div>
      <!-- Sidebar END -->
    </div>
    <div class="main-content col" style="margin-left:220px;">
      <div class="topbar">
        <span class="dashboard-title">Add Employee</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      
      <div class="dashboard-col">
        <?php if ($msg): ?>
          <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <form method="post">
          <label for="fullname">Full Name</label>
          <input type="text" class="form-control" id="fullname" name="fullname" required>
          <label for="username">Username</label>
          <input type="text" class="form-control" id="username" name="username" required>
          <label for="role">Role</label>
          <select class="form-select" id="role" name="role">
            <option value="employee">Employee</option>
            <option value="schedule_officer">Schedule Officer</option>
            <option value="hr_manager">HR Manager</option>
            <option value="admin">Admin</option>
          </select>
          <label for="email">Email</label>
          <input type="email" class="form-control" id="email" name="email" required>
          <label for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
          <button type="submit" class="btn btn-primary mt-3">Add Employee</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>