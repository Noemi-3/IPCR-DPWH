<?php
// Get the name of the current file
$current_page = basename($_SERVER['PHP_SELF']);

// GUARANTEE WE KNOW WHO IS ACTUALLY LOGGED IN
$sidebar_user_id = $_SESSION['user_id'];
$stmt_sb = $conn->prepare("SELECT full_name, role FROM users WHERE id = ?");
$stmt_sb->bind_param("i", $sidebar_user_id);
$stmt_sb->execute();
$sidebar_user = $stmt_sb->get_result()->fetch_assoc();

$sidebar_role = (int)$sidebar_user['role'];
$sidebar_name = $sidebar_user['full_name'] ?? 'User';

// Define access rules for the sidebar links
$show_dashboard = ($sidebar_role === 0 || $sidebar_role === 2); // Admins & Mods
$show_admin_controls = ($sidebar_role === 0 || $sidebar_role === 1); // Superadmin & Admins

// DYNAMIC IPCR LABEL
$ipcr_label = "My IPCR";
if (isset($_GET['uid']) && $_GET['uid'] != $sidebar_user_id) {
    $ipcr_label = "Employee IPCR";
}

// Define our CSS classes for active vs inactive states
$active_class = "bg-blue-600 text-white shadow-md shadow-blue-500/20";
$inactive_class = "text-slate-600 hover:bg-white hover:shadow-sm hover:text-blue-700 dark:text-slate-300 dark:hover:bg-slate-800/50 dark:hover:text-white transition-all duration-200";

$active_icon = "text-blue-100";
$inactive_icon = "text-slate-400 group-hover:text-blue-600 dark:text-slate-500 dark:group-hover:text-white transition-colors duration-200";
?>

<style>
    #app-sidebar { transition: width 0.3s ease, background-color 0.3s, border-color 0.3s; }
    .sidebar-text, #sidebar-logo, #sidebar-profile-info, .admin-label { 
        transition: opacity 0.2s ease; 
        white-space: nowrap; 
    }
    
    /* Collapsed State Styles */
    #app-sidebar.collapsed { width: 5rem; /* Equivalent to w-20 */ }
    #app-sidebar.collapsed .sidebar-text,
    #app-sidebar.collapsed #sidebar-logo,
    #app-sidebar.collapsed #sidebar-profile-info,
    #app-sidebar.collapsed .admin-label,
    #app-sidebar.collapsed .logout-text { 
        display: none; 
    }
    
    #app-sidebar.collapsed .nav-link { justify-content: center; padding-left: 0; padding-right: 0; }
    #app-sidebar.collapsed .nav-icon { margin-right: 0; }
    #app-sidebar.collapsed .header-container { justify-content: center; padding: 0; }
    #app-sidebar.collapsed .profile-container { justify-content: center; padding-left: 0; padding-right: 0; flex-direction: column; gap: 0.5rem; }
    #app-sidebar.collapsed .logout-btn, #app-sidebar.collapsed #theme-toggle { margin-left: 0; padding: 0.5rem; }
</style>

<aside id="app-sidebar" class="w-64 bg-slate-100 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 hidden md:flex flex-col z-20 flex-shrink-0">
    
    <script>
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            const sidebarElement = document.getElementById('app-sidebar');
            sidebarElement.classList.add('collapsed');
            sidebarElement.style.transition = 'none'; 
            setTimeout(() => { sidebarElement.style.transition = ''; }, 100);
        }
    </script>

    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800 header-container transition-colors duration-300">
        <div id="sidebar-logo" class="font-bold text-xl tracking-wider text-blue-600 dark:text-blue-400">DPWH<span class="text-slate-800 dark:text-white transition-colors">IPCR</span></div>
        <button id="toggle-sidebar-btn" class="text-slate-400 hover:text-blue-600 dark:hover:text-white focus:outline-none transition-colors p-1.5 rounded-md hover:bg-white hover:shadow-sm dark:hover:bg-slate-800" title="Toggle Sidebar">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
    
    <div class="flex-1 overflow-y-auto py-4 overflow-x-hidden">
        <nav class="px-3 space-y-2">

        <?php if($show_dashboard): ?>
            <a href="home.php" title="Dashboard" class="nav-link group flex items-center px-3 py-2.5 text-sm font-bold rounded-lg <?= ($current_page == 'home.php' || $current_page == 'employees.php') ? $active_class : $inactive_class ?>">
                <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0 <?= ($current_page == 'home.php' || $current_page == 'employees.php') ? $active_icon : $inactive_icon ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="sidebar-text">Dashboard</span>
            </a>
            <?php endif; ?>
            
            <a href="ipcr.php" title="<?= $ipcr_label ?>" class="nav-link group flex items-center px-3 py-2.5 text-sm font-bold rounded-lg <?= ($current_page == 'ipcr.php') ? $active_class : $inactive_class ?>">
                <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0 <?= ($current_page == 'ipcr.php') ? $active_icon : $inactive_icon ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="sidebar-text"><?= $ipcr_label ?></span>
            </a>
            
            <a href="history.php" title="History / Copies" class="nav-link group flex items-center px-3 py-2.5 text-sm font-bold rounded-lg <?= ($current_page == 'history.php') ? $active_class : $inactive_class ?>">
                <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0 <?= ($current_page == 'history.php') ? $active_icon : $inactive_icon ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="sidebar-text">History & Copies</span>
            </a>

            <?php if($show_admin_controls): ?>
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800 transition-colors">
                <p class="admin-label px-3 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">Admin Controls</p>
                
                <a href="create_task.php" title="Create New Task" class="nav-link group flex items-center px-3 py-2.5 text-sm font-bold rounded-lg <?= ($current_page == 'create_task.php') ? $active_class : $inactive_class ?>">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0 <?= ($current_page == 'create_task.php') ? $active_icon : $inactive_icon ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="sidebar-text">Create New Task</span>
                </a>
                
                <a href="admin_management.php" title="System Setup" class="nav-link group flex items-center px-3 py-2.5 mt-2 text-sm font-bold rounded-lg <?= ($current_page == 'admin_management.php') ? $active_class : $inactive_class ?>">
                    <svg class="nav-icon mr-3 h-5 w-5 flex-shrink-0 <?= ($current_page == 'admin_management.php') ? $active_icon : $inactive_icon ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="sidebar-text">System Setup</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </div>

    <div class="p-4 border-t border-slate-200 dark:border-slate-800 profile-container flex items-center transition-all duration-300 bg-transparent">
        <div class="h-9 w-9 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm font-black flex-shrink-0 shadow-sm" title="<?= htmlspecialchars($sidebar_name) ?>">
            <?= substr($sidebar_name, 0, 1) ?>
        </div>
        <div id="sidebar-profile-info" class="ml-3 flex-1 overflow-hidden">
            <p class="text-sm font-bold text-slate-800 dark:text-white truncate transition-colors"><?= explode(' ', $sidebar_name)[0] ?></p>
             <p class="text-[11px] font-semibold tracking-wide text-slate-500 dark:text-slate-400 truncate uppercase mt-0.5 transition-colors">
                <?php 
                if($sidebar_role === 0) echo "Superadmin"; 
                elseif($sidebar_role === 1) echo "Admin";
                elseif($sidebar_role === 2) echo "Moderator"; 
                else echo "Employee"; 
                ?>
            </p>
        </div>
        
        <button id="theme-toggle" type="button" class="text-slate-400 hover:text-indigo-600 dark:hover:text-amber-400 p-2 rounded-lg hover:bg-white dark:hover:bg-slate-800 shadow-sm border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all focus:outline-none ml-1 shrink-0" title="Toggle Dark Mode">
            <svg id="theme-toggle-dark-icon" class="w-4 h-4 hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
            <svg id="theme-toggle-light-icon" class="w-4 h-4 hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
        </button>

        <a href="logout.php" class="logout-btn ml-1 text-slate-400 hover:text-red-600 dark:hover:text-red-400 p-2 rounded-lg hover:bg-white hover:shadow-sm dark:hover:bg-slate-800 transition-all flex items-center shrink-0" title="Sign out">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="logout-text text-xs ml-1 hidden">Sign out</span>
        </a>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Sidebar Toggle Logic ---
        const sidebar = document.getElementById('app-sidebar');
        const toggleBtn = document.getElementById('toggle-sidebar-btn');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            if (sidebar.classList.contains('collapsed')) {
                localStorage.setItem('sidebar-collapsed', 'true');
            } else {
                localStorage.setItem('sidebar-collapsed', 'false');
            }
        });

        // --- Dark Mode Toggle Logic ---
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        const themeToggleBtn = document.getElementById('theme-toggle');

        // Show correct icon on load
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }
        });
    });
</script>