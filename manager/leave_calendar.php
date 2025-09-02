<?php
session_start();
require_once("../includes/db.php");
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'hr_manager') {
    header("Location: ../login.php"); exit();
}
$month = $_GET['month'] ?? date('Y-m');
$start_month = "$month-01";
$end_month = date('Y-m-t', strtotime($start_month));
$first_day_of_week = date('N', strtotime($start_month)); // 1 (Mon) ... 7 (Sun)
$days_in_month = date('t', strtotime($start_month));
$month_label = date('F Y', strtotime($start_month));

// Fetch all leaves in month
$stmt = $pdo->prepare("
    SELECT l.*, e.fullname, e.username, e.id as empid
    FROM leave_requests l
    JOIN employees e ON l.employee_id = e.id
    WHERE l.date_from <= ? AND l.date_to >= ?
    AND (l.status='approved' OR l.status='pending')
    ORDER BY l.date_from
");
$stmt->execute([$end_month, $start_month]);
$leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map leaves to each date
$leaves_by_date = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $date = date('Y-m-d', strtotime("$month-$d"));
    $leaves_by_date[$date] = [];
}
foreach ($leaves as $l) {
    $from = strtotime($l['date_from']);
    $to = strtotime($l['date_to']);
    for ($d = $from; $d <= $to; $d += 86400) {
        $date = date('Y-m-d', $d);
        if (isset($leaves_by_date[$date])) {
            $leaves_by_date[$date][] = $l;
        }
    }
}

$fullname = $_SESSION['fullname'] ?? 'HR Manager';
$role = $_SESSION['role'] ?? 'hr_manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Calendar - HR Manager | ViaHale TNVS HR3</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background: #fafbfc; font-family: 'QuickSand', 'Poppins', Arial, sans-serif; color: #22223b; font-size: 16px; }
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
        .calendar-type { margin: 0 auto 2rem auto; width: 100%; max-width: 900px; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.7rem; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
        .calendar-weekday { text-align: center; font-weight: 600; color: #4311a5; background: #ede9fe; border-radius: 6px; padding: 0.6em 0; font-size: 1.08rem; }
        .calendar-day { min-height: 75px; background: #f6f6fb; border-radius: 12px; box-shadow: 0 2px 6px rgba(148,148,200,0.05); text-align: left; position: relative; cursor: pointer; padding: 6px 7px 7px 7px; transition: background 0.15s; }
        .calendar-day:hover { background: #e6e6f7; }
        .calendar-day.today { border: 2px solid #9A66ff; }
        .calendar-day.disabled { background: #f2f2f2; color: #bdbdbd; cursor: default; }
        .calendar-day .day-num { font-weight: 700; display: block; margin-bottom: 2px; color: #4311a5; font-size: 1.1rem; }
        .calendar-day .leave-badge { display: block; font-size: 0.92rem; margin: 2px 0; border-radius: 8px; padding: 2px 7px; background: #fee2e2; color: #b91c1c; font-weight: 600; }
        .calendar-day .leave-badge.approved { background: #c3f6c3; color: #2563eb; }
        .calendar-day .leave-badge.pending { background: #fff3cd; color: #856404; }
        .calendar-day .leave-badge + .leave-badge { margin-top: 2px; }
        .calendar-empty { min-height: 75px; }
        .modal-header h5 { font-family: 'QuickSand', 'Poppins', Arial, sans-serif; }
        @media (max-width:900px) { .calendar-type { max-width: 100vw; } }
        @media (max-width:700px) { .calendar-type { font-size: 0.95rem; } .calendar-day { min-height: 55px; font-size: 0.98rem; } }
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
        </div>
      </div>
    </div>
    <div class="main-content col" style="margin-left:220px;">
      <div class="topbar">
        <span class="dashboard-title">Leave Calendar</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      <div class="breadcrumbs text-end mb-2">Dashboard &gt; Leave Management &gt; Leave Calendar</div>
      <div class="dashboard-col">
        <form method="get" class="mb-3">
          <div class="input-group" style="max-width:250px;">
            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($month) ?>">
            <button type="submit" class="btn btn-primary">Go</button>
          </div>
        </form>
        <div class="calendar-type">
          <div class="calendar-header">
            <strong style="font-size:1.3rem;"><?= $month_label ?></strong>
            <a href="?month=<?= date('Y-m', strtotime('-1 month', strtotime($start_month))) ?>" class="btn btn-light btn-sm">&laquo; Prev</a>
            <a href="?month=<?= date('Y-m', strtotime('+1 month', strtotime($start_month))) ?>" class="btn btn-light btn-sm">Next &raquo;</a>
          </div>
          <div class="calendar-grid">
            <?php
            $weekdays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            foreach ($weekdays as $wd) {
                echo '<div class="calendar-weekday">'.$wd.'</div>';
            }
            // Fill empty slots before first day
            for ($i=1; $i<$first_day_of_week; $i++) {
                echo '<div class="calendar-empty"></div>';
            }
            // Render each day
            for ($d=1; $d<=$days_in_month; $d++) {
                $date = date('Y-m-d', strtotime("$month-$d"));
                $today_class = ($date == date('Y-m-d')) ? 'today' : '';
                $has_leaves = count($leaves_by_date[$date]) > 0;
                $modal_id = 'modal_' . str_replace('-','',$date);
                echo '<div class="calendar-day '.($has_leaves?'calendar-has-leave':'').' '.$today_class.($has_leaves?'':' disabled').'" '
                . ($has_leaves ? 'data-bs-toggle="modal" data-bs-target="#'.$modal_id.'"' : '')
                . '>';
                echo '<span class="day-num">'.$d.'</span>';
                // Show summary badges
                foreach ($leaves_by_date[$date] as $lv) {
                    echo '<span class="leave-badge '.$lv['status'].'">'.htmlspecialchars($lv['fullname']).' ('.htmlspecialchars($lv['leave_type']).')'.'</span>';
                }
                echo '</div>';
                // Modal for this day
                if ($has_leaves) {
                ?>
                <div class="modal fade" id="<?= $modal_id ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header"><h5 class="modal-title">Leave Requests on <?= date('F j, Y', strtotime($date)) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <?php foreach ($leaves_by_date[$date] as $lv): ?>
                          <div class="mb-3 p-2" style="background:#f7f7fa;border-radius:8px;">
                            <strong><?= htmlspecialchars($lv['fullname']) ?> (<?= htmlspecialchars($lv['username']) ?>)</strong><br>
                            <b>Type:</b> <?= htmlspecialchars($lv['leave_type']) ?> <br>
                            <b>Status:</b> <span class="leave-badge <?= $lv['status'] ?>"><?= ucfirst($lv['status']) ?></span> <br>
                            <b>From:</b> <?= htmlspecialchars($lv['date_from']) ?> <b>To:</b> <?= htmlspecialchars($lv['date_to']) ?> <br>
                            <b>Reason:</b> <?= htmlspecialchars($lv['reason']) ?>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                </div>
                <?php
                }
            }
            // Fill empty slots after last day
            $remain = ( ($first_day_of_week-1 + $days_in_month) % 7 );
            if ($remain > 0) {
                for ($i=1; $i<= (7-$remain); $i++) {
                    echo '<div class="calendar-empty"></div>';
                }
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>