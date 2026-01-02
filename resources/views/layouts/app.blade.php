<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
      @yield('title', config('app.name', 'Vicky Server'))
    </title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Chart.js untuk visualisasi -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
            --sidebar-width: 250px;
            --header-height: 70px;
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--light-bg);
            color: #333;
            transition: background-color var(--transition-speed);
            overflow-x: hidden;
        }
        
        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding-top: 20px;
            transition: transform var(--transition-speed);
            z-index: 1050;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-hidden {
            transform: translateX(-100%);
        }
        
        .sidebar-brand {
            padding: 0 20px 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-brand h3 {
            font-weight: 700;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-nav li {
            margin-bottom: 5px;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: white;
        }
        
        .sidebar-nav i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        /* Sidebar Overlay untuk mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            opacity: 0;
            transition: opacity var(--transition-speed);
        }
        
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed), margin-right var(--transition-speed);
            min-height: 100vh;
        }
        
        .main-content-full {
            margin-left: 0;
        }
        
        /* Header Styling */
        .header {
            height: var(--header-height);
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Card Styling */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        /* Theme Toggle Button */
        .theme-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dee2e6;
            background-color: transparent;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .theme-btn:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
            color: #495057;
        }
        
        body[data-bs-theme="dark"] .theme-btn {
            border-color: #495057;
            color: #adb5bd;
        }
        
        body[data-bs-theme="dark"] .theme-btn:hover {
            background-color: #343a40;
            border-color: #6c757d;
            color: #f8f9fa;
        }
        
        /* Profile Button */
        .profile-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dee2e6;
            background-color: transparent;
            color: #6c757d;
            transition: all 0.3s ease;
            padding: 0;
        }
        
        .profile-btn:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
            color: #495057;
        }
        
        body[data-bs-theme="dark"] .profile-btn {
            border-color: #495057;
            color: #adb5bd;
        }
        
        body[data-bs-theme="dark"] .profile-btn:hover {
            background-color: #343a40;
            border-color: #6c757d;
            color: #f8f9fa;
        }
        
        /* Dark Theme */
        body[data-bs-theme="dark"] {
            background-color: #121212;
            color: #f8f9fa;
        }
        
        body[data-bs-theme="dark"] .header {
            background-color: #1e1e1e;
            color: #f8f9fa;
            border-bottom: 1px solid #2d3748;
        }
        
        body[data-bs-theme="dark"] .card {
            background-color: #1e1e1e;
            color: #f8f9fa;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid #2d3748;
        }
        
        body[data-bs-theme="dark"] .sidebar {
            background: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
        }
        
        /* Transaction List */
        .transaction-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: background-color 0.2s;
        }
        
        body[data-bs-theme="dark"] .transaction-item {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }
        
        .transaction-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        body[data-bs-theme="dark"] .transaction-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        /* Floating Action Button (FAB) */
        .fab-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1080;
        }
        
        .fab-main {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .fab-main:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.5);
        }
        
        .fab-main.active {
            transform: rotate(45deg);
            background: linear-gradient(135deg, #f72585, #f8961e);
        }
        
        .fab-menu {
            position: absolute;
            bottom: 70px;
            right: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        
        .fab-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .fab-item {
            display: flex;
            align-items: center;
            background-color: white;
            color: #333;
            padding: 12px 20px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            text-decoration: none;
            transform: translateX(10px);
            opacity: 0;
        }
        
        .fab-menu.active .fab-item {
            transform: translateX(0);
            opacity: 1;
        }
        
        .fab-menu.active .fab-item:nth-child(1) {
            transition-delay: 0.05s;
        }
        
        .fab-menu.active .fab-item:nth-child(2) {
            transition-delay: 0.1s;
        }
        
        .fab-menu.active .fab-item:nth-child(3) {
            transition-delay: 0.15s;
        }
        
        .fab-menu.active .fab-item:nth-child(4) {
            transition-delay: 0.2s;
        }
        
        body[data-bs-theme="dark"] .fab-item {
            background-color: #2d3748;
            color: #f8f9fa;
            border: 1px solid #4a5568;
        }
        
        .fab-item:hover {
            transform: translateX(-5px) !important;
            background-color: #f8f9fa;
        }
        
        body[data-bs-theme="dark"] .fab-item:hover {
            background-color: #374151;
        }
        
        .fab-item i {
            font-size: 1.2rem;
            margin-right: 10px;
            width: 24px;
            text-align: center;
        }
        
        .fab-label {
            font-size: 0.9rem;
            font-weight: 500;
            margin-right: 10px;
        }
        
        .fab-income {
            color: #10b981;
        }
        
        .fab-expense {
            color: #ef4444;
        }
        
        .fab-recurring {
            color: #3b82f6;
        }
        
        .fab-report {
            color: #f59e0b;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1050;
            }
            
            .sidebar-mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block !important;
            }
            
            .fab-container {
                bottom: 20px;
                right: 20px;
            }
            
            .fab-item {
                padding: 10px 16px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .fab-container {
                bottom: 15px;
                right: 15px;
            }
            
            .fab-main {
                width: 56px;
                height: 56px;
                font-size: 1.3rem;
            }
            
            .fab-item .fab-label {
                display: none;
            }
            
            .fab-item {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                padding: 0;
                justify-content: center;
            }
            
            .fab-item i {
                margin-right: 0;
                font-size: 1.4rem;
            }
        }
        
        /* Custom Utilities */
        .cursor-pointer {
            cursor: pointer;
        }
        
        .text-income {
            color: #10b981;
        }
        
        .text-expense {
            color: #ef4444;
        }
        
        .bg-income {
            background-color: rgba(16, 185, 129, 0.1);
        }
        
        .bg-expense {
            background-color: rgba(239, 68, 68, 0.1);
        }
        
        .border-radius-12 {
            border-radius: 12px;
        }
        
        .h-100 {
            height: 100%;
        }
        
        .w-100 {
            width: 100%;
        }
        
        /* Tombol Toggle Sidebar */
        .sidebar-toggle {
            transition: all 0.3s;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        
        .sidebar-toggle.active i {
            transform: rotate(90deg);
        }
        
        .sidebar-toggle i {
            transition: transform 0.3s;
        }
        
        /* Page Title in Content Area */
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: inherit;
        }
        
        body[data-bs-theme="dark"] .page-title {
            color: #f8f9fa;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-1"><i class="bi bi-wallet2"></i> {{ config('app.name', 'VickyServer') }}</h3>
                <small class="text-light opacity-75">Manajemen Keuangan Pribadi</small>
            </div>
        </div>
        
        <ul class="sidebar-nav">
            <li>
                <a href="{{ route('apps.financial') }}" class="active">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('apps.transactions.index') }}">
                    <i class="bi bi-currency-dollar"></i> Transactions
                </a>
            </li>
            <li>
                <a href="{{ route('apps.categories.index') }}">
                    <i class="bi bi-pie-chart"></i> Categories
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="bi bi-calendar-month"></i> Anggaran
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="bi bi-graph-up"></i> Laporan
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="bi bi-bullseye"></i> Tujuan
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="bi bi-wallet"></i> Akun Bank
                </a>
            </li>
            <li class="mt-4">
                <a href="#">
                    <i class="bi bi-gear"></i> Pengaturan
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="bi bi-question-circle"></i> Bantuan
                </a>
            </li>
        </ul>
        
        <div class="position-absolute bottom-0 w-100 p-3 text-center">
            <div class="card bg-dark text-white">
                <div class="card-body py-3">
                    <small>Saldo Tersimpan</small>
                    <h5 class="mb-0">Rp 12.450.000</h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Overlay untuk menutup sidebar di mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <button class="btn btn-outline-secondary sidebar-toggle d-lg-none me-2" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            
            <div class="header-actions">
                <!-- Tombol Tema Light/Dark dengan ikon -->
                <button class="btn theme-btn" id="themeToggle" title="Ubah Tema">
                    <i class="bi bi-sun" id="themeIcon"></i>
                </button>
                
                <!-- Tombol Profile Dropdown dengan ikon saja -->
                <div class="dropdown">
                    <button class="btn profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Profil Pengguna">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profil</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Pengaturan</a></li>
                        @if(Route::has('cores.modules.index'))
                        <li><a href="{{ route('cores.modules.index') }}" class="dropdown-item"><i class="bi bi-server me-2"></i>Server</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                    @if(Route::has('logout'))
                      <li>
                        <form method="POST" action="{{ route('logout') }}" id="formLogout">
                        @csrf
                        </form>
                        <button type="button" class="dropdown-item text-danger" onclick="if(confirm('Are you sure to logout this session?')) document.getElementById('formLogout').submit();"><i class="bi bi-box-arrow-right me-2"></i> Keluar</button>
                      </li>
                    @endif
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Main Dashboard Content -->
        <div class="container-fluid p-4">
          @yield('content')
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Inisialisasi tema
        document.addEventListener('DOMContentLoaded', function() {
            // Elemen DOM
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mainContent = document.getElementById('mainContent');
            
            // Elemen Tema
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            
            // Fungsi untuk membuka sidebar
            function openSidebar() {
                sidebar.classList.remove('sidebar-hidden');
                sidebar.classList.add('sidebar-mobile-open');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            // Fungsi untuk menutup sidebar
            function closeSidebar() {
                sidebar.classList.remove('sidebar-mobile-open');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
                
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove('sidebar-hidden');
                } else {
                    setTimeout(() => {
                        if (!sidebar.classList.contains('sidebar-mobile-open')) {
                            sidebar.classList.add('sidebar-hidden');
                        }
                    }, 300);
                }
            }
            
            // Toggle Sidebar untuk mobile
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (sidebar.classList.contains('sidebar-mobile-open')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                });
            }
            
            // Tutup sidebar ketika klik overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    closeSidebar();
                });
            }
            
            // Tutup sidebar ketika klik di luar sidebar (untuk desktop)
            document.addEventListener('click', function(e) {
                if (window.innerWidth >= 992) {
                    return;
                }
                
                if (sidebar.classList.contains('sidebar-mobile-open') && 
                    !sidebar.contains(e.target) && 
                    e.target !== sidebarToggle) {
                    closeSidebar();
                }
            });

            // Fungsi untuk mengubah ikon tema
            function updateThemeIcon(isDark) {
                if (isDark) {
                    themeIcon.classList.remove('bi-moon');
                    themeIcon.classList.add('bi-sun');
                    themeToggle.setAttribute('title', 'Ubah ke Mode Terang');
                } else {
                    themeIcon.classList.remove('bi-sun');
                    themeIcon.classList.add('bi-moon');
                    themeToggle.setAttribute('title', 'Ubah ke Mode Gelap');
                }
            }
            
            // Toggle tema gelap/terang
            const currentTheme = localStorage.getItem('theme') || 'light';
            
            // Set tema awal
            if (currentTheme === 'dark') {
                document.body.setAttribute('data-bs-theme', 'dark');
                updateThemeIcon(true);
            } else {
                updateThemeIcon(false);
            }
            
            themeToggle.addEventListener('click', function() {
                if (document.body.getAttribute('data-bs-theme') === 'light') {
                    document.body.setAttribute('data-bs-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    updateThemeIcon(true);
                } else {
                    document.body.setAttribute('data-bs-theme', 'light');
                    localStorage.setItem('theme', 'light');
                    updateThemeIcon(false);
                }
            });
            
            // Responsif: saat resize window
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    // Desktop: tampilkan sidebar, hilangkan overlay
                    sidebar.classList.remove('sidebar-hidden', 'sidebar-mobile-open');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                } else {
                    // Mobile: sembunyikan sidebar jika tidak aktif
                    if (!sidebar.classList.contains('sidebar-mobile-open')) {
                        sidebar.classList.add('sidebar-hidden');
                    }
                }
                
                // Tutup FAB menu pada resize (untuk konsistensi UX)
                if (fabMenu.classList.contains('active')) {
                    toggleFabMenu();
                }
            });
            
            // Format currency untuk semua elemen dengan class .currency
            document.querySelectorAll('.currency').forEach(element => {
                const value = parseFloat(element.textContent.replace(/[^0-9.-]+/g,""));
                if (!isNaN(value)) {
                    element.textContent = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(value);
                }
            });
            
            // Inisialisasi sidebar berdasarkan ukuran layar
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('sidebar-hidden');
            } else {
                sidebar.classList.add('sidebar-hidden');
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>