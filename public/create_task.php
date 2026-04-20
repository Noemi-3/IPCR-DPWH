<?php
require '../config/database.php';
require '../includes/session.php';

// 1. Security Check & User Data
$user_id = $_SESSION['user_id'];
$u = $conn->prepare("SELECT full_name, role FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$user = $u->get_result()->fetch_assoc();
$role = (int)($user['role'] ?? 3);

// THIS IS CRITICAL FOR THE SIDEBAR TO KNOW YOU ARE AN ADMIN
$is_superadmin = ($role === 0); 

if (!$is_superadmin) { header("Location: ipcr.php"); exit(); }

// 2. Fetch all active periods
$periods_query = $conn->query("SELECT id, month, year FROM login_periods ORDER BY year ASC, id ASC");

// 3. Fetch all users
$users_query = $conn->query("SELECT id, full_name, role FROM users WHERE (role IS NULL OR role NOT IN (2)) AND id < 100 ORDER BY full_name ASC");

// --- PLACEHOLDER DATA FOR THE RATING MATRIX ---
$q_placeholders = [
    5 => 'e.g., with no error',
    4 => 'e.g., with 1-2 minor errors',
    3 => 'e.g., with minor error',
    2 => 'e.g., with 3-4 minor errors',
    1 => 'e.g., with major error'
];
$e_placeholders = [
    5 => 'e.g., 100%',
    4 => 'e.g., 90-99.99%',
    3 => 'e.g., 80-89.99%',
    2 => 'e.g., 70-79.99%',
    1 => 'e.g., below 70%'
];
$t_placeholders = [
    5 => 'e.g., 30 minutes',
    4 => 'e.g., 45 minutes',
    3 => 'e.g., 1 hour',
    2 => 'e.g., 1 hour and 15 minutes',
    1 => 'e.g., 1 hour and 30 minutes'
];

// === INCLUDE UI COMPONENTS ===
require '../includes/header.php';
require '../includes/sidebar.php';
?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
        
        <header class="h-16 bg-white dark:bg-slate-800 shadow-sm flex items-center justify-between px-6 z-10 border-b border-slate-200 dark:border-slate-700 transition-colors duration-300">
            <h1 class="text-xl font-bold text-slate-800 dark:text-white flex items-center">
                <svg class="w-6 h-6 mr-2 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                Create & Assign New Task
            </h1>
        </header>

        <main class="flex-1 overflow-y-auto p-6 flex justify-center">
            <div class="w-full max-w-7xl">

                <?php if (isset($_GET['err']) && $_GET['err'] === 'duplicate_code'): ?>
                <div class="mb-8 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-xl shadow-md flex items-center">
                    <div class="flex-shrink-0 bg-white dark:bg-red-800 rounded-full p-1">
                        <svg class="h-6 w-6 text-red-500 dark:text-red-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-bold text-red-800 dark:text-red-400">Task Creation Failed</h3>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-0.5">The Task Code you entered already exists. Please use a unique identifier.</p>
                    </div>
                </div>
                <?php endif; ?>
            
                <form action="create_task_backend.php" method="POST" class="grid grid-cols-1 xl:grid-cols-3 gap-8 pb-12">
                    
                    <div class="xl:col-span-2 space-y-8">
                        
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-300">
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center space-x-3 bg-slate-50/50 dark:bg-slate-800/50">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">1</div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">Core Information</h2>
                            </div>
                            
                            <div class="p-6 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Task Code / Number <span class="text-red-500">*</span></label>
                                        <input type="text" name="task_code" required placeholder="e.g., 1.4" class="w-full p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 outline-none transition shadow-sm placeholder-slate-400 dark:placeholder-slate-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Output Category</label>
                                        <input type="text" name="output_category" placeholder="e.g., Network Uptime" class="w-full p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 outline-none transition shadow-sm placeholder-slate-400 dark:placeholder-slate-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Output / Task Title <span class="text-red-500">*</span></label>
                                    <textarea name="task_title" rows="2" required placeholder="Enter the main task description..." class="w-full p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 outline-none transition shadow-sm placeholder-slate-400 dark:placeholder-slate-500 resize-y"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Success Indicators (General Target) <span class="text-red-500">*</span></label>
                                    <textarea name="success_indicator" rows="2" required placeholder="Enter the overall success indicator..." class="w-full p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 outline-none transition shadow-sm placeholder-slate-400 dark:placeholder-slate-500 resize-y"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-300">
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                                <div class="flex items-center space-x-3">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm">2</div>
                                    <h2 class="text-lg font-bold text-slate-800 dark:text-white">Rating Calibration</h2>
                                </div>
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold rounded-full">Automation Data</span>
                            </div>
                            
                            <div class="p-6">
                                <div class="bg-blue-50/50 dark:bg-slate-900/50 p-5 rounded-xl border border-blue-100 dark:border-slate-700 mb-8">
                                    <h3 class="text-sm font-bold text-blue-900 dark:text-blue-300 mb-4 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        Target Standards (Rating of 5)
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Quality</label>
                                            <input type="text" name="qet_quality" placeholder="e.g., with no error" class="w-full p-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition shadow-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Efficiency</label>
                                            <input type="text" name="qet_efficiency" placeholder="e.g., 100%" class="w-full p-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition shadow-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">Timeliness</label>
                                            <input type="text" name="qet_timeliness" placeholder="e.g., 30 minutes" class="w-full p-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition shadow-sm">
                                        </div>
                                    </div>
                                </div>

                                <div class="overflow-hidden border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-slate-100 dark:bg-slate-800 text-xs text-slate-600 dark:text-slate-300 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                                                <th class="px-4 py-4 font-bold w-16 text-center">Score</th>
                                                <th class="px-4 py-4 font-bold w-1/3 border-l border-slate-200 dark:border-slate-700">Quality (Q)</th>
                                                <th class="px-4 py-4 font-bold w-1/3 border-l border-slate-200 dark:border-slate-700">Efficiency (E)</th>
                                                <th class="px-4 py-4 font-bold w-1/3 border-l border-slate-200 dark:border-slate-700">Timeliness (T)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50 text-sm">
                                            <?php for ($i = 5; $i >= 1; $i--): 
                                                // Dynamic colors for the score badges
                                                $badgeColor = 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
                                                if($i == 5) $badgeColor = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400';
                                                if($i == 1) $badgeColor = 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400';
                                            ?>
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/80 transition-colors group">
                                                <td class="px-4 py-3 text-center">
                                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full font-black <?= $badgeColor ?>"><?= $i ?></span>
                                                </td>
                                                <td class="p-2 border-l border-slate-100 dark:border-slate-700/50">
                                                    <input type="text" name="matrix[Q][<?= $i ?>]" placeholder="<?= $q_placeholders[$i] ?>" class="w-full p-2.5 bg-transparent text-slate-900 dark:text-white placeholder-slate-300 dark:placeholder-slate-600 border border-transparent hover:bg-white dark:hover:bg-slate-900 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-500/30 rounded-lg outline-none transition">
                                                </td>
                                                <td class="p-2 border-l border-slate-100 dark:border-slate-700/50">
                                                    <input type="text" name="matrix[E][<?= $i ?>]" placeholder="<?= $e_placeholders[$i] ?>" class="w-full p-2.5 bg-transparent text-slate-900 dark:text-white placeholder-slate-300 dark:placeholder-slate-600 border border-transparent hover:bg-white dark:hover:bg-slate-900 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-500/30 rounded-lg outline-none transition">
                                                </td>
                                                <td class="p-2 border-l border-slate-100 dark:border-slate-700/50">
                                                    <input type="text" name="matrix[T][<?= $i ?>]" placeholder="<?= $t_placeholders[$i] ?>" class="w-full p-2.5 bg-transparent text-slate-900 dark:text-white placeholder-slate-300 dark:placeholder-slate-600 border border-transparent hover:bg-white dark:hover:bg-slate-900 focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-500/30 rounded-lg outline-none transition">
                                                </td>
                                            </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-1">
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden sticky top-6 transition-colors duration-300 flex flex-col h-[calc(100vh-10rem)]">
                            
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center space-x-3 bg-slate-50/50 dark:bg-slate-800/50 shrink-0">
                                <div class="h-8 w-8 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-sm">3</div>
                                <h2 class="text-lg font-bold text-slate-800 dark:text-white">Deployment</h2>
                            </div>

                            <div class="p-6 flex-1 flex flex-col min-h-0 overflow-hidden">
                                <div class="mb-6 shrink-0">
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Target Semester <span class="text-red-500">*</span></label>
                                    <select name="period_id" required class="w-full p-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500 outline-none shadow-sm cursor-pointer transition">
                                        <option value="" disabled selected>Select Time Period...</option>
                                        <?php while ($p = $periods_query->fetch_assoc()): ?>
                                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['month'] . ' ' . $p['year']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="flex-1 flex flex-col min-h-0">
                                    <div class="flex justify-between items-end mb-3 shrink-0">
                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Assign To Staff</label>
                                        <button type="button" id="selectAllBtn" class="text-xs text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 font-bold px-2 py-1 rounded hover:bg-amber-50 dark:hover:bg-amber-900/20 transition">Select All</button>
                                    </div>
                                    
                                    <div class="flex-1 overflow-y-auto bg-slate-50 dark:bg-slate-900/30 border border-slate-200 dark:border-slate-700 rounded-xl p-2 shadow-inner space-y-1 custom-scrollbar">
                                        <?php while ($u = $users_query->fetch_assoc()): 
                                            $roleText = ($u['role'] == 0) ? "Superadmin" : (($u['role'] == 1) ? "Admin" : (($u['role'] == 2) ? "Moderator" : "Staff"));
                                            $roleColor = ($u['role'] == 0 || $u['role'] == 1) ? "bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400" : "bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300";
                                        ?>
                                        <label class="flex items-center p-3 hover:bg-white dark:hover:bg-slate-800 rounded-lg cursor-pointer transition border border-transparent hover:border-slate-200 dark:hover:border-slate-600 hover:shadow-sm">
                                            <input type="checkbox" name="assigned_users[]" value="<?= $u['id'] ?>" class="user-checkbox h-5 w-5 text-indigo-600 dark:bg-slate-700 dark:border-slate-500 rounded border-slate-300 focus:ring-indigo-500 transition">
                                            <div class="ml-4 flex-1">
                                                <span class="block text-sm font-bold text-slate-800 dark:text-slate-200 leading-none mb-1"><?= htmlspecialchars($u['full_name']) ?></span>
                                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full <?= $roleColor ?>"><?= $roleText ?></span>
                                            </div>
                                        </label>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 border-t border-slate-100 dark:border-slate-700 shrink-0 bg-white dark:bg-slate-800">
                                <button type="submit" class="w-full flex justify-center items-center px-4 py-4 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/50 transform hover:-translate-y-0.5 transition-all duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                    Deploy Task
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php require '../includes/ipcr_modals.php'; ?>

    <style>
        /* Custom thin scrollbar for the employee list */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #475569; }
    </style>

    <script>
        document.getElementById('selectAllBtn').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            this.textContent = allChecked ? "Select All" : "Deselect All";
            
            // Add a visual pop effect to the button
            this.classList.add('scale-110');
            setTimeout(() => this.classList.remove('scale-110'), 150);
        });
    </script>
</body>
</html>