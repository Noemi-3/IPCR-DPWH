<?php
require '../config/database.php';
require '../includes/session.php';

// Fetch User Info to check roles
$user_id = $_SESSION['user_id'];
$u = $conn->prepare("SELECT full_name, role FROM users WHERE id=?");
$u->bind_param("i", $user_id);
$u->execute();
$user = $u->get_result()->fetch_assoc();
$role = (int)($user['role'] ?? 3);

// Set the variable that the sidebar checks!
$is_superadmin = ($role === 0);
//Define who can delete history
$can_delete = ($role === 0 || $role === 1 || $role === 2);
$can_view_all = ($role === 0 || $role === 2); // Admins and Mods can see everyone

if ($can_view_all) {
    // ADMIN/MOD QUERY: Fetch ALL employee submissions
    $sql = "
    SELECT 
        p.id as period_id,
        p.month, 
        p.year,
        u.id as emp_id,
        u.full_name,
        MAX(ta.created_at) as last_updated
    FROM task_accomplishments ta
    JOIN login_periods p ON ta.period_id = p.id
    JOIN users u ON ta.user_id = u.id
    WHERE u.id < 100 -- Hide system demo accounts
    GROUP BY p.id, u.id
    ORDER BY p.year DESC, p.month DESC, u.full_name ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
} else {
    // EMPLOYEE QUERY: Fetch ONLY their own submissions
    $sql = "
    SELECT 
        p.id as period_id,
        p.month, 
        p.year,
        u.id as emp_id,
        u.full_name,
        MAX(ta.created_at) as last_updated
    FROM task_accomplishments ta
    JOIN login_periods p ON ta.period_id = p.id
    JOIN users u ON ta.user_id = u.id
    WHERE ta.user_id = ?
    GROUP BY p.id, u.id
    ORDER BY p.year DESC, p.month DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

$history = $stmt->get_result();

// === INCLUDE MODULAR UI COMPONENTS ===
require '../includes/header.php';
require '../includes/sidebar.php';
?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
        <header class="h-16 bg-white dark:bg-slate-800 shadow-sm flex items-center justify-between px-6 z-10 border-b border-slate-200 dark:border-slate-700 transition-colors duration-300">
            <h1 class="text-xl font-bold text-slate-800 dark:text-white"><?= $can_view_all ? 'Submission History' : 'Submission History' ?></h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-slate-500 dark:text-slate-400">Welcome, <?= htmlspecialchars($user['full_name']) ?></span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-300">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <?php if($can_view_all): ?>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Employee</th>
                                <?php endif; ?>
                                
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Period</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Last Updated</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            <?php if($history->num_rows > 0): ?>
                                <?php while($row = $history->fetch_assoc()): 
                                    $period_text = strtoupper($row['month'] . ' ' . $row['year']);
                                    $date = date("M d, Y h:i A", strtotime($row['last_updated']));
                                ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors duration-200">
                                    
                                    <?php if($can_view_all): ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-lg">
                                                <?= substr($row['full_name'], 0, 1) ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($row['full_name']) ?></div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400">ID: #<?= $row['emp_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white"><?= $period_text ?></div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">Period ID: #<?= $row['period_id'] ?></div>
                                    </td>
                                    
                                    <?php else: ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-400">
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-slate-900 dark:text-white"><?= $period_text ?></div>
                                                <div class="text-xs text-slate-500 dark:text-slate-400">ID: #<?= $row['period_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <?php endif; ?>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-500 dark:text-slate-400"><?= $date ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-400 border border-green-200 dark:border-green-800/50">
                                            <?= $can_view_all ? 'Rated / Saved' : 'Submitted' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
    <div class="flex items-center justify-end space-x-4 h-full">
        <a href="print_ipcr.php?period_id=<?= $row['period_id'] ?><?= $can_view_all ? '&uid=' . $row['emp_id'] : '' ?>" target="_blank" class="text-blue-600 hover:text-blue-900 font-bold hover:underline flex items-center">
            View Copy
            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
        </a>

        <?php if($can_delete): ?>
        <?php $target_emp_id = isset($row['emp_id']) ? $row['emp_id'] : $user_id; ?>
        <button onclick="openDeleteHistoryModal(<?= $row['period_id'] ?>, '<?= $period_text ?>', <?= $target_emp_id ?>)" class="text-red-500 hover:text-red-700 font-bold transition flex items-center cursor-pointer">
            Delete
            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
        </button>
        <?php endif; ?>
    </div>
</td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $can_view_all ? '5' : '4' ?>" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">
                                        No historical IPCRs found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </main>
    <?php if($can_delete): ?>
<div id="delete-history-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeDeleteHistoryModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
            <form action="delete_history_backend.php" method="POST">
                <input type="hidden" name="period_id" id="delete-history-period-id">
                <input type="hidden" name="target_user_id" id="delete-history-target-id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-slate-900">Delete IPCR Record</h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500">Are you sure you want to permanently delete the IPCR record for <span id="delete-history-period-name" class="font-bold text-slate-800"></span>? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-100">
                    <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition">Yes, Delete</button>
                    <button type="button" onclick="closeDeleteHistoryModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openDeleteHistoryModal(periodId, periodName, targetId) {
        document.getElementById('delete-history-period-id').value = periodId;
        document.getElementById('delete-history-target-id').value = targetId;
        document.getElementById('delete-history-period-name').textContent = periodName;
        document.getElementById('delete-history-modal').classList.remove('hidden');
    }
    function closeDeleteHistoryModal() {
        document.getElementById('delete-history-modal').classList.add('hidden');
    }
</script>
<?php endif; ?>
</body>
</html>