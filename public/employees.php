<?php
require '../config/database.php';
require '../includes/session.php';

// Fetch current logged-in user to check permissions
$user_id = $_SESSION['user_id'];
$u = $conn->prepare("SELECT full_name, role FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$logged_in_user = $u->get_result()->fetch_assoc();

$role = (int)$logged_in_user['role'];
$can_view_others = ($role === 0 || $role === 2); 

if (!$can_view_others) {
    header("Location: ipcr.php");
    exit();
}

// Determine which period we are viewing. 
// If they used the dropdown, save it. Otherwise, use their session default.
if (isset($_GET['period_id']) && is_numeric($_GET['period_id'])) {
    $selected_period = intval($_GET['period_id']);
    $_SESSION['period_id'] = $selected_period; // Save it so it remembers!
} else {
    $selected_period = $_SESSION['period_id'];
}

// Fetch all available periods for the dropdown menu
$periods_query = $conn->query("SELECT id, month, year FROM login_periods ORDER BY year DESC, id DESC");

// Fetch all employees
// Fetch real employees only (Hide system accounts with IDs over 100)
$employees_query = $conn->query("SELECT id, full_name, position, division, role FROM users WHERE id < 100 ORDER BY full_name ASC");

require '../includes/header.php';
require '../includes/sidebar.php';
?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
        <header class="h-16 bg-white dark:bg-slate-800 shadow-sm flex items-center justify-between px-6 z-10 border-b border-slate-200 dark:border-slate-700 transition-colors duration-300">
            <div class="flex items-center space-x-4">
                <a href="home.php" class="text-slate-400 dark:text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <h1 class="text-xl font-bold text-slate-800 dark:text-white">Employee Directory</h1>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                
                <div class="flex flex-col md:flex-row justify-between items-center bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 mb-6 transition-colors duration-300">
                    <p class="text-slate-500 dark:text-slate-400 mb-4 md:mb-0 text-sm">Select an employee below to view or evaluate their IPCR.</p>
                    
                    <form method="GET" class="flex items-center space-x-3 w-full md:w-auto">
                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Semester:</label>
                        <select name="period_id" onchange="this.form.submit()" class="flex-1 md:w-64 p-2 bg-slate-50 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm cursor-pointer transition">
                            <?php while($p = $periods_query->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>" <?= ($p['id'] == $selected_period) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(strtoupper($p['month'] . ' ' . $p['year'])) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($emp = $employees_query->fetch_assoc()): 
                        $is_admin = ($emp['role'] == 0 || $emp['role'] == 1);
                    ?>
                    <a href="ipcr.php?uid=<?= $emp['id'] ?>&period_id=<?= $selected_period ?>" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex items-center space-x-4 hover:shadow-md hover:border-blue-400 dark:hover:border-blue-500 hover:-translate-y-1 transition-all duration-200 group">
                        
                        <div class="h-14 w-14 rounded-full flex items-center justify-center text-xl font-bold flex-shrink-0 <?= $is_admin ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400' : 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400' ?>">
                            <?= substr($emp['full_name'], 0, 1) ?>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                <?= htmlspecialchars($emp['full_name']) ?>
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5"><?= htmlspecialchars($emp['position'] ?: 'No Position Set') ?></p>
                            <p class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-semibold mt-1 truncate"><?= htmlspecialchars($emp['division'] ?: 'No Division Set') ?></p>
                        </div>
                        
                        <div class="text-slate-300 dark:text-slate-600 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>

            </div>
        </main>
    </div>

</body>
</html>