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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code     = trim($_POST['task_code']);
    $title    = trim($_POST['task_title']);
    $category = trim($_POST['output_category']);
    $si       = trim($_POST['success_indicator']);
    
    $period_id = $_POST['period_id'] ?? null;
    $assigned_users = $_POST['assigned_users'] ?? [];
    $matrix = $_POST['matrix'] ?? [];

    if (empty($code) || empty($title) || empty($si)) {
        die("Error: Required fields are missing.");
    }

    $sql = "INSERT INTO tasks (task_code, task_title, output_category, success_indicator, qet_quality, qet_efficiency, qet_timeliness) VALUES (?, ?, ?, ?, 'N/A', 'N/A', 'N/A')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $code, $title, $category, $si);
    
    if ($stmt->execute()) {
        $new_task_id = $conn->insert_id;
        $task_id_string = (string)$new_task_id; // We will use this ID for the matrix

        if ($period_id && !empty($assigned_users)) {
            $assign_stmt = $conn->prepare("INSERT IGNORE INTO user_tasks (user_id, task_id, period_id) VALUES (?, ?, ?)");
            foreach ($assigned_users as $uid) {
                $assign_stmt->bind_param("iii", $uid, $new_task_id, $period_id);
                $assign_stmt->execute();
            }
        }

        $matrix_stmt = $conn->prepare("INSERT INTO rating_matrix (success_indicator, category, rating, input_value) VALUES (?, ?, ?, ?)");
        
        foreach (['Q', 'E', 'T'] as $cat) {
            for ($r = 5; $r >= 1; $r--) {
                if (!empty(trim($matrix[$cat][$r]))) {
                    $input_val = trim($matrix[$cat][$r]);
                    // CHANGED: We now insert the unique Task ID instead of the Task Code
                    $matrix_stmt->bind_param("ssis", $task_id_string, $cat, $r, $input_val);
                    $matrix_stmt->execute();
                }
            }
        }

        header("Location: ipcr.php?msg=task_created_assigned");
        exit();

    } else {
        echo "Error creating task: " . $conn->error;
    }
}
?>