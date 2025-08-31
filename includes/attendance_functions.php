<?php
require_once "db.php";

/**
 * Clock in the employee for today.
 */
function clockIn($employeeId) {
    global $pdo;
    if (!$employeeId) return "User not found.";
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Prevent double clock-in
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
    $stmt->execute([$employeeId, $today]);
    $row = $stmt->fetch();
    if ($row && $row['time_in']) {
        return "Already clocked in today at {$row['time_in']}.";
    }
    if ($row) {
        // Update existing attendance row if already present for today
        $stmt = $pdo->prepare("UPDATE attendance SET time_in = ?, ip_in = ? WHERE id = ?");
        $stmt->execute([$now, $ip, $row['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, time_in, ip_in, status) VALUES (?, ?, ?, ?, 'Present')");
        $stmt->execute([$employeeId, $today, $now, $ip]);
    }
    return "Clocked in at $now. IP: $ip";
}

/**
 * Clock out the employee for today.
 */
function clockOut($employeeId) {
    global $pdo;
    if (!$employeeId) return "User not found.";
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
    $stmt->execute([$employeeId, $today]);
    $row = $stmt->fetch();
    if (!$row || !$row['time_in']) return "You have not clocked in today.";
    if ($row['time_out']) return "Already clocked out today at {$row['time_out']}.";
    $stmt = $pdo->prepare("UPDATE attendance SET time_out = ?, ip_out = ? WHERE id = ?");
    $stmt->execute([$now, $ip, $row['id']]);
    return "Clocked out at $now. IP: $ip";
}

/**
 * Returns true if the employee is currently clocked in.
 */
function isClockedIn($employeeId) {
    global $pdo;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
    $stmt->execute([$employeeId, $today]);
    $row = $stmt->fetch();
    return ($row && $row['time_in'] && !$row['time_out']);
}

/**
 * List attendance logs for this employee, newest first.
 */
function viewAttendanceLogsCutoff($employeeId, $from, $to) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date >= ? AND date <= ? ORDER BY date DESC");
    $stmt->execute([$employeeId, $from, $to]);
    return $stmt->fetchAll();
}

/**
 * Mark all attendance records in the cutoff as submitted to timesheet.
 */
function submitAttendanceToTimesheet($employeeId, $from, $to) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE attendance SET submitted_to_timesheet = 1 WHERE employee_id = ? AND date >= ? AND date <= ?");
    $stmt->execute([$employeeId, $from, $to]);
}

function getAttendanceForDate($employeeId, $date) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ? LIMIT 1");
    $stmt->execute([$employeeId, $date]);
    return $stmt->fetch();
}

function createTimesheetSubmission($employeeId, $from, $to) {
    global $pdo;
    // Prevent duplicate for same period
    $stmt = $pdo->prepare("SELECT id FROM timesheets WHERE employee_id = ? AND period_from = ? AND period_to = ?");
    $stmt->execute([$employeeId, $from, $to]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO timesheets (employee_id, period_from, period_to, submitted_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$employeeId, $from, $to]);
    }
}
?>