<?php
require '../config/database.php';
require '../includes/session.php';

// 1. Security Check
$user_id = $_SESSION['user_id'];
$u = $conn->prepare("SELECT role FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$role = $u->get_result()->fetch_assoc()['role'] ?? 3;

if ($role !== 0) { die("Unauthorized access."); }

// 2. Get Parameters
$task_id        = $_GET['task_id'] ?? null;
$period_id      = $_GET['period_id'] ?? null;
$target_user_id = $_GET['target_user_id'] ?? null;

if (!$task_id || !$period_id || !$target_user_id) { die("Missing parameters."); }

// 3. Delete ONLY the Assignment (User + Task Link)
// We DO NOT delete from 'task_accomplishments' so that data is preserved for Undo.
$stmt = $conn->prepare("DELETE FROM user_tasks WHERE user_id = ? AND task_id = ? AND period_id = ?");
$stmt->bind_param("iii", $target_user_id, $task_id, $period_id);

if ($stmt->execute()) {
    // Redirect with Undo Parameters
    $params = "msg=deleted&undo_task=$task_id&undo_period=$period_id&undo_user=$target_user_id";
    header("Location: ipcr.php?" . $params);
    exit();
} else {
    echo "Error deleting task: " . $conn->error;
}
?>