<?php
session_start();
require_once("../includes/attendance_functions.php");

$employeeId = $_SESSION['employee_id'] ?? null;
$fullname = $_SESSION['fullname'] ?? 'Employee';
$role = $_SESSION['role'] ?? 'employee';

$today = date('Y-m-d');

// Get today's attendance record efficiently from database
$todayLog = null;
if ($employeeId) {
    $todayLog = getAttendanceForDate($employeeId, $today); // new DB-powered function (see below)
}

$message = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['clock_in'])) {
        $message = clockIn($employeeId);
        // After clock in, reload so latest data appears
        header("Location: attendance.php");
        exit();
    } elseif (isset($_POST['clock_out'])) {
        $message = clockOut($employeeId);
        // After clock out, log out user automatically
        if (strpos($message, "Clocked out at") === 0) {
            session_unset();
            session_destroy();
            header("Location: ../logout.php");
            exit();
        } else {
            // If not successful, reload to refresh data
            header("Location: attendance.php");
            exit();
        }
    }
    // Refresh today's log after an action
    $todayLog = getAttendanceForDate($employeeId, $today);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Attendance - ViaHale TNVS HR3</title>
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
        .info-box { background: #f4f0ff; border-left: 5px solid #9A66ff; padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1rem; }
        .clock-btn { font-size: 1.2rem; padding: 0.7rem 2.5rem; border-radius: 10px; margin: 8px; }
        .clock-in, .clock-out { background: linear-gradient(90deg, #9A66ff 0%, #4311a5 100%); color: #fff; border: none; }
        .clock-btn:disabled { background: #e4e4e4 !important; color: #999 !important; }
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
              <a class="nav-link active" href="../employee/attendance.php"><ion-icon name="timer-outline"></ion-icon>Clock In / Out</a>
              <a class="nav-link" href="../employee/attendance_logs.php"><ion-icon name="list-outline"></ion-icon>My Attendance Logs</a>
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
          <a class="nav-link text-danger" href="/logout.php">
            <ion-icon name="log-out-outline"></ion-icon>Logout
          </a>
        </div>
      </div>
    </div>
    <div class="main-content col">
      <div class="topbar">
        <span class="dashboard-title">Time & Attendance</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      <div class="breadcrumbs text-end mb-2"><a href="employee_dashboard.php">Dashboard</a> &gt; Time & Attendance</div>
      <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <div class="info-box mb-3">
          <strong>Today's Status:</strong><br>
          <?php if ($todayLog): ?>
              <span>
                  <strong>Clock In:</strong>
                  <?= $todayLog['time_in'] ? htmlspecialchars($todayLog['time_in']) : '<em>Not yet</em>' ?>
                  <?php if (!empty($todayLog['ip_in'])): ?>
                      <span class="text-muted" style="font-size:90%;">(IP: <?= htmlspecialchars($todayLog['ip_in']) ?>)</span>
                  <?php endif; ?>
              </span><br>
              <span>
                  <strong>Clock Out:</strong>
                  <?= $todayLog['time_out'] ? htmlspecialchars($todayLog['time_out']) : '<em>Not yet</em>' ?>
                  <?php if (!empty($todayLog['ip_out'])): ?>
                      <span class="text-muted" style="font-size:90%;">(IP: <?= htmlspecialchars($todayLog['ip_out']) ?>)</span>
                  <?php endif; ?>
              </span><br>
              <span>
                  <strong>Status:</strong>
                  <?= htmlspecialchars($todayLog['status'] ?? 'N/A') ?>
              </span>
          <?php else: ?>
              <em>No attendance yet today.</em>
          <?php endif; ?>
      </div>
      <form method="post" class="mb-4">
          <button type="submit" name="clock_in" class="clock-btn clock-in"
            <?= ($todayLog && !empty($todayLog['time_in'])) ? 'disabled' : '' ?>>
              <?= ($todayLog && !empty($todayLog['time_in'])) ? 'Already Clocked In' : 'Clock In' ?>
          </button>
          <button type="submit" name="clock_out" class="clock-btn clock-out"
            <?= (!$todayLog || empty($todayLog['time_in']) || !empty($todayLog['time_out'])) ? 'disabled' : '' ?>>
              <?= ($todayLog && !empty($todayLog['time_out'])) ? 'Already Clocked Out' : 'Clock Out' ?>
          </button>
      </form>
      <a href="/employee/attendance_logs.php" class="btn btn-outline-primary">View My Attendance Logs</a>
    </div>
  </div>
</div>
</body>
</html>