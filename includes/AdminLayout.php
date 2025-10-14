<?php
/**
 * Admin Layout Helper Class
 * Provides consistent layout with right-side sidebar and SVG icons
 */

require_once 'Auth.php';
require_once 'JalaliDate.php';
require_once __DIR__ . '/../functions.php';

class AdminLayout {
    
    /**
     * Render the admin header
     */
    public static function renderHeader($title = 'داشبورد') {
        $auth = new Auth();
        $user = $auth->getCurrentUser();
        
        echo '<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' - ' . htmlspecialchars(getSetting('system_name', 'سیستم مدیریت شکایات')) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/mobile-responsive.css" rel="stylesheet">
    <style>
        :root {
            --system-font-size: 14px;
        }
        
        body {
            font-family: "Tahoma", sans-serif;
            background-color: #f8f9fa;
            font-size: var(--system-font-size);
        }
        .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        .sidebar.hidden {
            transform: translateX(100%);
        }
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-size: 1rem;
        }
        .sidebar-nav {
            padding: 1rem 0;
        }
        .nav-item {
            margin: 0.2rem 0;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.6rem 1.2rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border-right: 3px solid transparent;
            font-size: calc(var(--system-font-size) - 1px);
        }
        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-right-color: #3498db;
        }
        .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-right-color: #3498db;
        }
        .nav-link i {
            margin-left: 0.8rem;
            width: 20px;
            text-align: center;
        }
        .submenu {
            margin-right: 0;
            margin-top: 0;
            padding-right: 0;
            display: none;
        }
        .nav-item.has-submenu > .nav-link::after {
            content: "▼";
            float: left;
            font-size: 0.7rem;
            margin-top: 0.2rem;
            margin-left: 8px;
            transition: transform 0.3s ease;
        }
        .nav-item.has-submenu.active > .nav-link {
            background-color: rgba(255,255,255,0.1);
            border-right-color: #3498db;
        }
        .nav-item.has-submenu.active > .nav-link::after {
            transform: rotate(180deg);
        }
        .nav-item.has-submenu.active .submenu {
            display: block;
        }
        .submenu .nav-link {
            padding: 0.4rem 0.8rem;
            font-size: calc(var(--system-font-size) - 3px);
            color: rgba(255,255,255,0.7);
        }
        .submenu .nav-link:hover {
            color: rgba(255,255,255,0.9);
        }
        .submenu .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.15);
            border-right-color: #3498db;
        }
        .main-content {
            margin-right: 280px;
            min-height: 100vh;
            transition: margin-right 0.3s ease;
        }
        .main-content.full-width {
            margin-right: 0;
        }
        .top-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 0.8rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .sidebar-toggle {
            position: fixed;
            top: 50%;
            right: 280px;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            width: 30px;
            height: 60px;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: -2px 0 8px rgba(0,0,0,0.2);
        }
        .sidebar-toggle:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            box-shadow: -4px 0 12px rgba(102, 126, 234, 0.4);
        }
        .sidebar.hidden + .sidebar-toggle {
            right: 0;
        }
        .top-navbar h2 {
            font-size: calc(var(--system-font-size) + 4px);
        }
        .content-area {
            padding: 0 1.5rem 1.5rem;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 0.8rem 1.2rem;
        }
        .card-header h4, .card-header h5 {
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: calc(var(--system-font-size) - 1px);
            padding: 0.6rem 0.75rem;
        }
        .table tbody td {
            font-size: calc(var(--system-font-size) - 1px);
            padding: 0.6rem 0.75rem;
        }
        .badge {
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: calc(var(--system-font-size) - 3px);
        }
        .user-info {
            color: rgba(255,255,255,0.9);
            font-size: calc(var(--system-font-size) - 3px);
            margin-top: 1rem;
            padding: 0.8rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .logout-btn {
            background: none;
            border: none;
            color: rgba(255,255,255,0.8);
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-size: calc(var(--system-font-size) - 3px);
        }
        .logout-btn:hover {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                transform: translateX(100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-right: 0;
                width: 100%;
            }
            .main-content.full-width {
                width: 100%;
            }
            .top-navbar {
                padding: 0.5rem 1rem;
            }
            .top-navbar h2 {
                font-size: 1.2rem;
            }
            .container-fluid {
                padding: 0.5rem;
            }
            .card {
                margin-bottom: 1rem;
            }
            .card-header {
                padding: 0.8rem 1rem;
            }
            .card-body {
                padding: 1rem;
            }
            .table-responsive {
                font-size: 0.8rem;
            }
            .table thead th {
                padding: 0.4rem 0.5rem;
                font-size: 0.75rem;
            }
            .table tbody td {
                padding: 0.4rem 0.5rem;
                font-size: 0.75rem;
            }
            .btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
            }
            .btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.7rem;
            }
            .badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.5rem;
            }
            .stats-card {
                margin-bottom: 1rem;
            }
            .stats-card .card-body {
                padding: 1rem 0.8rem;
            }
            .stats-number {
                font-size: 1.5rem;
            }
            .stats-label {
                font-size: 0.8rem;
            }
            .sidebar-toggle {
                display: block;
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: 1001;
                background: #2c3e50;
                color: white;
                border: none;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            }
        }
        
        @media (max-width: 576px) {
            .top-navbar h2 {
                font-size: 1rem;
            }
            .card-header h4,
            .card-header h5 {
                font-size: 0.9rem;
            }
            .table-responsive {
                font-size: 0.7rem;
            }
            .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            .stats-number {
                font-size: 1.3rem;
            }
            .stats-label {
                font-size: 0.75rem;
            }
            .container-fluid {
                padding: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-shield-alt me-2"></i>مدیریت شکایات</h4>
        </div>
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        داشبورد
                    </a>
                </li>
                <!-- مدیریت افراد submenu removed; items moved to top-level -->
                <li class="nav-item">
                    <a class="nav-link" href="add_individual_page.php">
                        <i class="fas fa-user-plus"></i>
                        افزودن فرد جدید
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="list_individuals.php">
                        <i class="fas fa-list"></i>
                        لیست افراد
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cases.php">
                        <i class="fas fa-folder-open"></i>
                        مدیریت پرونده‌ها
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        گزارشات
                    </a>
                </li>';
        
        // Add admin-only navigation items
        if ($user['role'] === 'admin') {
            echo '
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.php">
                        <i class="fas fa-users-cog"></i>
                        مدیران سیستم
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="general_settings.php">
                        <i class="fas fa-cog"></i>
                        تنظیمات عمومی
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="audit_logs.php">
                        <i class="fas fa-clipboard-check"></i>
                        گزارش رفتار کاربران
                    </a>
                </li>';
        }
        
        echo '
            </ul>
        </nav>
        <div class="user-info">
            <div class="d-flex align-items-center mb-2">
                <i class="fas fa-user-circle fa-2x me-2"></i>
                <div>
                    <div class="fw-bold">' . htmlspecialchars($user['display_name'] ?: $user['username']) . '</div>
                    <small class="text-muted">' . ($user['role'] === 'admin' ? 'مدیر سیستم' : 'پشتیبان') . '</small>
                </div>
            </div>
            <div class="d-grid">
                <form method="POST" action="logout.php">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt me-1"></i>
                        خروج
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <button class="sidebar-toggle" onclick="toggleSidebar()" id="sidebarToggle">
        <i class="fas fa-chevron-right"></i>
    </button>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">' . htmlspecialchars($title) . '</h2>
                <div class="text-muted">
                    <i class="fas fa-calendar-alt me-1"></i>
                    ' . JalaliDate::getCurrentJalaliDate() . '
                </div>
            </div>
        </div>
        <div class="content-area">';
    }
    
    /**
     * Render the admin footer
     */
    public static function renderFooter() {
        echo '</div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set active navigation item
        const currentPage = window.location.pathname.split("/").pop();
        const navLinks = document.querySelectorAll(".nav-link");
        navLinks.forEach(link => {
            if (link.getAttribute("href") === currentPage) {
                link.classList.add("active");
            }
        });
        
        // Toggle submenu functionality
        function toggleSubmenu(element) {
            const navItem = element.closest(\'.nav-item\');
            const isActive = navItem.classList.contains(\'active\');
            
            // Remove active class from all submenu items
            document.querySelectorAll(\'.nav-item.has-submenu\').forEach(item => {
                item.classList.remove(\'active\');
            });
            
            // Toggle current item
            if (!isActive) {
                navItem.classList.add(\'active\');
            }
        }
        
        // Sidebar toggle functionality
        function toggleSidebar() {
            const sidebar = document.querySelector(\'.sidebar\');
            const mainContent = document.querySelector(\'.main-content\');
            const toggleBtn = document.getElementById(\'sidebarToggle\');
            
            // Check if mobile
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                sidebar.classList.toggle(\'show\');
                // Update button icon for mobile
                if (sidebar.classList.contains(\'show\')) {
                    toggleBtn.innerHTML = \'<i class="fas fa-times"></i>\';
                } else {
                    toggleBtn.innerHTML = \'<i class="fas fa-bars"></i>\';
                }
            } else {
                sidebar.classList.toggle(\'hidden\');
                mainContent.classList.toggle(\'full-width\');
                
                // Update button icon for desktop
                if (sidebar.classList.contains(\'hidden\')) {
                    toggleBtn.innerHTML = \'<i class="fas fa-chevron-left"></i>\';
                } else {
                    toggleBtn.innerHTML = \'<i class="fas fa-chevron-right"></i>\';
                }
                
                // Save state to localStorage
                localStorage.setItem(\'sidebarHidden\', sidebar.classList.contains(\'hidden\'));
            }
        }
        
        // Restore sidebar state on page load
        document.addEventListener(\'DOMContentLoaded\', function() {
            const sidebarHidden = localStorage.getItem(\'sidebarHidden\') === \'true\';
            const isMobile = window.innerWidth <= 768;
            
            if (sidebarHidden && !isMobile) {
                const sidebar = document.querySelector(\'.sidebar\');
                const mainContent = document.querySelector(\'.main-content\');
                const toggleBtn = document.getElementById(\'sidebarToggle\');
                
                sidebar.classList.add(\'hidden\');
                mainContent.classList.add(\'full-width\');
                toggleBtn.innerHTML = \'<i class="fas fa-chevron-left"></i>\';
            } else if (isMobile) {
                const toggleBtn = document.getElementById(\'sidebarToggle\');
                toggleBtn.innerHTML = \'<i class="fas fa-bars"></i>\';
            }
            
            // Apply font size from settings
            applyFontSize();
            
            // Handle window resize for mobile/desktop switching
            window.addEventListener(\'resize\', function() {
                const isMobile = window.innerWidth <= 768;
                const sidebar = document.querySelector(\'.sidebar\');
                const toggleBtn = document.getElementById(\'sidebarToggle\');
                
                if (isMobile) {
                    // Mobile mode
                    sidebar.classList.remove(\'hidden\');
                    sidebar.classList.remove(\'show\');
                    toggleBtn.innerHTML = \'<i class="fas fa-bars"></i>\';
                } else {
                    // Desktop mode
                    sidebar.classList.remove(\'show\');
                    const sidebarHidden = localStorage.getItem(\'sidebarHidden\') === \'true\';
                    if (sidebarHidden) {
                        sidebar.classList.add(\'hidden\');
                        toggleBtn.innerHTML = \'<i class="fas fa-chevron-left"></i>\';
                    } else {
                        toggleBtn.innerHTML = \'<i class="fas fa-chevron-right"></i>\';
                    }
                }
            });
        });
        
        // Function to apply font size
        function applyFontSize() {
            // Get font size from localStorage or use default
            const fontSize = localStorage.getItem(\'systemFontSize\') || \'14\';
            document.documentElement.style.setProperty(\'--system-font-size\', fontSize + \'px\');
        }
    </script>
    
    <!-- Footer -->
    <footer class="mt-5 py-3 bg-light border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <small class="text-muted">' . htmlspecialchars(getSetting('footer_text', 'تمامی حقوق محفوظ است')) . '</small>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>';
    }
    
    /**
     * Render a success alert
     */
    public static function renderSuccessAlert($message) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            ' . htmlspecialchars($message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
    
    /**
     * Render an error alert
     */
    public static function renderErrorAlert($message) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ' . htmlspecialchars($message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}
?>
