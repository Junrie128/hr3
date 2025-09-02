<?php
session_start();
require_once("../includes/db.php");

if (!isset($_SESSION['employee_id']) || $_SESSION['role'] !== 'hr_manager') {
    exit("Not authorized.");
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT t.*, e.fullname, e.username
    FROM timesheets t
    JOIN employees e ON t.employee_id = e.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$ts = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ts) die("Timesheet not found.");

// Fetch attendance logs for this period
$stmt2 = $pdo->prepare("
    SELECT * FROM attendance
    WHERE employee_id = ? AND date >= ? AND date <= ?
    ORDER BY date
");
$stmt2->execute([$ts['employee_id'], $ts['period_from'], $ts['period_to']]);
$logs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<div>
    <strong>Employee:</strong> <?= htmlspecialchars($ts['fullname']) ?> (@<?= htmlspecialchars($ts['username']) ?>)<br>
    <strong>Period:</strong> <?= htmlspecialchars($ts['period_from']) ?> to <?= htmlspecialchars($ts['period_to']) ?><br>
    <strong>Submitted At:</strong> <?= htmlspecialchars($ts['submitted_at']) ?><br>
    <strong>Status:</strong> <?= htmlspecialchars(ucfirst($ts['status'] ?? 'pending')) ?>
</div>
<div class="card mt-3">
    <div class="card-header">Attendance Logs</div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <th>IP In</th>
                    <th>IP Out</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['date']) ?></td>
                    <td><?= htmlspecialchars($log['time_in']) ?></td>
                    <td><?= htmlspecialchars($log['time_out']) ?></td>
                    <td><?= htmlspecialchars($log['status']) ?></td>
                    <td><?= htmlspecialchars($log['ip_in'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['ip_out'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center">No attendance records for this period.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>