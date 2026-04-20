<?php
require '../config/database.php';
require '../includes/session.php';

// 1. Check Permissions
$user_id = $_SESSION['user_id'];
$u = $conn->prepare("SELECT role FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$user = $u->get_result()->fetch_assoc();
$role = (int)($user['role'] ?? 3);

// Only Superadmin (0), Admin (1), or Moderator (2) can delete
if ($role > 2) {
    header("Location: history.php?err=unauthorized");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['period_id']) && isset($_POST['target_user_id'])) {
    $period_id = (int)$_POST['period_id'];
    $target_user_id = (int)$_POST['target_user_id']; 

    // Delete all ratings/accomplishments for this specific user in this specific period
    $stmt = $conn->prepare("DELETE FROM task_accomplishments WHERE user_id = ? AND period_id = ?");
    $stmt->bind_param("ii", $target_user_id, $period_id);
    
    if ($stmt->execute()) {
        header("Location: history.php?msg=deleted");
    } else {
        header("Location: history.php?err=failed");
    }
    exit();
}

header("Location: history.php");
exit();