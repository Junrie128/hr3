<?php
session_start();
require_once("../includes/db.php");

// Only HR Managers
if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'hr_manager') {
    header("Location: ../login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'HR Manager';
$role = $_SESSION['role'] ?? 'hr_manager';

// Fetch all timesheets and associated employee info
$stmt = $pdo->query("
    SELECT t.*, e.fullname, e.username
    FROM timesheets t
    JOIN employees e ON t.employee_id = e.id
    ORDER BY t.submitted_at DESC
");
$timesheets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Timesheet Reports - HR Manager | ViaHale TNVS HR3</title>
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
        .main-content { margin-left: 220px; padding: 2rem 2rem 2rem 2rem; }
        .card { border-radius: 18px; box-shadow: 0 2px 8px rgba(140, 140, 200, 0.07); border: 1px solid #f0f0f0; margin-bottom: 2rem; }
        .table { font-size: 0.98rem; color: #22223b; background: #fff; }
        .table th { color: #6c757d; font-weight: 600; border: none; background: transparent; }
        .table td { border: none; background: transparent; }
        .status-badge { padding: 3px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; display: inline-block; }
        .status-badge.success, .status-badge.approved { background: #dbeafe; color: #2563eb; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.danger, .status-badge.rejected { background: #fee2e2; color: #b91c1c; }
        @media (max-width: 1200px) { .main-content { padding: 1rem 0.3rem 1rem 0.3rem; } .sidebar { width: 180px; padding: 1rem 0.3rem; } .main-content { margin-left: 180px; } }
        @media (max-width: 900px) { .sidebar { left: -220px; width: 180px; padding: 1rem 0.3rem; } .sidebar.show { left: 0; } .main-content { margin-left: 0; padding: 1rem 0.5rem 1rem 0.5rem; } }
        @media (max-width: 700px) { .dashboard-title { font-size: 1.1rem; } .main-content { padding: 0.7rem 0.2rem 0.7rem 0.2rem; } .sidebar { width: 100vw; left: -100vw; padding: 0.7rem 0.2rem; } .sidebar.show { left: 0; } }
        @media (max-width: 500px) { .main-content { padding: 0.1rem 0.01rem; } .sidebar { width: 100vw; left: -100vw; padding: 0.3rem 0.01rem; } .sidebar.show { left: 0; } }
        @media (min-width: 1400px) { .sidebar { width: 260px; padding: 2rem 1rem 2rem 1rem; } .main-content { margin-left: 260px; padding: 2rem 2rem 2rem 2rem; } }

        /* Enhanced Modal Styles */
        .modal-content {
            border-radius: 16px;
            box-shadow: 0 6px 32px rgba(70, 57, 130, 0.20);
            border: 1px solid #e0e7ff;
            background: #fff;
            font-family: 'QuickSand', 'Poppins', Arial, sans-serif;
        }

        .modal-header {
            background: linear-gradient(90deg, #9A66ff 0%, #4311a5 100%);
            color: #fff;
            border-bottom: none;
            border-radius: 16px 16px 0 0;
            padding: 1.1rem 1.5rem;
            box-shadow: 0 2px 8px rgba(140, 140, 200, 0.09);
        }

        .modal-title {
            font-size: 1.23rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .btn-close {
            color: #fff !important;
            filter: brightness(1.8) grayscale(0.25);
            opacity: 0.85;
            transition: opacity 0.15s;
        }
        .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            background: #fafbfc;
            padding: 1.7rem 1.5rem 1.5rem 1.5rem;
            border-radius: 0 0 16px 16px;
            font-size: 1.02rem;
            color: #22223b;
            min-height: 120px;
        }

        #modalLoading {
            font-size: 1.1rem;
            color: #4311a5;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .modal-content {
                border-radius: 8px;
                padding: 0.7rem;
            }
            .modal-header, .modal-body {
                padding: 0.7rem 1rem;
            }
            .modal-title {
                font-size: 1.08rem;
            }
        }

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
    <div class="main-content col">
      <div class="topbar">
        <span class="dashboard-title">Timesheet Reports</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header bg-primary text-white">All Submitted Timesheets</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Username</th>
                        <th>Period</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($timesheets as $ts): ?>
                    <tr>
                        <td><?= htmlspecialchars($ts['fullname']) ?></td>
                        <td>@<?= htmlspecialchars($ts['username']) ?></td>
                        <td><?= htmlspecialchars($ts['period_from']) ?> to <?= htmlspecialchars($ts['period_to']) ?></td>
                        <td><?= htmlspecialchars($ts['submitted_at']) ?></td>
                        <td>
                          <?php
                          $status = strtolower($ts['status'] ?? 'pending');
                          $badge = 'pending';
                          if ($status === 'approved') $badge = 'approved';
                          elseif ($status === 'rejected') $badge = 'rejected';
                          elseif ($status === 'success') $badge = 'success';
                          ?>
                          <span class="status-badge <?= $badge ?>">
                            <?= htmlspecialchars(ucfirst($status)) ?>
                          </span>
                        </td>
                        <td>
                            <button type="button"
                              class="btn btn-sm btn-primary view-details-btn"
                              data-timesheet-id="<?= $ts['id'] ?>"
                              data-bs-toggle="modal"
                              data-bs-target="#detailsModal">
                              View Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($timesheets)): ?>
                    <tr><td colspan="6" class="text-center">No timesheets found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal HTML (place at end of body) -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailsModalLabel">Timesheet Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalLoading" style="display:none;"><div class="text-center py-4">Loading...</div></div>
        <div id="modalTimesheetDetails"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// AJAX modal logic
document.querySelectorAll('.view-details-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.timesheetId;
        document.getElementById('modalTimesheetDetails').innerHTML = '';
        document.getElementById('modalLoading').style.display = '';
        fetch('timesheet_modal_data.php?id=' + encodeURIComponent(id))
            .then(r => r.text())
            .then(html => {
                document.getElementById('modalTimesheetDetails').innerHTML = html;
                document.getElementById('modalLoading').style.display = 'none';
            });
    });
});
</script>
</body>
</html>