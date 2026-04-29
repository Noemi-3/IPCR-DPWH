<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPCR Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    // 1. Tell Tailwind to use manual CSS classes for dark mode
    tailwind.config = {
        darkMode: 'class',
    }

    // 2. Check the user's preference immediately to prevent a "white flash" on load
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        
        .smart-area { min-height: 100px; max-height: 200px; overflow-y: auto; white-space: pre-wrap; outline: none; }
        .smart-area[contenteditable="false"] { background-color: #f9fafb; color: #6b7280; cursor: not-allowed; }
        .smart-area u { text-decoration: none; border-bottom: 2px solid #3b82f6; background-color: #eff6ff; color: #1e3a8a; font-weight: 600; padding: 0 2px; border-radius: 2px; }
        
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number]:disabled { background-color: #f9fafb; color: #9ca3af; cursor: not-allowed; border-color: #e5e7eb; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <div id="toast-container" class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

    <script>
        // GLOBAL TOAST NOTIFICATION FUNCTION
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            // THE FIX 1: Replaced 'max-w-sm w-full' with a solid width 'w-80 md:w-96'
            toast.className = `transform transition-all duration-300 translate-x-full opacity-0 w-80 md:w-96 shadow-lg rounded-lg pointer-events-auto overflow-hidden ring-1 ring-black ring-opacity-5`;
            
            // Determine colors and icons based on notification type
            let bgClass, iconHtml;
            if (type === 'success') {
                bgClass = 'bg-white dark:bg-slate-800 border-l-4 border-green-500';
                iconHtml = `<svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
            } else if (type === 'error') {
                bgClass = 'bg-white dark:bg-slate-800 border-l-4 border-red-500';
                iconHtml = `<svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
            } else if (type === 'info') {
                bgClass = 'bg-white dark:bg-slate-800 border-l-4 border-blue-500';
                iconHtml = `<svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
            }

            // Construct the HTML for the toast card
            toast.innerHTML = `
                <div class="${bgClass} p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            ${iconHtml}
                        </div>
                        <div class="ml-3 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">${message}</p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button onclick="this.closest('.transform').remove()" class="bg-transparent rounded-md inline-flex text-slate-400 hover:text-slate-500 focus:outline-none transition-colors">
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            container.appendChild(toast);

            // Animate sliding in
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            }, 10);

            // Auto remove after 4 seconds
            setTimeout(() => {
                toast.classList.remove('translate-x-0', 'opacity-100');
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300); 
            }, 4000);
        }
    </script>