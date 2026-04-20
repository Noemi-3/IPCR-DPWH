<?php
require '../config/database.php';
require '../includes/session.php';

// 1. Get LOGGED-IN User's ID and Permissions
$logged_in_user_id = $_SESSION['user_id'];

$u_logged = $conn->prepare("SELECT role FROM users WHERE id=?");
$u_logged->bind_param("i", $logged_in_user_id);
$u_logged->execute();
$logged_role = (int)$u_logged->get_result()->fetch_assoc()['role'];

// Set capabilities based on who is holding the mouse (Logged-in user)
$is_superadmin = ($logged_role === 0); 
$can_create    = ($logged_role === 0); 
$can_edit      = ($logged_role === 0 || $logged_role === 2); 
$can_delete    = ($logged_role === 0 || $logged_role === 2); 

$input_state = $can_edit ? '' : 'disabled';
$content_editable_state = $can_edit ? 'true' : 'false';

// 2. Determine the TARGET User (Whose IPCR are we looking at?)
if (isset($_GET['uid']) && $can_edit) {
    $target_user_id = intval($_GET['uid']);
} else {
    $target_user_id = $logged_in_user_id;
}

// 3. Handle Semestral Period
if (isset($_GET['period_id']) && is_numeric($_GET['period_id'])) {
    $period_id = intval($_GET['period_id']);
} else {
    $period_id = $_SESSION['period_id'];
}

$show_undo = false;
if (isset($_GET['msg']) && $_GET['msg'] == 'deleted' && isset($_GET['undo_task'])) {
    $show_undo = true;
    $undo_link = "undo_task_assignment.php?task_id=" . $_GET['undo_task'] . 
                 "&period_id=" . $_GET['undo_period'] . 
                 "&target_user_id=" . $_GET['undo_user'];
}

// 4. Fetch TARGET User's details for the Commitment Statement Header
$u_target = $conn->prepare("SELECT full_name, position, division, role FROM users WHERE id=?");
$u_target->bind_param("i", $target_user_id);
$u_target->execute();
$user = $u_target->get_result()->fetch_assoc();

$p = $conn->prepare("SELECT month, year FROM login_periods WHERE id=?");
$p->bind_param("i", $period_id);
$p->execute();
$period = $p->get_result()->fetch_assoc();
$period_display = $period['month'] . ' ' . $period['year']; 

$sql = "
SELECT t.id AS task_id, t.task_code, t.task_title, t.success_indicator
FROM user_tasks ut
JOIN tasks t ON t.id = ut.task_id
WHERE ut.user_id = ? AND ut.period_id = ?
ORDER BY CAST(SUBSTRING_INDEX(t.task_code,'.',1) AS UNSIGNED), CAST(SUBSTRING_INDEX(t.task_code,'.',-1) AS UNSIGNED)
";
$stmt = $conn->prepare($sql);

$stmt->bind_param("ii", $target_user_id, $period_id);
$stmt->execute();
$tasks = $stmt->get_result();

$ratingMatrix = [];
$matrixRes = $conn->query("SELECT success_indicator, category, input_value, rating FROM rating_matrix");
if ($matrixRes) {
    while ($row = $matrixRes->fetch_assoc()) {
        $si   = trim((string)$row['success_indicator']);
        $cat  = trim((string)$row['category']);
        $rate = (int)$row['rating'];
        $val  = trim((string)$row['input_value']);
        if ($si !== '' && $cat !== '' && $rate > 0) {
            $ratingMatrix[$si][$cat][$rate] = $val;
        }
    }
}
$ratingMatrixJson = json_encode($ratingMatrix, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// === INCLUDE UI COMPONENTS ===
require '../includes/header.php';
require '../includes/sidebar.php';
?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
        
        <header class="h-16 bg-white dark:bg-slate-800 shadow-sm flex items-center justify-between px-6 z-10 border-b border-slate-200 dark:border-slate-700 transition-colors duration-300">
            <h1 class="text-xl font-bold text-slate-800 dark:text-white">Performance Review <span class="text-slate-400 dark:text-slate-500 text-sm font-normal ml-2">Period: <?= $period_display ?></span></h1>
            
            <div class="flex items-center space-x-6">
                <div class="text-right">
                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide font-semibold">Final Rating</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 leading-none" id="header-grand-avg">0.00</p>
                </div>
                
                <div class="h-8 w-px bg-slate-200 dark:bg-slate-600"></div>
                
                <div class="w-40">
                    <div class="flex justify-between items-end mb-1.5">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wide font-bold">Grading Progress</p>
                        <p class="text-[11px] font-black text-blue-600 dark:text-blue-400 leading-none" id="progress-text">0/0</p>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 shadow-inner overflow-hidden">
                        <div id="progress-bar-fill" class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full transition-all duration-500 ease-out" style="width: 0%"></div>
                    </div>
                </div>

                <span id="header-adjectival" class="hidden"></span>
                
                <?php if($can_edit): ?>
                <button type="submit" form="ipcr-form" class="ml-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">Save Changes</button>
                <?php else: ?>
                 <span class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-slate-200 border border-slate-200 dark:border-slate-600">Read Only</span>
                <?php endif; ?>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-6xl mx-auto">
                
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm p-6 mb-6 border-l-4 border-blue-500 dark:border-blue-400 transition-colors duration-300">
                    <div class="flex">
                        <div class="flex-shrink-0"><svg class="h-5 w-5 text-blue-400 dark:text-blue-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg></div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">Commitment Statement</h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-200/90 leading-relaxed">
                                I, <span class="font-bold border-b border-blue-300 dark:border-blue-500/50"><?= htmlspecialchars($user['full_name']) ?></span>, 
                                <span class="font-bold border-b border-blue-300 dark:border-blue-500/50"><?= htmlspecialchars($user['position']) ?></span> of 
                                <span class="font-bold border-b border-blue-300 dark:border-blue-500/50"><?= htmlspecialchars($user['division']) ?></span>, 
                                commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures.
                            </div>
                        </div>
                    </div>
                </div>

                <form id="ipcr-form" method="POST" action="save_ipcr.php">
                    <input type="hidden" name="target_user_id" value="<?= $target_user_id ?>">
                    
                    <div class="space-y-6">
                        <?php while ($t = $tasks->fetch_assoc()): $siCode = trim($t['task_code']); ?>
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-all duration-200 task-row" id="row-<?= $t['task_id'] ?>">
                            <div class="grid grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-slate-100 dark:divide-slate-700">
                                
                                <div class="col-span-12 lg:col-span-4 p-5 bg-slate-50/50 dark:bg-slate-800/50 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center mb-3">
                                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300"><?= htmlspecialchars($t['task_code']) ?></span>
                                        </div>
                                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-2 leading-snug"><?= nl2br(htmlspecialchars($t['task_title'])) ?></h4>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 italic mt-3 border-t border-slate-200 dark:border-slate-700 pt-3">
                                            <span class="font-bold text-slate-600 dark:text-slate-300 block mb-1">Success Indicator:</span>
                                            <?= nl2br(htmlspecialchars($t['success_indicator'])) ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($can_edit || $can_delete): ?>
                                    <div class="mt-4 pt-3 border-t border-slate-200/60 dark:border-slate-700 flex items-center space-x-4">
                                        <button type="button" onclick="openEditModal(this)" data-id="<?= $t['task_id'] ?>" data-code="<?= htmlspecialchars($t['task_code']) ?>" data-title="<?= htmlspecialchars($t['task_title']) ?>" data-si="<?= htmlspecialchars($t['success_indicator']) ?>" class="text-xs flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition group">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-blue-500 dark:text-blue-400 group-hover:text-blue-700" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg> Edit Task
                                        </button>
                                        <button type="button" onclick="openDeleteModal('delete_task_assignment.php?task_id=<?= $t['task_id'] ?>&period_id=<?= $period_id ?>&target_user_id=<?= $target_user_id ?>')" class="text-xs flex items-center text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium transition group">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-red-500 dark:text-red-400 group-hover:text-red-700" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 000-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg> Remove
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-span-12 lg:col-span-6 p-5 relative smart-cell" data-task-id="<?= $t['task_id'] ?>" data-si-code="<?= htmlspecialchars($t['task_id']) ?>" data-success-indicator="<?= htmlspecialchars($t['success_indicator']) ?>">
                                    <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Actual Accomplishment</label>
                                    <div class="smart-area w-full p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all shadow-inner" contenteditable="<?= $content_editable_state ?>" id="div-<?= $t['task_id'] ?>" placeholder="Accomplishments will auto-generate here based on ratings..."></div>
                                    <input type="hidden" name="narrative[<?= $t['task_id'] ?>]" id="input-<?= $t['task_id'] ?>">
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2 text-right">Auto-generated based on QET ratings</p>
                                </div>

                                <div class="col-span-12 lg:col-span-2 bg-slate-50 dark:bg-slate-800/80 p-4 flex flex-col justify-center items-center space-y-3">
                                    <div class="w-full space-y-2">
                                        <div class="flex items-center justify-between"><span class="text-xs font-bold text-slate-500 dark:text-slate-400 w-4">Q</span><input type="number" name="q[<?= $t['task_id'] ?>]" step="1" min="1" max="5" <?= $input_state ?> class="rating-input w-12 h-8 text-center text-sm font-bold text-slate-700 dark:text-white bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"></div>
                                        <div class="flex items-center justify-between"><span class="text-xs font-bold text-slate-500 dark:text-slate-400 w-4">E</span><input type="number" name="e[<?= $t['task_id'] ?>]" step="1" min="1" max="5" <?= $input_state ?> class="rating-input w-12 h-8 text-center text-sm font-bold text-slate-700 dark:text-white bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"></div>
                                        <div class="flex items-center justify-between"><span class="text-xs font-bold text-slate-500 dark:text-slate-400 w-4">T</span><input type="number" name="t[<?= $t['task_id'] ?>]" step="1" min="1" max="5" <?= $input_state ?> class="rating-input w-12 h-8 text-center text-sm font-bold text-slate-700 dark:text-white bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"></div>
                                    </div>
                                    <div class="w-full pt-3 border-t border-slate-200 dark:border-slate-700 mt-2 text-center">
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 uppercase font-bold block">AVG</span>
                                        <span class="text-xl font-black text-slate-800 dark:text-white avg-cell" id="avg-<?= $t['task_id'] ?>">0.00</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="mt-8 flex justify-end space-x-4 mb-12">
                        <a href="print_ipcr.php?period_id=<?= $period_id ?>&uid=<?= $target_user_id ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-slate-600 shadow-sm text-sm font-medium rounded-md text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none transition-colors">
                            <svg class="mr-2 -ml-1 h-5 w-5 text-slate-500 dark:text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-4h6a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2zm9-12h2m-6 0h-2" /></svg>
                            Print IPCR Form
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div> 

<?php 
require '../includes/ipcr_modals.php';
require '../includes/ipcr_scripts.php';
?>

</body>
</html>