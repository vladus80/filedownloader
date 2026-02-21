<?php
/**
 * Header компонент с современным дизайном
 */
?>
<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'FileDownloader'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(10px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Styles -->
    <style>
        .progress-bar {
            transition: width 0.3s ease;
        }
        
        .theme-transition * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease !important;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .dark ::-webkit-scrollbar-track {
            background: #374151;
        }
        
        .dark ::-webkit-scrollbar-thumb {
            background: #6b7280;
        }
        
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
        
        /* Toast styles */
        #toast-container { 
            position: fixed; top: 20px; right: 20px; z-index: 9999; 
            display: flex; flex-direction: column; gap: 8px; pointer-events: none; 
            max-width: calc(100vw - 40px); 
        }
        .toast { 
            padding: 14px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
            pointer-events: auto; max-width: min(360px, calc(100vw - 40px)); 
            animation: toastIn 0.25s ease; overflow-wrap: break-word; word-wrap: break-word; 
            word-break: break-word; overflow-x: hidden; box-sizing: border-box; 
        }
        .toast.success { background: #10b981; border: 1px solid #059669; color: white; }
        .toast.error { background: #ef4444; border: 1px solid #dc2626; color: white; }
        @keyframes toastIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        
        /* Drag and drop styles */
        .drop-zone {
            transition: all 0.3s ease;
        }
        .drop-zone.dragover {
            background-color: #dbeafe !important;
            border-color: #3b82f6 !important;
            transform: scale(1.02);
        }
        .dark .drop-zone.dragover {
            background-color: #1e3a8a !important;
            border-color: #60a5fa !important;
        }
        
        /* Tooltip styles */
        .tooltip-container {
            position: relative;
            display: inline-block;
        }
        
        .tooltip {
            visibility: hidden;
            position: absolute;
            z-index: 50;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-bottom: 8px;
            padding: 8px 12px;
            background: #1f2937;
            color: white;
            border-radius: 6px;
            font-size: 12px;
            font-family: monospace;
            white-space: pre-wrap;
            max-width: 400px;
            word-wrap: break-word;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            opacity: 0;
            transition: opacity 0.3s, visibility 0.3s;
        }
        
        .dark .tooltip {
            background: #374151;
            border: 1px solid #4b5563;
        }
        
        .tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -6px;
            border: 6px solid transparent;
            border-top-color: #1f2937;
        }
        
        .dark .tooltip::after {
            border-top-color: #374151;
        }
        
        .tooltip-container:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo and Title -->
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">FileDownloader</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Система управления файлами</p>
                    </div>
                </div>
                
                <!-- Navigation and Theme Toggle -->
                <div class="flex items-center space-x-4">
                    <!-- Navigation -->
                    <nav class="hidden md:flex space-x-1">
                        <?php 
                        // Определяем текущую страницу
                        $currentPage = basename($_SERVER['PHP_SELF']);
                        
                        // Функция для получения классов активного пункта меню
                        function getNavClasses($page, $currentPage) {
                            $baseClasses = 'relative px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 transform hover:scale-105';
                            $activeClasses = 'bg-blue-600 dark:bg-blue-500 text-white shadow-lg shadow-blue-500/25 dark:shadow-blue-500/20';
                            $inactiveClasses = 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400';
                            
                            return $baseClasses . ' ' . ($page === $currentPage ? $activeClasses : $inactiveClasses);
                        }
                        ?>
                        
                        <a href="index.php" class="<?php echo getNavClasses('index.php', $currentPage); ?>">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                Загрузка
                            </span>
                            <?php if ($currentPage === 'index.php'): ?>
                                <span class="absolute inset-x-0 -bottom-px h-0.5 bg-gradient-to-r from-blue-600 to-blue-400 dark:from-blue-400 dark:to-blue-300 rounded-full"></span>
                            <?php endif; ?>
                        </a>
                        
                        <a href="history.php" class="<?php echo getNavClasses('history.php', $currentPage); ?>">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                История
                            </span>
                            <?php if ($currentPage === 'history.php'): ?>
                                <span class="absolute inset-x-0 -bottom-px h-0.5 bg-gradient-to-r from-blue-600 to-blue-400 dark:from-blue-400 dark:to-blue-300 rounded-full"></span>
                            <?php endif; ?>
                        </a>
                        
                        <?php if (isAdmin()): ?>
                        <a href="admin.php" class="<?php echo getNavClasses('admin.php', $currentPage); ?>">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Админка
                            </span>
                            <?php if ($currentPage === 'admin.php'): ?>
                                <span class="absolute inset-x-0 -bottom-px h-0.5 bg-gradient-to-r from-blue-600 to-blue-400 dark:from-blue-400 dark:to-blue-300 rounded-full"></span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                    </nav>
                    
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors" title="Переключить тему">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>
                    
                    <!-- User Menu -->
                    <div class="relative">
                        <button id="user-menu-button" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center">
                                <span class="text-white font-medium"><?php echo strtoupper(substr(getCurrentUser(), 0, 1)); ?></span>
                            </div>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                                    <div class="font-medium"><?php echo htmlspecialchars(getCurrentUser()); ?></div>
                                </div>
                                <a href="index.php?logout=1" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    Выйти
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <button id="mobile-menu-button" class="md:hidden p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-200 dark:border-gray-700">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="index.php" class="<?php echo str_replace('relative', '', getNavClasses('index.php', $currentPage)); ?> block">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Загрузка
                    </span>
                </a>
                <a href="history.php" class="<?php echo str_replace('relative', '', getNavClasses('history.php', $currentPage)); ?> block">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        История
                    </span>
                </a>
                <?php if (isAdmin()): ?>
                <a href="admin.php" class="<?php echo str_replace('relative', '', getNavClasses('admin.php', $currentPage)); ?> block">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Админка
                    </span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Toast Container -->
    <div id="toast-container" aria-live="polite"></div>
    
    <!-- Main Content -->
    <main class="flex-1">
<script>
// Theme management
(function() {
    // Theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle');
    const html = document.documentElement;
    
    // Load saved theme or default to light
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        html.classList.add('dark');
    }
    
    themeToggle.addEventListener('click', function() {
        html.classList.add('theme-transition');
        
        if (html.classList.contains('dark')) {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
        
        setTimeout(() => {
            html.classList.remove('theme-transition');
        }, 300);
    });
    
    // User menu toggle
    const userMenuButton = document.getElementById('user-menu-button');
    const userMenu = document.getElementById('user-menu');
    
    userMenuButton.addEventListener('click', function(e) {
        e.stopPropagation();
        userMenu.classList.toggle('hidden');
    });
    
    // Close user menu when clicking outside
    document.addEventListener('click', function() {
        userMenu.classList.add('hidden');
    });
    
    userMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    mobileMenuButton.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
    });
    
    // Toast system
    window.showToast = function(message, type) {
        type = type || 'success';
        var container = document.getElementById('toast-container');
        if (!container) return;
        
        var el = document.createElement('div');
        el.className = 'toast ' + type + ' animate-slide-up';
        el.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    ${type === 'success' 
                        ? '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
                        : '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>'
                    }
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
            </div>
        `;
        
        container.appendChild(el);
        
        setTimeout(function() {
            el.style.opacity = '0';
            el.style.transform = 'translateX(20px)';
            setTimeout(function() { 
                if (el.parentNode) {
                    el.remove(); 
                }
            }, 300);
        }, 3000);
    };
    
    // Copy to clipboard with fallback
    window.copyToClipboard = function(text, successMessage) {
        successMessage = successMessage || 'Скопировано в буфер обмена';
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                window.showToast(successMessage, 'success');
            }).catch(function() {
                copyFallback(text);
            });
        } else {
            copyFallback(text);
        }
    };
    
    function copyFallback(text) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        
        try {
            document.execCommand('copy');
            window.showToast('Скопировано в буфер обмена', 'success');
        } catch (e) {
            window.showToast('Ошибка копирования. Скопируйте вручную', 'error');
        }
        
        document.body.removeChild(textarea);
    }
    
    // Drag and drop utilities
    window.setupDragAndDrop = function(dropZone, fileInput, onDrop) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        function highlight() {
            dropZone.classList.add('dragover');
        }
        
        function unhighlight() {
            dropZone.classList.remove('dragover');
        }
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                if (onDrop) {
                    onDrop(files);
                }
            }
        }
    };
    
    // Progress bar utilities
    window.createProgressBar = function(container) {
        const progressContainer = document.createElement('div');
        progressContainer.className = 'w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4 overflow-hidden';
        progressContainer.innerHTML = `
            <div class="progress-bar bg-blue-600 dark:bg-blue-400 h-full rounded-full" style="width: 0%"></div>
            <div class="text-center text-sm text-gray-600 dark:text-gray-400 mt-1">
                <span class="progress-text">0%</span>
            </div>
        `;
        
        if (container) {
            container.appendChild(progressContainer);
        }
        
        return {
            element: progressContainer,
            bar: progressContainer.querySelector('.progress-bar'),
            text: progressContainer.querySelector('.progress-text'),
            update: function(percent) {
                this.bar.style.width = percent + '%';
                this.text.textContent = Math.round(percent) + '%';
            },
            remove: function() {
                if (this.element.parentNode) {
                    this.element.remove();
                }
            }
        };
    };
    
    // Format file size
    window.formatFileSize = function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };
    
    // File type icon
    window.getFileIcon = function(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const iconMap = {
            'pdf': '📄',
            'doc': '📝', 'docx': '📝',
            'xls': '📊', 'xlsx': '📊',
            'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️',
            'txt': '📄',
            'zip': '📦', 'rar': '📦', '7z': '📦'
        };
        
        return iconMap[ext] || '📎';
    };
    
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects to interactive elements
        const interactiveElements = document.querySelectorAll('button, a, input, select, textarea');
        interactiveElements.forEach(el => {
            if (!el.classList.contains('no-hover')) {
                el.classList.add('transition-all', 'duration-200');
            }
        });
    });
})();
</script>
