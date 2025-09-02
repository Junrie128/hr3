<?php
session_start();
require_once("../includes/db.php");

$fullname = $_SESSION['fullname'] ?? 'Schedule Officer';
$role = $_SESSION['role'] ?? 'schedule_officer';

// Handle add employee form submission (via modal)
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee_modal'])) {
    $name = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_emp = $_POST['role'] ?? 'employee';
    $password = trim($_POST['password'] ?? '');
    $conf_password = trim($_POST['conf_password'] ?? '');
    $job_title = trim($_POST['job_title'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $date_joined = trim($_POST['date_joined'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    $address = trim($_POST['address'] ?? '');

    if ($name && $username && $email && $password && $password === $conf_password) {
        $stmt = $pdo->prepare("INSERT INTO employees 
            (fullname, username, email, password, role, job_title, contact_number, birthday, department, gender, date_joined, status, address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name,
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role_emp,
            $job_title,
            $contact_number,
            $birthday,
            $department,
            $gender,
            $date_joined,
            $status,
            $address
        ]);
        $msg = "Employee added successfully!";
    } else {
        $msg = "All required fields must be filled and passwords must match.";
    }
}

// Demo profile images (replace with real uploads in production)
$profile_images = [
    1 => "../assets/images/profile1.jpg",
    2 => "../assets/images/profile2.jpg",
    3 => "../assets/images/profile3.jpg",
    4 => "../assets/images/profile4.jpg",
    5 => "../assets/images/profile5.jpg",
    6 => "../assets/images/profile6.jpg",
    7 => "../assets/images/profile7.jpg"
];

// Search/filter
$search = trim($_GET['search'] ?? '');
$params = [];
$where = '';
if ($search) {
    $where = 'WHERE fullname LIKE ? OR username LIKE ? OR email LIKE ?';
    $params = ["%$search%", "%$search%", "%$search%"];
}
$stmt = $pdo->prepare("SELECT id, fullname, username, email, role, job_title, contact_number, birthday, department, gender, date_joined, status, address FROM employees $where ORDER BY id DESC");
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Management - ViaHale TNVS HR3</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        body { font-family: 'QuickSand', 'Poppins', Arial, sans-serif; background: #fafbfc; color: #22223b; font-size: 16px; }
        .sidebar { background: #181818ff; color: #fff; min-height: 100vh; border: none; width: 220px; position: fixed; left: 0; top: 0; z-index: 1040; transition: left 0.3s; overflow-y: auto; padding: 1rem 0.3rem 1rem 0.3rem; scrollbar-width: none; }
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
        .employee-cards-row { display: flex; gap: 1.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .employee-card { background: #fff; border-radius: 18px; box-shadow: 0 2px 8px rgba(140,140,200,0.07); padding: 1.5rem 1.2rem; width: 270px; min-width: 220px; display: flex; flex-direction: column; align-items: center; gap: 0.7rem; border: 1px solid #f0f0f0; position: relative; }
        .employee-card-header { width: 100%; height: 70px; background: linear-gradient(90deg, #9A66ff 0%, #4311a5 100%); border-radius: 18px 18px 0 0; position: absolute; left: 0; top: 0; z-index: 1; }
        .employee-card-img { width: 74px; height: 74px; border-radius: 50%; object-fit: cover; margin-top: 30px; border: 3px solid #fff; box-shadow: 0 1px 8px rgba(120,120,120,0.07); position: relative; z-index: 2;}
        .employee-card-edit { position: absolute; top: 10px; right: 12px; background: #ff9800; color: #fff; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; z-index: 2; cursor: pointer;}
        .employee-card-name { font-weight: 700; font-size: 1.13rem; margin-bottom: 2px; margin-top: 8px;}
        .employee-card-role { color: #6c757d; font-size: 1.01rem; margin-bottom: 2px; }
        .employee-card-email, .employee-card-contact { font-size: 0.97rem; color: #22223b; margin-bottom: 1px; }
        .employee-card-contact { color: #888; font-size: 0.95rem; }
        .employee-card-job { color: #4311a5; font-size: 1.02rem; font-weight: 600;}
        .employee-list-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
        .employee-list-header .dashboard-title { margin-bottom: 0; }
        .btn-add { background: linear-gradient(90deg, #9A66ff 0%, #4311a5 100%); border: none; color: #fff; font-weight: 600; border-radius: 8px; padding: 0.7rem 1.3rem; font-size: 1.02rem; }
        .search-box { border-radius: 22px; font-size: 1.04rem; padding: 0.4rem 0.9rem; border: 1px solid #d0d7e2; width: 230px; }
        @media (max-width: 1200px) { .main-content { padding: 1rem 0.3rem 1rem 0.3rem; } .sidebar { width: 180px; padding: 1rem 0.3rem; } .main-content { margin-left: 180px; } .employee-card { width: 210px; } }
        @media (max-width: 900px) { .sidebar { left: -220px; width: 180px; padding: 1rem 0.3rem; } .sidebar.show { left: 0; } .main-content { margin-left: 0; padding: 1rem 0.5rem 1rem 0.5rem; } }
        @media (max-width: 700px) { .dashboard-title { font-size: 1.1rem; } .main-content { padding: 0.7rem 0.2rem 0.7rem 0.2rem; } .sidebar { width: 100vw; left: -100vw; padding: 0.7rem 0.2rem; } .sidebar.show { left: 0; } .main-content { padding: 0.3rem 0.1rem; } }
        @media (max-width: 500px) { .sidebar { width: 100vw; left: -100vw; padding: 0.3rem 0.01rem; } .sidebar.show { left: 0; } .main-content { padding: 0.1rem 0.01rem; } .employee-card { width: 92vw; } }
        @media (min-width: 1400px) { .sidebar { width: 260px; padding: 2rem 1rem 2rem 1rem; } .main-content { margin-left: 260px; padding: 2rem 2rem 2rem 2rem; } .employee-card { width: 270px; } }
        .modal-dialog { max-width: 700px; }
        .modal-content { border-radius: 18px; }
        .modal-header h5 { font-family: 'QuickSand', 'Poppins', Arial, sans-serif; font-size: 1.13rem; font-weight: 600;}
        .modal-body label { font-weight: 500; margin-top:0.7rem; }
        .modal-body input, .modal-body select { border-radius: 8px; font-size: 1.05rem; }
        .modal-body .form-img-preview { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; margin: 0.8rem auto; display: block; border: 3px solid #e0e7ff;}
        .modal-footer .btn { min-width: 110px; }
        .btn-save { background: #4caf50; color: #fff; }
        .btn-cancel { background: #f44336; color: #fff; }
        .alert-info { border-radius: 10px; }
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
              <a class="nav-link" href="schedule_officer_dashboard.php"><ion-icon name="home-outline"></ion-icon>Dashboard</a>
            </nav>
          </div>
          <div class="mb-4">
            <h6 class="text-uppercase px-2 mb-2">Employee Management</h6>
            <nav class="nav flex-column">
              <a class="nav-link active" href="employee_management.php"><ion-icon name="people-outline"></ion-icon>Employee List</a>
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
    </div>
    <div class="main-content col">
      <div class="topbar">
        <span class="dashboard-title">Employee Management</span>
        <div class="profile">
          <img src="../assets/images/default-profile.png" class="profile-img" alt="Profile">
          <div class="profile-info">
            <strong><?= htmlspecialchars($fullname) ?></strong><br>
            <small><?= htmlspecialchars(ucfirst($role)) ?></small>
          </div>
        </div>
      </div>
      <div class="breadcrumbs text-end mb-2">Dashboard &gt; Employee Management</div>
      <div class="employee-list-header">
        <div>
          <span class="dashboard-title" style="font-size:1.3rem;">Employee List</span>
        </div>
        <div class="d-flex align-items-center" style="gap:1rem;">
          <form method="get" class="d-flex" style="gap:0.7rem;">
            <input type="text" class="search-box" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, username, or email">
            <button class="btn btn-primary" type="submit" style="border-radius:8px;">Search</button>
          </form>
          <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <ion-icon name="person-add-outline"></ion-icon> Add Employee
          </button>
        </div>
      </div>
      <?php if ($msg): ?>
        <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <div class="employee-cards-row">
        <?php foreach ($employees as $emp): ?>
          <div class="employee-card">
            <div class="employee-card-header"></div>
            <div class="employee-card-edit" data-bs-toggle="modal" data-bs-target="#editEmployeeModal<?= $emp['id'] ?>">
              <ion-icon name="create-outline"></ion-icon>
            </div>
            <img src="<?= $profile_images[$emp['id']] ?? '../assets/images/default-profile.png' ?>" class="employee-card-img" alt="Profile">
            <div class="employee-card-name"><?= htmlspecialchars($emp['fullname']) ?></div>
            <div class="employee-card-job"><?= htmlspecialchars($emp['job_title']) ?></div>
            <div class="employee-card-role"><?= htmlspecialchars(ucfirst($emp['role'])) ?></div>
            <div class="employee-card-email"><?= htmlspecialchars($emp['email']) ?></div>
            <div class="employee-card-contact"><?= htmlspecialchars($emp['contact_number']) ?></div>
          </div>
          <!-- Edit Modal Template (Demo/UI only) -->
          <div class="modal fade" id="editEmployeeModal<?= $emp['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <form class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Employee Details</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                  <div class="col-md-4">
                    <img src="<?= $profile_images[$emp['id']] ?? '../assets/images/default-profile.png' ?>" class="form-img-preview" alt="Profile">
                    <input type="file" class="form-control mb-2" id="profile_photo" name="profile_photo" style="font-size:0.95rem;">
                    <button type="button" class="btn btn-sm btn-secondary w-100">Browse</button>
                  </div>
                  <div class="col-md-8">
                    <div class="row g-2">
                      <div class="col-6">
                        <label>Employee ID</label>
                        <input type="text" class="form-control" value="Emp<?= $emp['id'] ?>" readonly>
                      </div>
                      <div class="col-6">
                        <label>Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['fullname']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Job Title</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['job_title']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($emp['email']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Contact Number</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['contact_number']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Birthday</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['birthday']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Department</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['department']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Gender</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['gender']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Date of Joining</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['date_joined']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Status</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['status']) ?>">
                      </div>
                      <div class="col-12">
                        <label>Address</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['address']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Role</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['role']) ?>">
                      </div>
                      <div class="col-6">
                        <label>Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($emp['username']) ?>">
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-save">Save</button>
                  <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if(empty($employees)): ?>
          <div style="padding:2rem;font-size:1.1rem;">No employees found.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Employee Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <input type="hidden" name="add_employee_modal" value="1">
        <div class="col-md-4">
          <img src="../assets/images/default-profile.png" class="form-img-preview" alt="Profile">
          <input type="file" class="form-control mb-2" id="profile_photo" name="profile_photo" style="font-size:0.95rem;">
          <button type="button" class="btn btn-sm btn-secondary w-100">Browse</button>
        </div>
        <div class="col-md-8">
          <div class="row g-2">
            <div class="col-6">
              <label>Name</label>
              <input type="text" class="form-control" name="fullname" required>
            </div>
            <div class="col-6">
              <label>Job Title</label>
              <input type="text" class="form-control" name="job_title">
            </div>
            <div class="col-6">
              <label>Email</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="col-6">
              <label>Contact Number</label>
              <input type="text" class="form-control" name="contact_number">
            </div>
            <div class="col-6">
              <label>Birthday</label>
              <input type="date" class="form-control" name="birthday">
            </div>
            <div class="col-6">
              <label>Department</label>
              <input type="text" class="form-control" name="department">
            </div>
            <div class="col-6">
              <label>Gender</label>
              <select class="form-select" name="gender"><option>Male</option><option>Female</option><option>Other</option></select>
            </div>
            <div class="col-6">
              <label>Date of Joining</label>
              <input type="date" class="form-control" name="date_joined">
            </div>
            <div class="col-6">
              <label>Status</label>
              <select class="form-select" name="status"><option>Active</option><option>Inactive</option></select>
            </div>
            <div class="col-6">
              <label>Password</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="col-6">
              <label>Confirm Password</label>
              <input type="password" class="form-control" name="conf_password" required>
            </div>
            <div class="col-6">
              <label>Role</label>
              <select class="form-select" name="role">
                <option>Employee</option>
                <option>Schedule Officer</option>
                <option>HR Manager</option>
                <option>Admin</option>
                <option>Other</option>
              </select>
            </div>
            <div class="col-6">
              <label>Username</label>
              <input type="text" class="form-control" name="username" required>
            </div>
            <div class="col-12">
              <label>Address</label>
              <input type="text" class="form-control" name="address">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-save">Save</button>
        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>