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

// SECURITY BOUNCER
if ($role > 1) {
    header("Location: home.php?msg=access_denied");
    exit();
}

$is_superadmin = ($role === 0);

// ==========================================
// FORM SUBMISSION HANDLERS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Handle New Employee Registration
    if (isset($_POST['action']) && $_POST['action'] === 'add_employee') {
        $full_name = trim($_POST['full_name']);
        $position = trim($_POST['position']);
        $division = trim($_POST['division']);
        $new_role = (int)$_POST['role'];
        
        $default_password = password_hash('dpwh123', PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (full_name, position, division, role, is_active, password) VALUES (?, ?, ?, ?, 1, ?)");
        $stmt->bind_param("sssis", $full_name, $position, $division, $new_role, $default_password);
        
        if ($stmt->execute()) {
            header("Location: admin_management.php?msg=employee_added");
        } else {
            header("Location: admin_management.php?msg=error");
        }
        $stmt->close();
        exit();
    }

    // 2. Handle New Period Creation
    if (isset($_POST['action']) && $_POST['action'] === 'add_period') {
        $month = trim($_POST['month']); 
        $year = trim($_POST['year']);   

        $stmt = $conn->prepare("INSERT INTO login_periods (month, year) VALUES (?, ?)");
        $stmt->bind_param("ss", $month, $year);
        
        if ($stmt->execute()) {
            header("Location: admin_management.php?msg=period_added");
        } else {
            header("Location: admin_management.php?msg=error");
        }
        $stmt->close();
        exit();
    }

    // 3. Handle Employee Soft Deletion (Status Toggle)
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $target_id = (int)$_POST['target_id'];
        $current_status = (int)$_POST['current_status'];
        
        // Flip the status: If 1 (active), make it 0 (inactive), and vice versa.
        $new_status = $current_status === 1 ? 0 : 1;

        // SAFETY LOCK: Prevent the logged-in admin from deactivating themselves
        if ($target_id === $user_id) {
            header("Location: admin_management.php?msg=error_self");
            exit();
        }

        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $target_id);
        
        if ($stmt->execute()) {
            header("Location: admin_management.php?msg=status_updated");
        } else {
            header("Location: admin_management.php?msg=error");
        }
        $stmt->close();
        exit();
    }
}

// Fetch all employees for the Roster Table (Hiding the system demo accounts!)
$roster_query = "
    SELECT id, full_name, position, division, role, is_active 
    FROM users 
    WHERE full_name NOT IN ('GENERAL USER', 'SYSTEM ADMIN', 'SYSTEM MODERATOR')
    ORDER BY is_active DESC, full_name ASC
";
$roster_result = $conn->query($roster_query);

// === INCLUDE MODULAR UI COMPONENTS ===
require '../includes/header.php';
require '../includes/sidebar.php';
?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
        <header class="h-16 bg-white dark:bg-slate-800 shadow-sm flex items-center justify-between px-6 z-10 border-b border-slate-200 dark:border-slate-700 transition-colors duration-300">
            <h1 class="text-xl font-bold text-slate-800 dark:text-white">System Management</h1>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-200 dark:border-purple-800/50">Admin Privileges Active</span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-300">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex items-center">
                            <div class="h-10 w-10 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-400 mr-4">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Register New Employee</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Add personnel to the DPWH database.</p>
                            </div>
                        </div>
                        <form action="admin_management.php" method="POST" class="p-6 space-y-4">
                            <input type="hidden" name="action" value="add_employee">
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Full Name</label>
                                <input type="text" name="full_name" required placeholder="e.g., JUAN DELA CRUZ" class="w-full p-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition uppercase">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Position</label>
                                    <input type="text" name="position" required placeholder="e.g., Engineer II" class="w-full p-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Division/Section</label>
                                    <input type="text" name="division" placeholder="e.g., ICT Section" class="w-full p-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">System Role</label>
                                <select name="role" required class="w-full p-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition cursor-pointer">
                                    <option value="3">Standard Employee</option>
                                    <option value="2">Moderator</option>
                                    <?php if($is_superadmin): ?>
                                    <option value="1">Administrator</option>
                                    <option value="0">Superadmin</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm transition">
                                    Create Employee Account
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-300 h-fit">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex items-center">
                            <div class="h-10 w-10 bg-emerald-100 dark:bg-emerald-900/50 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400 mr-4">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Create Rating Period</h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Initialize a new semestral evaluation cycle.</p>
                            </div>
                        </div>
                        <form action="admin_management.php" method="POST" class="p-6 space-y-4">
                            <input type="hidden" name="action" value="add_period">
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Month Span</label>
                                <input type="text" name="month" required placeholder="e.g., January - June" class="w-full p-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Year</label>
                                <input type="number" name="year" required value="<?= date('Y') ?>" min="2020" max="2050" class="w-full p-2.5 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition">
                            </div>

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-700">
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm transition">
                                    Open New Rating Period
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors duration-300">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Active/Inactive Employee Roster</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Manage account access and system visibility for all registered personnel.</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-900/50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Employee</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                                <?php if($roster_result && $roster_result->num_rows > 0): ?>
                                    <?php while($emp = $roster_result->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors duration-200 <?= $emp['is_active'] == 0 ? 'opacity-60' : '' ?>">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-bold text-slate-900 dark:text-white uppercase"><?= htmlspecialchars($emp['full_name']) ?></div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($emp['position']) ?> <?= !empty($emp['division']) ? '- ' . htmlspecialchars($emp['division']) : '' ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-xs text-slate-600 dark:text-slate-300">
                                                <?php 
                                                    if($emp['role'] == 0) echo "Superadmin";
                                                    elseif($emp['role'] == 1) echo "Administrator";
                                                    elseif($emp['role'] == 2) echo "Moderator";
                                                    else echo "Employee";
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <?php if($emp['is_active'] == 1): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400 border border-green-200 dark:border-green-800/50">Active</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400 border border-red-200 dark:border-red-800/50">Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            
                                            <?php if($emp['id'] === $user_id): ?>
                                                <span class="text-xs text-slate-400 italic">Current User</span>
                                            <?php else: ?>
                                                <form action="admin_management.php" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to change this employee\'s system access status?');">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="target_id" value="<?= $emp['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $emp['is_active'] ?>">
                                                    
                                                    <?php if($emp['is_active'] == 1): ?>
                                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 font-bold hover:underline transition">Deactivate</button>
                                                    <?php else: ?>
                                                        <button type="submit" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300 font-bold hover:underline transition">Restore Access</button>
                                                    <?php endif; ?>
                                                </form>
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">No employees found in the system.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');
            
            if (msg === 'employee_added') {
                if(typeof showToast === 'function') showToast('New employee successfully registered to the system.', 'success');
            } else if (msg === 'period_added') {
                if(typeof showToast === 'function') showToast('New rating period has been initialized.', 'success');
            } else if (msg === 'status_updated') {
                if(typeof showToast === 'function') showToast('Employee access status has been updated.', 'info');
            } else if (msg === 'error_self') {
                if(typeof showToast === 'function') showToast('Action Denied: You cannot deactivate your own account.', 'error');
            } else if (msg === 'error') {
                if(typeof showToast === 'function') showToast('Database error: Unable to complete the request.', 'error');
            }
            
            if (msg) {
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        });
    </script>

</body>
</html>