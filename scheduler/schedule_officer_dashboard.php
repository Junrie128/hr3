<?php
session_start();
$fullname = $_SESSION['fullname'] ?? 'Schedule Officer';
$role = $_SESSION['role'] ?? 'schedule_officer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule Officer Dashboard - ViaHale TNVS HR3</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-cards { display: flex; gap: 1.5rem; margin-bottom: 2.2rem; flex-wrap: wrap; }
        .stats-card { background: #fff; border-radius: 18px; box-shadow: 0 2px 8px rgba(140, 140, 200, 0.07); flex: 1; padding: 1.5rem 1.2rem; text-align: center; min-width: 170px; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; border: 1px solid #f0f0f0; }
        .stats-card .icon { background: #ede9fe; color: #4311a5; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 0.5rem; }
        .stats-card .label { font-size: 1.08rem; color: #6c757d; margin-bottom: 0.2rem; }
        .stats-card .value { font-size: 1.6rem; font-weight: 700; color: #22223b; }
        .dashboard-row { display: flex; gap: 1.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .dashboard-col { background: #fff; border-radius: 18px; box-shadow: 0 2px 8px rgba(140, 140, 200, 0.07); padding: 1.5rem 1.2rem; flex: 1; min-width: 0; min-width: 320px; margin-bottom: 1rem; display: flex; flex-direction: column; gap: 1rem; border: 1px solid #f0f0f0; }
        .dashboard-col h5 { font-family: 'QuickSand', 'Poppins', Arial, sans-serif; font-size: 1.13rem; font-weight: 600; margin-bottom: 1.1rem; color: #22223b; }
        .table { font-size: 0.98rem; color: #22223b; }
        .table th { color: #6c757d; font-weight: 600; border: none; background: transparent; }
        .table td { border: none; background: transparent; }
        .status-badge.success { background: #dbeafe; color: #2563eb; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.danger { background: #fee2e2; color: #b91c1c; }
        @media (max-width: 1200px) { .main-content { padding: 1rem 0.3rem 1rem 0.3rem; } .sidebar { width: 180px; padding: 1rem 0.3rem; } .main-content { margin-left: 180px; } }
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
          <a class="nav-link text-danger" href="../logout.php">
            <ion-icon name="log-out-outline"></ion-icon>Logout
          </a>
        </div>
      </div>
    </div>
    <div class="main-content col" style="margin-left:220px;">
      <div class="topbar">
        <span class="dashboard-title">Welcome back, <?= htmlspecialchars($fullname) ?>!</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      
      <div class="stats-cards">
        <div class="stats-card">
          <div class="icon"><ion-icon name="calendar-outline"></ion-icon></div>
          <div class="label">Active Schedules</div>
          <div class="value">12</div>
        </div>
        <div class="stats-card">
          <div class="icon"><ion-icon name="swap-horizontal-outline"></ion-icon></div>
          <div class="label">Pending Shift Swaps</div>
          <div class="value">3</div>
        </div>
        <div class="stats-card">
          <div class="icon"><ion-icon name="alert-circle-outline"></ion-icon></div>
          <div class="label">Conflicts Detected</div>
          <div class="value">1</div>
        </div>
        <div class="stats-card">
          <div class="icon"><ion-icon name="people-outline"></ion-icon></div>
          <div class="label">Requests Today</div>
          <div class="value">5</div>
        </div>
      </div>
      <div class="dashboard-row">
        <div class="dashboard-col" style="flex:1.7">
          <h5>Schedules by Department (Bar)</h5>
          <div style="height:260px;"><canvas id="barChart"></canvas></div>
        </div>
        <div class="dashboard-col" style="flex:1">
          <h5>Shift Swap Status (Pie)</h5>
          <div style="height:260px;"><canvas id="pieChart"></canvas></div>
        </div>
      </div>
      <div class="dashboard-col">
        <h5>Recent Scheduling Activity</h5>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Date</th>
              <th>User</th>
              <th>Activity</th>
              <th>Module</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>2025-08-29</td>
              <td>Anna Cruz</td>
              <td>Shift Updated</td>
              <td>Shift & Schedule Management</td>
              <td><span class="status-badge success">Success</span></td>
            </tr>
            <tr>
              <td>2025-08-29</td>
              <td>Ben Lee</td>
              <td>Swap Requested</td>
              <td>Shift & Schedule Management</td>
              <td><span class="status-badge pending">Pending</span></td>
            </tr>
            <tr>
              <td>2025-08-28</td>
              <td>Schedule Officer</td>
              <td>Conflict Flagged</td>
              <td>Shift & Schedule Management</td>
              <td><span class="status-badge danger">Conflict</span></td>
            </tr>
            <tr>
              <td>2025-08-28</td>
              <td>Marie Tan</td>
              <td>Schedule Published</td>
              <td>Shift & Schedule Management</td>
              <td><span class="status-badge success">Success</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
  // Chart.js Scripts (Dummy Data)
  const barCtx = document.getElementById('barChart').getContext('2d');
  new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: ['HR', 'Operations', 'IT', 'Finance', 'Logistics'],
      datasets: [
        { label: 'Schedules', data: [3, 4, 2, 1, 2], backgroundColor: '#9A66ff' }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: { y: { beginAtZero: true } }
    }
  });

  const pieCtx = document.getElementById('pieChart').getContext('2d');
  new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: ['Approved 60%', 'Rejected 5%', 'Pending 35%'],
      datasets: [{
        data: [60, 5, 35],
        backgroundColor: ['#9A66ff', '#dc3545', '#ffc107']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { animateScale: true }
    }
  });
</script>
</body>
</html>