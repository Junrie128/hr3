<?php
session_start();
require_once("../includes/attendance_functions.php");

// Payday/cutoff settings
define('PAYDAY_DAY', 15);
define('CUTOFF_DAYS_BEFORE', 2);

$employeeId = $_SESSION['employee_id'] ?? null;
$fullname = $_SESSION['fullname'] ?? 'Employee';
$role = $_SESSION['role'] ?? 'employee';

function getCurrentPeriod() {
    $today = date('d');
    $month = date('m');
    $year = date('Y');
    if ($today <= PAYDAY_DAY) {
        $from = "$year-$month-01";
        $to = "$year-$month-" . str_pad(PAYDAY_DAY, 2, '0', STR_PAD_LEFT);
    } else {
        $from = "$year-$month-" . str_pad((PAYDAY_DAY+1), 2, '0', STR_PAD_LEFT);
        $to = date('Y-m-t');
    }
    return [$from, $to];
}

list($from, $to) = getCurrentPeriod();
$logs = viewAttendanceLogsCutoff($employeeId, $from, $to);

// Download CSV if requested
if (isset($_GET['download']) && $_GET['download'] === 'csv') {
    $filename = "attendance_{$from}_to_{$to}.csv";
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $output = fopen('php://output', 'w');
    fputcsv($output, ["Date", "Time In", "Time Out", "Status", "IP In", "IP Out"]);
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['date'],
            $log['time_in'],
            $log['time_out'],
            $log['status'] ?? '',
            $log['ip_in'] ?? '',
            $log['ip_out'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Handle submission to timesheet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_timesheet'])) {
    submitAttendanceToTimesheet($employeeId, $from, $to);
    createTimesheetSubmission($employeeId, $from, $to); // <-- NEW
    $_SESSION['last_timesheet_submit'] = date('Y-m-d H:i:s');
    $submitted_message = "Attendance logs from $from to $to have been submitted to Timesheet Management!";
}

$today = date('Y-m-d');
$payday = $to;
$days_to_payday = (strtotime($payday) - strtotime($today)) / 86400;
$require_submit = ($days_to_payday <= CUTOFF_DAYS_BEFORE && $days_to_payday >= 0);

// Check if already submitted for this period
$already_submitted = false;
foreach ($logs as $log) {
    if (!empty($log['submitted_to_timesheet'])) {
        $already_submitted = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Logs - ViaHale TNVS HR3</title>
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
        .main-content { margin-left: 220px; padding: 2rem 2rem 2rem 2rem; }
        .attendance-table th, .attendance-table td { text-align: center; }
        .submitted-row { background: #e0ffe0 !important; }
        @media (max-width: 1200px) { .main-content { padding: 1rem 0.3rem 1rem 0.3rem; } .sidebar { width: 180px; padding: 1rem 0.3rem; } .main-content { margin-left: 180px; } }
        @media (max-width: 900px) { .sidebar { left: -220px; width: 180px; padding: 1rem 0.3rem; } .sidebar.show { left: 0; } .main-content { margin-left: 0; padding: 1rem 0.5rem 1rem 0.5rem; } }
        @media (max-width: 700px) { .main-content { padding: 0.7rem 0.2rem 0.7rem 0.2rem; } .sidebar { width: 100vw; left: -100vw; padding: 0.7rem 0.2rem; } .sidebar.show { left: 0; } }
        @media (max-width: 500px) { .main-content { padding: 0.1rem 0.01rem; } .sidebar { width: 100vw; left: -100vw; padding: 0.3rem 0.01rem; } .sidebar.show { left: 0; } }
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
              <a class="nav-link" href="/employee/employee_dashboard.php"><ion-icon name="home-outline"></ion-icon>Dashboard</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Time & Attendance</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="../employee/attendance.php"><ion-icon name="timer-outline"></ion-icon>Clock In / Out</a>
              <a class="nav-link active" href="../employee/attendance_logs.php"><ion-icon name="list-outline"></ion-icon>My Attendance Logs</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Leave Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="/employee/leave_request.php"><ion-icon name="calendar-outline"></ion-icon>Request Leave</a>
              <a class="nav-link" href="/employee/leave_balance.php"><ion-icon name="calendar-outline"></ion-icon>Leave Balance</a>
              <a class="nav-link" href="/employee/leave_history.php"><ion-icon name="calendar-outline"></ion-icon>Leave History</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Shift & Schedule</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="/employee/schedule.php"><ion-icon name="calendar-outline"></ion-icon>My Schedule</a>
              <a class="nav-link" href="/employee/shift_swap.php"><ion-icon name="swap-horizontal-outline"></ion-icon>Request Shift Swap</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Timesheet Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="/employee/timesheet_submit.php"><ion-icon name="document-text-outline"></ion-icon>Submit Timesheet</a>
              <a class="nav-link" href="/employee/timesheets.php"><ion-icon name="document-text-outline"></ion-icon>My Timesheets</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Claims & Reimbursement</h6>
            <nav class="nav flex-column">
              <a class="nav-link" href="/employee/claim_file.php"><ion-icon name="create-outline"></ion-icon>File a Claim</a>
              <a class="nav-link" href="/employee/claims.php"><ion-icon name="cash-outline"></ion-icon>My Claims</a>
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
    <div class="main-content col">
      <div class="topbar">
        <span class="dashboard-title">Attendance Logs</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      <div class="breadcrumbs text-end mb-2"><a href="employee_dashboard.php">Dashboard</a> &gt; <a href="attendance.php">Time & Attendance</a> &gt; Attendance Logs</div>
      <a href="/employee/attendance.php" class="btn btn-outline-secondary mb-3">&larr; Back to Attendance</a>

      <?php if (!empty($submitted_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($submitted_message) ?></div>
      <?php endif; ?>

      <?php if ($require_submit && !$already_submitted): ?>
        <div class="alert alert-warning">
            <strong>It's almost payday!</strong> Please download and submit your attendance logs for the period <b><?= htmlspecialchars($from) ?></b> to <b><?= htmlspecialchars($to) ?></b>.<br>
            <a href="?download=csv" class="btn btn-sm btn-success my-2">Download CSV for Cutoff</a>
            <form method="post" style="display:inline;">
                <button type="submit" name="submit_timesheet" class="btn btn-sm btn-primary">Submit to Timesheet Management</button>
            </form>
        </div>
      <?php elseif ($already_submitted): ?>
        <div class="alert alert-info">
            Your attendance for the current cutoff period (<b><?= htmlspecialchars($from) ?></b> to <b><?= htmlspecialchars($to) ?></b>) has already been submitted.
        </div>
      <?php else: ?>
        <div class="mb-3">
            <a href="?download=csv" class="btn btn-outline-primary">Download This Cutoff as CSV</a>
            <form method="post" style="display:inline;">
                <button type="submit" name="submit_timesheet" class="btn btn-outline-success">Submit This Cutoff to Timesheet Management</button>
            </form>
        </div>
      <?php endif; ?>

      <table class="table table-bordered attendance-table bg-white">
        <thead>
          <tr>
              <th>Date</th>
              <th>Time In</th>
              <th>Time Out</th>
              <th>Status</th>
              <th>IP In</th>
              <th>IP Out</th>
              <th>Submitted?</th>
          </tr>
        </thead>
        <tbody>
        <?php if (count($logs) > 0): ?>
            <?php foreach ($logs as $log): ?>
                <tr class="<?= !empty($log['submitted_to_timesheet']) ? 'submitted-row' : '' ?>">
                    <td><?= htmlspecialchars($log['date']) ?></td>
                    <td><?= htmlspecialchars($log['time_in']) ?></td>
                    <td><?= htmlspecialchars($log['time_out']) ?></td>
                    <td><?= htmlspecialchars($log['status']) ?></td>
                    <td><?= htmlspecialchars($log['ip_in'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['ip_out'] ?? '') ?></td>
                    <td><?= !empty($log['submitted_to_timesheet']) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">No attendance records for this cutoff period.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
      <?php if (!empty($_SESSION['last_timesheet_submit'])): ?>
        <div class="text-muted mt-3" style="font-size:90%;">
            Last submitted: <?= htmlspecialchars($_SESSION['last_timesheet_submit']) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>