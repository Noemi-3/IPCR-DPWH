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
    $remarks_data = $_POST['remarks'] ?? []; // Grab the new remarks array

    $stmt = $conn->prepare("
        INSERT INTO task_accomplishments 
        (user_id, task_id, period_id, actual_accomplishment, q_rating, e_rating, t_rating, remarks) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        actual_accomplishment = VALUES(actual_accomplishment),
        q_rating = VALUES(q_rating),
        e_rating = VALUES(e_rating),
        t_rating = VALUES(t_rating),
        remarks = VALUES(remarks)
    ");

    foreach ($narratives as $task_id => $narrative) {
        $q = isset($qs[$task_id]) && $qs[$task_id] !== '' ? $qs[$task_id] : null;
        $e = isset($es[$task_id]) && $es[$task_id] !== '' ? $es[$task_id] : null;
        $t = isset($ts[$task_id]) && $ts[$task_id] !== '' ? $ts[$task_id] : null;
        $remark = isset($remarks_data[$task_id]) && $remarks_data[$task_id] !== '' ? $remarks_data[$task_id] : null;

        // Skip if everything is empty
        if (empty($narrative) && $q === null && $e === null && $t === null && $remark === null) {
            continue;
        }

        // Added 's' at the end for the string-based remarks column
        $stmt->bind_param("iiisiiis", $user_id, $task_id, $period_id, $narrative, $q, $e, $t, $remark);
        $stmt->execute();
    }

    $stmt->close();
    
    // THE FIX: Check if this was a background Auto-Save
    if (isset($_POST['is_autosave']) && $_POST['is_autosave'] === '1') {
        // If it was an autosave, just send back a silent 200 OK signal and STOP.
        http_response_code(200);
        exit();
    }
    
    // Otherwise, redirect like normal!
    header("Location: ipcr.php?uid=" . $user_id . "&period_id=" . $period_id . "&msg=ipcr_saved");
    exit();
}
?>