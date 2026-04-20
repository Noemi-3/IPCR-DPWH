<?php
session_start();
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    $stmt = $conn->prepare("SELECT id, full_name, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        
        // CHECK IF A SPECIFIC PERIOD WAS CHOSEN ON THE LOGIN SCREEN
        if (isset($_POST['period_id']) && !empty($_POST['period_id'])) {
            $_SESSION['period_id'] = (int)$_POST['period_id'];
        } else {
            // AUTO-FETCH THE LATEST SEMESTER AS THE DEFAULT FALLBACK (For Admins)
            $period_query = $conn->query("SELECT id FROM login_periods ORDER BY year DESC, id DESC LIMIT 1");
            if ($period_query->num_rows > 0) {
                $_SESSION['period_id'] = $period_query->fetch_assoc()['id'];
            } else {
                $_SESSION['period_id'] = 1; // Failsafe
            }
        }
        
        // ROUTING LOGIC based on Role
        if ($user['role'] == 0 || $user['role'] == 2) {
            // Admins & Moderators go to the main System Dashboard
            header("Location: home.php");
        } else {
            // Regular Employees go straight to their own IPCR
            header("Location: ipcr.php");
        }
        exit();
    }
}

// If something goes wrong, send them back to login
header("Location: index.php");
exit();
?>