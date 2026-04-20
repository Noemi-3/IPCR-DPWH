<?php
require '../config/database.php';
require '../includes/session.php';

$user_id = $_SESSION['user_id'];
$u = $conn->prepare("SELECT full_name, role FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$user = $u->get_result()->fetch_assoc();

$role = (int)$user['role'];
$is_superadmin = ($role === 0);
$can_create    = ($role === 0);

require '../includes/header.php';
require '../includes/sidebar.php';
?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
        
        <header class="h-16 bg-white dark:bg-slate-800 shadow-sm flex items-center px-6 z-10 border-b border-slate-200 dark:border-slate-700 transition-colors duration-300">
            <h1 class="text-xl font-bold text-slate-800 dark:text-white">System Dashboard</h1>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-2xl shadow-lg p-8 mb-8 text-white flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-black mb-2">Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>!</h2>
                        <p class="text-blue-100 text-lg">Select a module below to manage employee performance records.</p>
                    </div>
                    <div class="hidden md:block opacity-80">
                        <svg class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
                    
                    <a href="employees.php" class="group bg-white dark:bg-slate-800 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:border-blue-500 dark:hover:border-blue-400 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center text-center h-full">
                        <div class="h-20 w-20 bg-blue-50 dark:bg-slate-700 rounded-full flex items-center justify-center mb-6 group-hover:bg-blue-600 transition-colors">
                            <svg class="h-10 w-10 text-blue-600 dark:text-blue-400 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Employee IPCRs</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">View, edit, and evaluate Individual Performance Commitment and Review forms for all staff.</p>
                    </a>

                    <?php if ($can_create): ?>
                        <a href="create_task.php" class="group bg-white dark:bg-slate-800 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:border-purple-500 dark:hover:border-purple-400 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center text-center h-full">
                            <div class="h-20 w-20 bg-purple-50 dark:bg-slate-700 rounded-full flex items-center justify-center mb-6 group-hover:bg-purple-600 transition-colors">
                                <svg class="h-10 w-10 text-purple-600 dark:text-purple-400 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Create New Task</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Assign new Success Indicators and duties to employee IPCRs for the current semester.</p>
                        </a>
                    <?php else: ?>
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col items-center text-center opacity-70 cursor-not-allowed h-full">
                            <div class="h-20 w-20 bg-slate-200 dark:bg-slate-700 rounded-full flex items-center justify-center mb-6">
                                <svg class="h-10 w-10 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-500 dark:text-slate-400 mb-2">Create New Task</h3>
                            <p class="text-sm text-slate-400 dark:text-slate-500 font-medium flex-1">Locked. Only System Administrators have permission to assign new tasks to the database.</p>
                        </div>
                    <?php endif; ?>

                    <a href="history.php" class="group bg-white dark:bg-slate-800 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-xl hover:border-amber-500 dark:hover:border-amber-400 hover:-translate-y-1 transition-all duration-300 flex flex-col items-center text-center h-full">
                        <div class="h-20 w-20 bg-amber-50 dark:bg-slate-700 rounded-full flex items-center justify-center mb-6 group-hover:bg-amber-600 transition-colors">
                            <svg class="h-10 w-10 text-amber-500 dark:text-amber-400 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">History & Copies</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Access archived IPCR records, previous semestral ratings, and print historical documents.</p>
                    </a>

                </div>
            </div>
        </main>
    </div>

</body>
</html>