<?php
// Page to manage leave types and quotas
session_start();
require_once("../includes/db.php");
// FIX: Allow both admin and hr_manager
if (!isset($_SESSION['employee_id']) || !in_array($_SESSION['role'], ['admin', 'hr_manager'])) {
    header("Location: ../login.php");
    exit();
}

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_type'], $_POST['new_quota'])) {
        $stmt = $pdo->prepare("INSERT INTO leave_types (type, quota) VALUES (?, ?)");
        $stmt->execute([$_POST['new_type'], $_POST['new_quota']]);
    }
    if (isset($_POST['edit_id'], $_POST['edit_type'], $_POST['edit_quota'])) {
        $stmt = $pdo->prepare("UPDATE leave_types SET type=?, quota=? WHERE id=?");
        $stmt->execute([$_POST['edit_type'], $_POST['edit_quota'], $_POST['edit_id']]);
    }
    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM leave_types WHERE id=?");
        $stmt->execute([$_POST['delete_id']]);
    }
}
// Fetch all leave types
$stmt = $pdo->query("SELECT * FROM leave_types ORDER BY type ASC");
$leave_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Types Admin - HR Manager | ViaHale TNVS HR3</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        body { font-family: 'QuickSand', 'Poppins', Arial, sans-serif; background: #fafbfc; color: #22223b; font-size: 16px; }
        .sidebar { background: #181818ff; color: #fff; min-height: 100vh; border: none; width: 220px; position: fixed; left: 0; top: 0; z-index: 1040; transition: left 0.3s; overflow-y: auto; padding: 1rem 0.3rem 1rem 0.3rem; scrollbar-width: none; height: 100vh; -ms-overflow-style: none; }
        .sidebar::-webkit-scrollbar { display: none; width: 0px; background: transparent; }
        .sidebar a, .sidebar button { color: #bfc7d1; background: none; border: none; font-size: 0.95rem; padding: 0.45rem 0.7rem; border-radius: 8px; display: flex; align-items: center; gap: 0.7rem; margin-bottom: 0.1rem; transition: background 0.2s, color 0.2s; width: 100%; text-align: left; white-space: nowrap; }
        .sidebar a.active, .sidebar a:hover, .sidebar button.active, .sidebar button:hover { background: linear-gradient(90deg, #9A66ff 0%, #4311a5 100%); color: #fff; }
        .sidebar hr { border-top: 1px solid #232a43; margin: 0.7rem 0; }
        .sidebar .nav-link ion-icon { font-size: 1.2rem; margin-right: 0.3rem; }
        .topbar { padding: 0.7rem 1.2rem 0.7rem 1.2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 0 !important; }
        .topbar .profile { display: flex; align-items: center; gap: 1.2rem; }
        .topbar .profile-img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; margin-right: 0.7rem; border: 2px solid #e0e7ff; }
        .topbar .profile-info { line-height: 1.1; }
        .topbar .profile-info strong { font-size: 1.08rem; font-weight: 600; color: #22223b; }
        .topbar .profile-info small { color: #6c757d; font-size: 0.93rem; }
        .dashboard-title { font-family: 'QuickSand', 'Poppins', Arial, sans-serif; font-size: 1.7rem; font-weight: 700; margin-bottom: 1.2rem; color: #22223b; }
        .breadcrumbs { color: #9A66ff; font-size: 0.98rem; text-align: right; }
        .dashboard-col { background: #fff; border-radius: 18px; box-shadow: 0 2px 8px rgba(140,140,200,0.07); padding: 2rem 1.2rem 1rem 1.2rem; margin: 2rem auto 1rem auto; max-width: 600px; border: 1px solid #f0f0f0; }
        .leave-types-table th, .leave-types-table td { vertical-align: middle !important; }
        .leave-types-table th { background: #f9f9fc; color: #4311a5; }
        @media (max-width: 900px) { .sidebar { left: -220px; width: 180px; padding: 1rem 0.3rem; } .sidebar.show { left: 0; } .main-content { margin-left: 0; padding: 1rem 0.5rem 1rem 0.5rem; } }
        @media (max-width: 700px) { .dashboard-title { font-size: 1.1rem; } .main-content { padding: 0.7rem 0.2rem 0.7rem 0.2rem; } .sidebar { width: 100vw; left: -100vw; padding: 0.7rem 0.2rem; } .sidebar.show { left: 0; } .main-content { padding: 0.3rem 0.1rem; } }
        @media (max-width: 500px) { .sidebar { width: 100vw; left: -100vw; padding: 0.3rem 0.01rem; } .sidebar.show { left: 0; } .main-content { padding: 0.1rem 0.01rem; } }
        @media (min-width: 1400px) { .sidebar { width: 260px; padding: 2rem 1rem 2rem 1rem; } .main-content { margin-left: 260px; padding: 2rem 2rem 2rem 2rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
  <div class="row g-0">
    <div class="sidenav col-auto p-0">
      <div class="sidebar d-flex flex-column justify-content-between shadow-sm border-end">
        <div>
          <div class="d-flex justify-content-center align-items-center mb-5 mt-3">
            <img src="../assets/images/image.png" class="img-fluid me-2" style="height: 55px;" alt="Logo">
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase mb-2">Dashboard</h6>
            <nav class="nav flex-column">
              <a class="nav-link active" href="#"><ion-icon name="home-outline"></ion-icon>Dashboard</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Time & Attendance</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="#"><ion-icon name="timer-outline"></ion-icon>Employee Clock In/Out</a>
              <a class="nav-link" href="#"><ion-icon name="list-outline"></ion-icon>Attendance Logs</a>
              <a class="nav-link" href="#"><ion-icon name="stats-chart-outline"></ion-icon>Attendance Statistics</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Timesheet Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="#"><ion-icon name="create-outline"></ion-icon>Timesheet Submission</a>
              <a class="nav-link" href="#"><ion-icon name="checkmark-done-outline"></ion-icon>Timesheet Review & Approval</a>
              <a class="nav-link" href="#"><ion-icon name="archive-outline"></ion-icon>Timesheet Archive</a>
              <a class="nav-link" href="#"><ion-icon name="document-text-outline"></ion-icon>Timesheet Reports</a>
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
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Leave Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="#"><ion-icon name="calendar-outline"></ion-icon>Leave Requests</a>
              <a class="nav-link" href="../admin/leave_types.php"><ion-icon name="settings-outline"></ion-icon>Types of leave</a>
              <a class="nav-link" href="#"><ion-icon name="checkmark-done-outline"></ion-icon>Leave Approval</a>
              <a class="nav-link" href="#"><ion-icon name="calendar-outline"></ion-icon>Leave Balance</a>
              <a class="nav-link" href="#"><ion-icon name="calendar-outline"></ion-icon>Leave History</a>
              <a class="nav-link" href="#"><ion-icon name="calendar-outline"></ion-icon>Absence Calendar</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Claims & Reimbursement</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="#"><ion-icon name="create-outline"></ion-icon>Claim Submission</a>
              <a class="nav-link" href="#"><ion-icon name="cash-outline"></ion-icon>Pending Claims</a>
              <a class="nav-link" href="#"><ion-icon name="checkmark-done-outline"></ion-icon>Processed Claims</a>
              <a class="nav-link" href="#"><ion-icon name="alert-circle-outline"></ion-icon>Flagged Claims</a>
              <a class="nav-link" href="#"><ion-icon name="settings-outline"></ion-icon>Reimbursement Policies</a>
              <a class="nav-link" href="#"><ion-icon name="document-text-outline"></ion-icon>Audit & Reports</a>
              <a class="nav-link" href="#"><ion-icon name="alert-circle-outline"></ion-icon>Escalated Claims</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Administrative / User Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="#"><ion-icon name="people-circle-outline"></ion-icon>User Management</a>
              <a class="nav-link" href="#"><ion-icon name="person-add-outline"></ion-icon>Role Assignment</a>
              <a class="nav-link" href="#"><ion-icon name="cog-outline"></ion-icon>System Settings</a>
              <a class="nav-link" href="#"><ion-icon name="list-outline"></ion-icon>Audit Logs</a>
              <a class="nav-link" href="#"><ion-icon name="stats-chart-outline"></ion-icon>General Reports</a>
            </nav>
          </div>
        </div>
        <div class="p-3 border-top mb-2">
          <a class="nav-link text-danger" href="../logout.php">
            <ion-icon name="log-out-outline"></ion-icon>Logout
        </div>
      </div>
    </div>
    <div class="main-content col" style="margin-left:220px;">
      <div class="topbar">
        <span class="dashboard-title">Leave Types Admin</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($_SESSION['fullname']) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?></small>
          </div>
        </div>
      </div>
      <div class="breadcrumbs text-end mb-2">Dashboard &gt; Leave Management &gt; Leave Types Admin</div>
      <div class="dashboard-col">
        <form method="post" class="row g-2 mb-4">
          <div class="col-auto">
            <input type="text" name="new_type" class="form-control" placeholder="New Leave Type" required>
          </div>
          <div class="col-auto">
            <input type="number" name="new_quota" class="form-control" placeholder="Annual Quota" min="0" required>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-success">Add</button>
          </div>
        </form>
        <table class="table table-bordered leave-types-table table-striped">
          <thead>
            <tr>
              <th>Type</th>
              <th>Quota</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($leave_types as $lt): ?>
            <tr>
              <form method="post">
                <td>
                  <input type="text" name="edit_type" value="<?= htmlspecialchars($lt['type']) ?>" class="form-control" required>
                </td>
                <td>
                  <input type="number" name="edit_quota" value="<?= $lt['quota'] ?>" class="form-control" min="0" required>
                </td>
                <td>
                  <input type="hidden" name="edit_id" value="<?= $lt['id'] ?>">
                  <button type="submit" class="btn btn-primary btn-sm">Save</button>
              </form>
              <form method="post" style="display:inline;">
                  <input type="hidden" name="delete_id" value="<?= $lt['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this leave type?')">Delete</button>
              </form>
                </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($leave_types)): ?>
            <tr><td colspan="3" class="text-center">No leave types defined.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>