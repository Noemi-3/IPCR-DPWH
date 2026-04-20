<?php
require '../config/database.php';
require '../includes/session.php';

// 1. Security Check
$user_id = $_SESSION['user_id'];
$u = $conn->prepare("SELECT full_name, role FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$user = $u->get_result()->fetch_assoc();
$role = (int)($user['role'] ?? 3);

// Only Superadmin (0)
if ($role !== 0) { die("Unauthorized access."); }

$id = $_GET['id'] ?? null;
if (!$id) { die("Invalid Task ID"); }

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code  = $_POST['task_code'];
    $title = $_POST['task_title'];
    $si    = $_POST['success_indicator'];
    
    $stmt = $conn->prepare("UPDATE tasks SET task_code=?, task_title=?, success_indicator=? WHERE id=?");
    $stmt->bind_param("sssi", $code, $title, $si, $id);
    
    if ($stmt->execute()) {
        header("Location: ipcr.php?msg=updated");
        exit();
    } else {
        $error = "Failed to update.";
    }
}

// 3. Fetch Current Data
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task | IPCR System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }</style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-64 bg-slate-900 text-white hidden md:flex flex-col shadow-xl z-20">
        <div class="h-16 flex items-center px-6 border-b border-slate-800">
            <div class="font-bold text-xl tracking-wider text-blue-400">DPWH<span class="text-white">IPCR</span></div>
        </div>
        <div class="flex-1 py-4 px-3">
            <a href="ipcr.php" class="group flex items-center px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-md transition">
                <svg class="mr-3 h-5 w-5 text-slate-400 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50">
        
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h1 class="text-xl font-bold text-slate-800">Edit Task Definition</h1>
            <div class="text-sm text-slate-500">Task ID: <span class="font-mono font-bold text-slate-700">#<?= $id ?></span></div>
        </header>

        <main class="flex-1 overflow-y-auto p-6 flex justify-center">
            <div class="w-full max-w-3xl">
                
                <form method="POST" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    
                    <div class="p-6 space-y-6">
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Editing this task will update the text for <strong>all employees</strong> assigned to this task code.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Task Code / Number
                            </label>
                            <input type="text" name="task_code" value="<?= htmlspecialchars($task['task_code']) ?>" 
                                   class="w-full sm:w-1/3 p-3 bg-white border border-slate-300 rounded-lg text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm"
                                   placeholder="e.g. 1.1">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Output / Task Title
                            </label>
                            <textarea name="task_title" rows="3" 
                                      class="w-full p-3 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm placeholder-slate-400"
                                      placeholder="Enter the main task description..."><?= htmlspecialchars($task['task_title']) ?></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                                Success Indicators (Targets)
                            </label>
                            <textarea name="success_indicator" rows="6" 
                                      class="w-full p-3 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm placeholder-slate-400 font-mono"
                                      placeholder="Enter the detailed success indicators..."><?= htmlspecialchars($task['success_indicator']) ?></textarea>
                            <p class="mt-2 text-xs text-slate-400">Tip: Ensure keywords match your Rating Matrix for auto-generation.</p>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex items-center justify-end space-x-3 border-t border-slate-200">
                        <a href="ipcr.php" class="inline-flex items-center px-4 py-2 border border-slate-300 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                            Save Changes
                        </button>
                    </div>

                </form>

            </div>
        </main>
    </div>
</body>
</html>