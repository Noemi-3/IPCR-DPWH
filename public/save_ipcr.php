<?php
require '../config/database.php';
require '../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CRITICAL FIX: Check if we are saving for someone else (Admin/Mod action)
    // If target_user_id exists in the form, use it. Otherwise, use the logged-in user.
    $user_id = $_POST['target_user_id'] ?? $_SESSION['user_id'];
    
    // Check if the form passed a specific period_id, otherwise use the session default
    $period_id = $_POST['period_id_override'] ?? $_SESSION['period_id'];
    
    // Inputs from the form
    $narratives = $_POST['narrative'] ?? [];
    $qs = $_POST['q'] ?? [];
    $es = $_POST['e'] ?? [];
    $ts = $_POST['t'] ?? [];

    $stmt = $conn->prepare("
        INSERT INTO task_accomplishments 
        (user_id, task_id, period_id, actual_accomplishment, q_rating, e_rating, t_rating) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        actual_accomplishment = VALUES(actual_accomplishment),
        q_rating = VALUES(q_rating),
        e_rating = VALUES(e_rating),
        t_rating = VALUES(t_rating)
    ");

    foreach ($narratives as $task_id => $narrative) {
        $q = isset($qs[$task_id]) && $qs[$task_id] !== '' ? $qs[$task_id] : null;
        $e = isset($es[$task_id]) && $es[$task_id] !== '' ? $es[$task_id] : null;
        $t = isset($ts[$task_id]) && $ts[$task_id] !== '' ? $ts[$task_id] : null;

        // Skip if everything is empty
        if (empty($narrative) && $q === null && $e === null && $t === null) {
            continue;
        }

        $stmt->bind_param("iiisiii", $user_id, $task_id, $period_id, $narrative, $q, $e, $t);
        $stmt->execute();
    }

    $stmt->close();
    
    // Redirect back to the specific user's IPCR dashboard with the success message!
    header("Location: ipcr.php?uid=" . $user_id . "&period_id=" . $period_id . "&msg=ipcr_saved");
    exit();
}
?>