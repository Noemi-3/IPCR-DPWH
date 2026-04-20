<?php
require '../config/database.php';
require '../includes/session.php';

// 1. Security
$user_id = $_SESSION['user_id'];
$u = $conn->prepare("SELECT role FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$role = $u->get_result()->fetch_assoc()['role'] ?? 3;

if ($role !== 0) { die("Unauthorized access."); }

// 2. Get Undo Parameters
$task_id   = $_GET['task_id'];
$period_id = $_GET['period_id'];
$target_user_id = $_GET['target_user_id'];

// 3. Restore the Assignment
// IGNORE ensures we don't crash if it already exists
$stmt = $conn->prepare("INSERT IGNORE INTO user_tasks (user_id, task_id, period_id) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $target_user_id, $task_id, $period_id);
$stmt->execute();

// 4. Redirect back with success message
header("Location: ipcr.php?period_id=" . $period_id . "&msg=restored");
exit();
?>