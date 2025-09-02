<?php
session_start();
require_once("../includes/db.php");

if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'hr_manager') {
    header("Location: ../login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'HR Manager';
$role = $_SESSION['role'] ?? 'hr_manager';

// For dynamic leave types:
$types_stmt = $pdo->query("SELECT type, quota FROM leave_types ORDER BY type ASC");
$leave_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter by employee name (optional)
$search_emp = $_GET['employee'] ?? '';
$where_sql = '';
$params = [];
if ($search_emp) {
    $where_sql = 'WHERE e.fullname LIKE ?';
    $params[] = "%$search_emp%";
}

// Get all employees
$stmt = $pdo->prepare("SELECT e.id, e.fullname FROM employees e $where_sql ORDER BY e.fullname ASC");
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build leave balances for each employee
$balances = [];
foreach ($employees as $emp) {
    $emp_id = $emp['id'];
    $emp_name = $emp['fullname'];
    $emp_bal = [];
    foreach ($leave_types as $lt) {
        $type = $lt['type']; $quota = $lt['quota'];
        $stmt2 = $pdo->prepare("SELECT date_from, date_to FROM leave_requests WHERE employee_id = ? AND leave_type = ? AND status = 'approved' AND YEAR(date_from) = YEAR(CURDATE())");
        $stmt2->execute([$emp_id, $type]);
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $used = 0;
        foreach ($rows as $row) {
            $days = (strtotime($row['date_to']) - strtotime($row['date_from'])) / 86400 + 1;
            $used += $days > 0 ? $days : 1;
        }
        $emp_bal[$type] = ['used' => $used, 'quota' => $quota];
    }
    $balances[] = ['id' => $emp_id, 'fullname' => $emp_name, 'leaves' => $emp_bal];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Balance - HR Manager | ViaHale TNVS HR3</title>
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
        .dashboard-col { background: #fff; border-radius: 18px; box-shadow: 0 2px 8px rgba(140,140,200,0.07); padding: 2rem 1.2rem 1rem 1.2rem; margin: 2rem auto 1rem auto; max-width: 1100px; border: 1px solid #f0f0f0; }
        .leave-bal-table th, .leave-bal-table td { vertical-align: middle !important; }
        .leave-bal-table th { background: #f9f9fc; color: #4311a5; }
        .balance-bar { height: 16px; background: #e0e7ff; border-radius: 8px; overflow: hidden; margin-top: 4px; }
        .balance-bar-inner { height: 100%; background: linear-gradient(90deg,#36a2eb 0%, #4311a5 100%); border-radius: 8px; }
        .badge.bg-info { background: #9A66ff !important; color: #fff; }
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
              <a class="nav-link" href="#"><ion-icon name="person-outline"></ion-icon>Employee Directory</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Timesheet Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="../manager/timesheet_review.php"><ion-icon name="checkmark-done-outline"></ion-icon>Timesheet Review & Approval</a>
              <a class="nav-link" href="../manager/timesheet_reports.php"><ion-icon name="document-text-outline"></ion-icon>Timesheet Reports</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Leave Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="../manager/leave_requests.php"><ion-icon name="calendar-outline"></ion-icon>Leave Requests</a>
              <a class="nav-link" href="../manager/leave_balance.php"><ion-icon name="calendar-outline"></ion-icon>Leave Balance</a>
              <a class="nav-link" href="../manager/leave_history.php"><ion-icon name="calendar-outline"></ion-icon>Leave History</a>
              <a class="nav-link" href="../manager/leave_types.php"><ion-icon name="settings-outline"></ion-icon>Types of leave</a>
              <a class="nav-link" href="../manager/leave_calendar.php"><ion-icon name="calendar-outline"></ion-icon>Leave Calendar</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Claims & Reimbursement</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="#"><ion-icon name="alert-circle-outline"></ion-icon>Escalated Claims</a>
              <a class="nav-link" href="#"><ion-icon name="document-text-outline"></ion-icon>Audit & Reports</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Shift & Schedule Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="#"><ion-icon name="calendar-outline"></ion-icon>View Schedules</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Policy & Reports</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="#"><ion-icon name="settings-outline"></ion-icon>Policy Management</a>
              <a class="nav-link" href="#"><ion-icon name="stats-chart-outline"></ion-icon>General Reports</a>
            </nav>
          </div>
        </div>
        <div class="p-3 border-top mb-2">
          <a class="nav-link text-danger" href="../logout.php">
            <ion-icon name="log-out-outline"></ion-icon>Logout
          </a>
        </div>
      </div>
    </div>
    <div class="main-content col" style="margin-left:220px;">
      <div class="topbar">
        <span class="dashboard-title">Leave Balance</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      
      <div class="dashboard-col">
        <form method="get" class="mb-3">
          <div class="input-group" style="max-width:330px;">
            <input type="text" class="form-control" name="employee" value="<?= htmlspecialchars($search_emp) ?>" placeholder="Search employee name...">
            <button class="btn btn-primary" type="submit">Search</button>
          </div>
        </form>
        <table class="table table-bordered leave-bal-table table-striped">
          <thead>
            <tr>
              <th>Employee Name</th>
              <?php foreach($leave_types as $lt): ?>
                <th><?= htmlspecialchars($lt['type']) ?> Leave</th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach($balances as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['fullname']) ?> <br><small>ID: <?= $row['id'] ?></small></td>
              <?php foreach($leave_types as $lt): ?>
                <?php $type = $lt['type']; $quota = $lt['quota']; $used = $row['leaves'][$type]['used']; ?>
                <td>
                  <?php if($quota == 0): ?>
                    <span class="badge bg-info">Unlimited</span>
                  <?php else: ?>
                    <span class="fw-bold"><?= max(0, $quota - $used) ?>/<?= $quota ?></span>
                    <div class="balance-bar">
                      <div class="balance-bar-inner" style="width:<?= round(($quota-$used)/$quota*100) ?>%"></div>
                    </div>
                    <small class="text-muted"><?= $used ?> used</small>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          <?php if(empty($balances)): ?>
            <tr><td colspan="<?= count($leave_types)+1 ?>" class="text-center">No employees found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>