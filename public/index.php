<?php
require '../config/database.php';

// Fetch everyone who is NOT an Admin (0, 1) or Moderator (2)
$employees = $conn->query("SELECT id, full_name FROM users WHERE (role IS NULL OR role NOT IN (2)) AND id < 100 ORDER BY full_name ASC");

// Fetch all available periods for the dropdown menu
$periods = $conn->query("SELECT id, month, year FROM login_periods ORDER BY year DESC, id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>IPCR Login | DPWH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #e6f0ff 0%, #f7fbff 100%); }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-center items-center p-4">

    <div class="max-w-4xl w-full">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-purple-600 text-white font-black text-2xl shadow-lg mb-4">
                IP
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">DPWH <span class="text-blue-600">IPCR System</span></h1>
            <p class="text-slate-500 font-medium mt-2" id="login-subtitle">Select your system role to log in</p>
        </div>

        <form method="POST" action="auth.php" id="loginForm">
            <input type="hidden" name="user_id" id="final_user_id" value="">

            <div id="role-view" class="grid grid-cols-1 md:grid-cols-3 gap-6 transition-all duration-300">
                
                <button type="button" onclick="submitRole(101)" class="w-full bg-white rounded-2xl shadow-sm border border-slate-200 p-8 hover:shadow-xl hover:border-blue-500 hover:-translate-y-1 transition-all duration-300 group text-left h-full focus:outline-none focus:ring-4 focus:ring-blue-100">
                    <div class="h-12 w-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 mb-2">Admin</h2>
                    <p class="text-sm text-slate-500 leading-relaxed">Full access. Can create new tasks, edit ratings, and delete task assignments.</p>
                </button>

                <button type="button" onclick="submitRole(102)" class="w-full bg-white rounded-2xl shadow-sm border border-slate-200 p-8 hover:shadow-xl hover:border-purple-500 hover:-translate-y-1 transition-all duration-300 group text-left h-full focus:outline-none focus:ring-4 focus:ring-purple-100">
                    <div class="h-12 w-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 mb-2">Moderator</h2>
                    <p class="text-sm text-slate-500 leading-relaxed">Manager access. Can edit ratings and remove assigned tasks, but cannot create.</p>
                </button>

                <button type="button" onclick="showEmployeeSelect()" class="w-full bg-white rounded-2xl shadow-sm border border-slate-200 p-8 hover:shadow-xl hover:border-emerald-500 hover:-translate-y-1 transition-all duration-300 group text-left h-full focus:outline-none focus:ring-4 focus:ring-emerald-100">
                    <div class="h-12 w-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 mb-2">Employee</h2>
                    <p class="text-sm text-slate-500 leading-relaxed">Select your name and semester to view your IPCR dashboard.</p>
                </button>
            </div>

            <div id="employee-view" class="hidden max-w-md mx-auto bg-white p-8 rounded-2xl shadow-xl border border-slate-200 transition-all duration-300">
                
                <div class="space-y-5 mb-8">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Who are you?</label>
                        <select id="employee_dropdown" class="w-full p-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                            <option value="" disabled selected>-- Choose your name --</option>
                            <?php while ($emp = $employees->fetch_assoc()): ?>
                                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['full_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Select Semester</label>
                        <select name="period_id" id="employee_period_dropdown" class="w-full p-3 bg-slate-50 border border-slate-300 rounded-xl text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer">
                            <?php while ($p = $periods->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars(strtoupper($p['month'] . ' ' . $p['year'])) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button type="button" onclick="showRoleSelect()" class="w-1/3 px-4 py-3 border border-slate-300 text-slate-600 font-semibold rounded-xl hover:bg-slate-50 transition">Back</button>
                    <button type="button" onclick="submitEmployee()" class="w-2/3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 shadow-sm transition">Sign In</button>
                </div>
            </div>

        </form>

        <div class="text-center mt-12 text-slate-400 font-medium text-sm">
            © <?= date('Y') ?> DPWH IPCR System
        </div>
    </div>

    <script>
        function submitRole(roleId) {
            document.getElementById('final_user_id').value = roleId;
            document.getElementById('loginForm').submit();
        }

        function showEmployeeSelect() {
            document.getElementById('role-view').classList.add('hidden');
            document.getElementById('employee-view').classList.remove('hidden');
            document.getElementById('login-subtitle').innerText = "Select your details from the directory";
        }

        function showRoleSelect() {
            document.getElementById('employee-view').classList.add('hidden');
            document.getElementById('role-view').classList.remove('hidden');
            document.getElementById('login-subtitle').innerText = "Select your system role to log in";
        }

        function submitEmployee() {
            const dropdown = document.getElementById('employee_dropdown');
            if (dropdown.value) {
                document.getElementById('final_user_id').value = dropdown.value;
                document.getElementById('loginForm').submit();
            } else {
                alert("Please select your name first.");
            }
        }
    </script>
</body>
</html>