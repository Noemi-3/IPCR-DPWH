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