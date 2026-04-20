<?php
require '../config/database.php';
require '../includes/session.php';

// Security: Only Superadmin
$user_id = $_SESSION['user_id'];
$u = $conn->prepare("SELECT role FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$role = $u->get_result()->fetch_assoc()['role'] ?? 3;

if ($role !== 0) { die("Unauthorized."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = $_POST['task_id'];
    $code  = $_POST['task_code'];
    $title = $_POST['task_title'];
    $si    = $_POST['success_indicator'];

    $stmt = $conn->prepare("UPDATE tasks SET task_code=?, task_title=?, success_indicator=? WHERE id=?");
    $stmt->bind_param("sssi", $code, $title, $si, $id);
    
    if ($stmt->execute()) {
        // Return to dashboard with success message
        header("Location: ipcr.php?msg=updated");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>