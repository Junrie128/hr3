<?php
session_start();
require_once("../includes/db.php");

if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'hr_manager') {
    header("Location: ../login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'HR Manager';
$role = $_SESSION['role'] ?? 'hr_manager';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['leave_id'])) {
    $leave_id = $_POST['leave_id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : ($_POST['action'] === 'reject' ? 'rejected' : 'pending');
    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, reviewed_at = NOW(), reviewer_id = ? WHERE id = ?");
    $stmt->execute([$action, $_SESSION['employee_id'], $leave_id]);
}

// Search/filter
$where = [];
$params = [];
if (!empty($_GET['employee'])) {
    $where[] = "e.fullname LIKE ?";
    $params[] = "%" . $_GET['employee'] . "%";
}
if (!empty($_GET['type'])) {
    $where[] = "l.leave_type = ?";
    $params[] = $_GET['type'];
}
if (!empty($_GET['status'])) {
    $where[] = "l.status = ?";
    $params[] = $_GET['status'];
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch leave requests with employee info
$stmt = $pdo->prepare("
    SELECT l.*, e.fullname, e.id AS empid
    FROM leave_requests l
    JOIN employees e ON l.employee_id = e.id
    $where_sql
    ORDER BY l.requested_at DESC
");
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Leave types for filter
$leave_types = ['Vacation', 'Sick', 'Emergency', 'Unpaid'];
$statuses = ['pending', 'approved', 'rejected'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Requests - HR Manager | ViaHale TNVS HR3</title>
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
        .dashboard-col { background: #fff; border-radius: 18px; box-shadow: 0 2px 8px rgba(140, 140, 200, 0.07); padding: 2rem 1.2rem 1rem 1.2rem; flex: 1; min-width: 0; margin: 2rem auto 1rem auto; max-width: 1100px; display: flex; flex-direction: column; gap: 1rem; border: 1px solid #f0f0f0; }
        .dashboard-col h5 { font-family: 'QuickSand', 'Poppins', Arial, sans-serif; font-size: 1.13rem; font-weight: 600; margin-bottom: 1.1rem; color: #22223b; }
        .table { font-size: 0.98rem; color: #22223b; background: #fff; }
        .table th { color: #6c757d; font-weight: 600; border: none; background: transparent; }
        .table td { border: none; background: transparent; }
        .status-badge.approved, .status-badge.success { background: #dbeafe; color: #2563eb; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.rejected, .status-badge.danger { background: #fee2e2; color: #b91c1c; }
        .filter-form { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.2rem; align-items: center; }
        .filter-form .form-control, .filter-form .form-select { min-width: 140px; border-radius: 6px; }
        .filter-form .btn { border-radius: 6px; }
        @media (max-width: 1000px) { .dashboard-col { padding: 1rem 0.2rem; } }
        @media (max-width: 700px) { .dashboard-title { font-size: 1.1rem; } .dashboard-col { padding: 1rem 0.1rem; } }
        @media (max-width: 500px) { .dashboard-col { padding: 0.4rem 0.01rem; } }
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
        <span class="dashboard-title">Leave Requests</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      
      <div class="dashboard-col">
        <h5>All Leave Requests</h5>
        <form method="get" class="filter-form">
          <input type="text" class="form-control" name="employee" value="<?= htmlspecialchars($_GET['employee'] ?? '') ?>" placeholder="Employee Name">
          <select name="type" class="form-select">
            <option value="">All Types</option>
            <?php foreach($leave_types as $t): ?>
              <option value="<?= $t ?>" <?= ($_GET['type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?> Leave</option>
            <?php endforeach; ?>
          </select>
          <select name="status" class="form-select">
            <option value="">All Status</option>
            <?php foreach($statuses as $s): ?>
              <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primary"><ion-icon name="search-outline"></ion-icon> Filter</button>
        </form>
        <table class="table table-hover table-striped">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Type</th>
              <th>Date From</th>
              <th>Date To</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Requested At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($requests as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['fullname']) ?> <br><small>ID: <?= htmlspecialchars($r['empid']) ?></small></td>
              <td><?= htmlspecialchars($r['leave_type']) ?> Leave</td>
              <td><?= htmlspecialchars($r['date_from']) ?></td>
              <td><?= htmlspecialchars($r['date_to']) ?></td>
              <td><?= htmlspecialchars($r['reason']) ?></td>
              <td>
                <?php
                  $status = strtolower($r['status']);
                  $badge = 'pending';
                  if ($status === 'approved') $badge = 'approved';
                  elseif ($status === 'rejected') $badge = 'rejected';
                  elseif ($status === 'success') $badge = 'success';
                ?>
                <span class="status-badge <?= $badge ?>"><?= ucfirst($status) ?></span>
              </td>
              <td><?= htmlspecialchars(date("Y-m-d H:i", strtotime($r['requested_at']))) ?></td>
              <td>
                <?php if ($status === 'pending'): ?>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="leave_id" value="<?= $r['id'] ?>">
                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success mb-1">Approve</button>
                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger mb-1">Reject</button>
                  </form>
                <?php else: ?>
                  <span class="text-muted">No action</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($requests)): ?>
            <tr><td colspan="8" class="text-center">No leave requests found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>