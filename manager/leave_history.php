<?php
session_start();
require_once("../includes/db.php");
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'hr_manager') {
    header("Location: ../login.php");
    exit();
}
$fullname = $_SESSION['fullname'] ?? 'HR Manager';
$role = $_SESSION['role'] ?? 'hr_manager';

// Fetch leave types from DB for filter
$types_stmt = $pdo->query("SELECT type FROM leave_types ORDER BY type");
$leave_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

// Filters
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
if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $where[] = "l.date_from >= ? AND l.date_to <= ?";
    $params[] = $_GET['date_from'];
    $params[] = $_GET['date_to'];
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Export CSV
if (isset($_GET['export']) && $_GET['export']=='csv') {
    $stmt = $pdo->prepare("
        SELECT l.*, e.fullname, e.id AS empid
        FROM leave_requests l
        JOIN employees e ON l.employee_id = e.id
        $where_sql
        ORDER BY l.requested_at DESC
    ");
    $stmt->execute($params);
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=leave_history.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Employee','Type','Date From','Date To','Days','Reason','Status','Requested At']);
    foreach($leaves as $l) {
        $days = (strtotime($l['date_to']) - strtotime($l['date_from']))/86400 + 1;
        fputcsv($out, [
            $l['fullname'], $l['leave_type'], $l['date_from'], $l['date_to'],
            $days>0?$days:1, $l['reason'], $l['status'], $l['requested_at']
        ]);
    }
    fclose($out); exit();
}

// Fetch leave history with employee info
$stmt = $pdo->prepare("
    SELECT l.*, e.fullname, e.id AS empid, e.username
    FROM leave_requests l
    JOIN employees e ON l.employee_id = e.id
    $where_sql
    ORDER BY l.requested_at DESC
");
$stmt->execute($params);
$leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statuses = ['pending', 'approved', 'rejected'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave History - HR Manager | ViaHale TNVS HR3</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
        .status-badge.approved { background: #dbeafe; color: #2563eb; padding: 3px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; }
        .status-badge.pending { background: #fff3cd; color: #856404; padding: 3px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; }
        .status-badge.rejected { background: #fee2e2; color: #b91c1c; padding: 3px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; }
        .filter-form { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.2rem; align-items: center; }
        .filter-form .form-control, .filter-form .form-select { min-width: 140px; border-radius: 6px; }
        .filter-form .btn { border-radius: 6px; }
        .modal-dialog { max-width: 420px; }
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
        <span class="dashboard-title">Leave History</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      
      <div class="dashboard-col">
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
          <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" title="From">
          <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" title="To">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>" class="btn btn-outline-secondary">Export CSV</a>
        </form>
        <table class="table table-hover table-striped">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Type</th>
              <th>Date From</th>
              <th>Date To</th>
              <th>Days</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Requested At</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($leaves as $l): ?>
            <tr>
              <td>
                <a href="#" data-bs-toggle="modal" data-bs-target="#empModal<?= $l['empid'] ?>">
                  <?= htmlspecialchars($l['fullname']) ?>
                </a>
                <br><small>ID: <?= htmlspecialchars($l['empid']) ?></small>
                <!-- Employee Modal -->
                <div class="modal fade" id="empModal<?= $l['empid'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header"><h5 class="modal-title"><?= htmlspecialchars($l['fullname']) ?> (<?= htmlspecialchars($l['username']) ?>)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p><strong>ID:</strong> <?= $l['empid'] ?></p>
                        <?php
                        // Fetch leave balances for this employee:
                        $bal_stmt = $pdo->query("SELECT type, quota FROM leave_types ORDER BY type");
                        $bal_types = $bal_stmt->fetchAll(PDO::FETCH_ASSOC);
                        echo "<h6>Leave Balance:</h6><ul>";
                        foreach($bal_types as $bt) {
                            $type = $bt['type']; $quota = $bt['quota'];
                            $bal_q = $pdo->prepare("SELECT date_from, date_to FROM leave_requests WHERE employee_id=? AND leave_type=? AND status='approved' AND YEAR(date_from)=YEAR(CURDATE())");
                            $bal_q->execute([$l['empid'],$type]);
                            $rows = $bal_q->fetchAll(PDO::FETCH_ASSOC); $used=0;
                            foreach($rows as $row){ $d=(strtotime($row['date_to'])-strtotime($row['date_from']))/86400+1; $used+=$d>0?$d:1; }
                            echo "<li>$type: <b>".($quota==0?"Unlimited":max(0,$quota-$used)."/$quota")."</b> <span style='color:#888;font-size:0.98em;'>($used used)</span></li>";
                        }
                        echo "</ul>";
                        // Show 5 recent requests:
                        $hist_q = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id=? ORDER BY requested_at DESC LIMIT 5");
                        $hist_q->execute([$l['empid']]);
                        $recent = $hist_q->fetchAll(PDO::FETCH_ASSOC);
                        echo "<h6>Recent Requests:</h6><ul>";
                        foreach($recent as $rr) {
                            $days = (strtotime($rr['date_to'])-strtotime($rr['date_from']))/86400+1;
                            echo "<li>{$rr['leave_type']} ({$rr['date_from']} - {$rr['date_to']}) [{$rr['status']}] <span style='color:#888;font-size:0.97em;'>($days days)</span></li>";
                        }
                        echo "</ul>";
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($l['leave_type']) ?> Leave</td>
              <td><?= htmlspecialchars($l['date_from']) ?></td>
              <td><?= htmlspecialchars($l['date_to']) ?></td>
              <td>
                <?php
                  $days = (strtotime($l['date_to']) - strtotime($l['date_from'])) / 86400 + 1;
                  echo $days > 0 ? $days : 1;
                ?>
              </td>
              <td><?= htmlspecialchars($l['reason']) ?></td>
              <td>
                <span class="status-badge <?= strtolower($l['status']) ?>"><?= ucfirst($l['status']) ?></span>
              </td>
              <td><?= htmlspecialchars(date("Y-m-d H:i", strtotime($l['requested_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if(empty($leaves)): ?>
            <tr><td colspan="8" class="text-center">No leave history found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>