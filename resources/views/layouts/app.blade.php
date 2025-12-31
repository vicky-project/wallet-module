<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinTrack - Dashboard Keuangan</title>
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
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-1"><i class="bi bi-wallet2"></i> FinTrack</h3>
                <small class="text-light opacity-75">Manajemen Keuangan Pribadi</small>
            </div>
            <button class="btn btn-sm btn-outline-light d-lg-none sidebar-close" id="sidebarClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <ul class="sidebar-nav">
            <li>
                <a href="#" class="active">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="bi bi-arrow-up-circle"></i> Pemasukan
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="bi bi-arrow-down-circle"></i> Pengeluaran
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="bi bi-pie-chart"></i> Kategori
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
    
    <!-- Floating Action Button (FAB) -->
    <div class="fab-container" id="fabContainer">
        <div class="fab-menu" id="fabMenu">
            <a href="#" class="fab-item" id="fabIncome">
                <i class="bi bi-plus-circle fab-income"></i>
                <span class="fab-label">Tambah Pemasukan</span>
            </a>
            <a href="#" class="fab-item" id="fabExpense">
                <i class="bi bi-dash-circle fab-expense"></i>
                <span class="fab-label">Tambah Pengeluaran</span>
            </a>
            <a href="#" class="fab-item" id="fabRecurring">
                <i class="bi bi-repeat fab-recurring"></i>
                <span class="fab-label">Transaksi Rutin</span>
            </a>
            <a href="#" class="fab-item" id="fabReport">
                <i class="bi bi-file-earmark-text fab-report"></i>
                <span class="fab-label">Laporan</span>
            </a>
        </div>
        <button class="fab-main" id="fabMain">
            <i class="bi bi-plus-lg" id="fabIcon"></i>
        </button>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <button class="btn btn-outline-secondary sidebar-toggle d-lg-none me-2" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <!-- Judul Dashboard dihapus sesuai permintaan -->
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
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Main Dashboard Content -->
        <div class="container-fluid p-4">
            <!-- Page Title dipindahkan ke sini -->
            <h1 class="page-title">Dashboard Keuangan</h1>
            
            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-income">
                                    <i class="bi bi-arrow-up-circle text-income"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="card-subtitle mb-1">Pemasukan Bulan Ini</h6>
                                    <h3 class="card-title mb-0 text-income">Rp 8.250.000</h3>
                                    <small class="text-muted">+12% dari bulan lalu</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="card-icon bg-expense">
                                    <i class="bi bi-arrow-down-circle text-expense"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="card-subtitle mb-1">Pengeluaran Bulan Ini</h6>
                                    <h3 class="card-title mb-0 text-expense">Rp 5.120.000</h3>
                                    <small class="text-muted">-5% dari bulan lalu</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="card-icon" style="background-color: rgba(59, 130, 246, 0.1);">
                                    <i class="bi bi-graph-up" style="color: #3b82f6;"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="card-subtitle mb-1">Saldo Bersih</h6>
                                    <h3 class="card-title mb-0">Rp 3.130.000</h3>
                                    <small class="text-muted">+25% dari bulan lalu</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="card-icon" style="background-color: rgba(245, 158, 11, 0.1);">
                                    <i class="bi bi-piggy-bank" style="color: #f59e0b;"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="card-subtitle mb-1">Tabungan Tercapai</h6>
                                    <h3 class="card-title mb-0">78%</h3>
                                    <small class="text-muted">Rp 3.9jt dari target 5jt</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Transactions -->
            <div class="row mb-4">
                <!-- Chart Section -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Ringkasan Keuangan (6 Bulan Terakhir)</h5>
                            <select class="form-select form-select-sm w-auto">
                                <option>6 Bulan Terakhir</option>
                                <option>Tahun Ini</option>
                                <option>Tahun Lalu</option>
                            </select>
                        </div>
                        <div class="card-body">
                            <canvas id="financeChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Transaksi Terbaru</h5>
                            <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="transaction-item">
                                <div class="transaction-icon bg-income">
                                    <i class="bi bi-arrow-up-circle text-income"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Gaji Bulanan</h6>
                                    <small class="text-muted">12 April 2023 • Gaji</small>
                                </div>
                                <div class="text-income">
                                    +Rp 5.000.000
                                </div>
                            </div>
                            
                            <div class="transaction-item">
                                <div class="transaction-icon bg-expense">
                                    <i class="bi bi-cart-check text-expense"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Belanja Bulanan</h6>
                                    <small class="text-muted">10 April 2023 • Belanja</small>
                                </div>
                                <div class="text-expense">
                                    -Rp 1.250.000
                                </div>
                            </div>
                            
                            <div class="transaction-item">
                                <div class="transaction-icon bg-expense">
                                    <i class="bi bi-lightning-charge text-expense"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Bayar Listrik</h6>
                                    <small class="text-muted">8 April 2023 • Utilitas</small>
                                </div>
                                <div class="text-expense">
                                    -Rp 450.000
                                </div>
                            </div>
                            
                            <div class="transaction-item">
                                <div class="transaction-icon bg-income">
                                    <i class="bi bi-cash-coin text-income"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Freelance Project</h6>
                                    <small class="text-muted">5 April 2023 • Freelance</small>
                                </div>
                                <div class="text-income">
                                    +Rp 2.500.000
                                </div>
                            </div>
                            
                            <div class="transaction-item">
                                <div class="transaction-icon bg-expense">
                                    <i class="bi bi-train-front text-expense"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">Transportasi</h6>
                                    <small class="text-muted">3 April 2023 • Transportasi</small>
                                </div>
                                <div class="text-expense">
                                    -Rp 320.000
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Budget Overview -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Ringkasan Anggaran</h5>
                            <a href="#" class="btn btn-sm btn-outline-primary">Atur Anggaran</a>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Makanan & Minuman</span>
                                    <span>Rp 850.000 / Rp 1.000.000</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 85%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Transportasi</span>
                                    <span>Rp 320.000 / Rp 500.000</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 64%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Hiburan</span>
                                    <span>Rp 450.000 / Rp 400.000</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 113%"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Belanja</span>
                                    <span>Rp 1.250.000 / Rp 1.500.000</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: 83%"></div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning mt-4">
                                <small>
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Perhatian:</strong> Anggaran untuk Hiburan telah melebihi batas. Pertimbangkan untuk mengurangi pengeluaran di kategori ini.
                                </small>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <small>
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <strong>Tips:</strong> Gunakan tombol <i class="bi bi-plus-lg"></i> di pojok kanan bawah untuk menambahkan transaksi dengan cepat!
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Inisialisasi tema
        document.addEventListener('DOMContentLoaded', function() {
            // Elemen DOM
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const mainContent = document.getElementById('mainContent');
            
            // Elemen FAB
            const fabMain = document.getElementById('fabMain');
            const fabMenu = document.getElementById('fabMenu');
            const fabIcon = document.getElementById('fabIcon');
            const fabIncome = document.getElementById('fabIncome');
            const fabExpense = document.getElementById('fabExpense');
            const fabRecurring = document.getElementById('fabRecurring');
            const fabReport = document.getElementById('fabReport');
            
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
            
            // Tombol close di dalam sidebar
            if (sidebarClose) {
                sidebarClose.addEventListener('click', function(e) {
                    e.stopPropagation();
                    closeSidebar();
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
            
            // FAB Toggle Functionality
            function toggleFabMenu() {
                fabMain.classList.toggle('active');
                fabMenu.classList.toggle('active');
                
                if (fabMain.classList.contains('active')) {
                    fabIcon.classList.remove('bi-plus-lg');
                    fabIcon.classList.add('bi-x');
                } else {
                    fabIcon.classList.remove('bi-x');
                    fabIcon.classList.add('bi-plus-lg');
                }
            }
            
            // Toggle FAB Menu
            fabMain.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleFabMenu();
            });
            
            // Tutup FAB Menu ketika klik di luar
            document.addEventListener('click', function(e) {
                if (!fabMain.contains(e.target) && !fabMenu.contains(e.target)) {
                    if (fabMenu.classList.contains('active')) {
                        toggleFabMenu();
                    }
                }
            });
            
            // Tutup FAB Menu ketika klik item menu
            [fabIncome, fabExpense, fabRecurring, fabReport].forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const action = this.id.replace('fab', '').toLowerCase();
                    console.log(`Aksi FAB: ${action}`);
                    
                    // Tutup menu setelah memilih
                    setTimeout(() => {
                        if (fabMenu.classList.contains('active')) {
                            toggleFabMenu();
                        }
                    }, 300);
                    
                    // Simulasi aksi (dalam implementasi nyata akan redirect ke halaman tertentu)
                    switch(action) {
                        case 'income':
                            alert('Membuka form Tambah Pemasukan');
                            // window.location.href = '/income/create';
                            break;
                        case 'expense':
                            alert('Membuka form Tambah Pengeluaran');
                            // window.location.href = '/expense/create';
                            break;
                        case 'recurring':
                            alert('Membuka halaman Transaksi Rutin');
                            // window.location.href = '/transactions/recurring';
                            break;
                        case 'report':
                            alert('Membuka halaman Laporan');
                            // window.location.href = '/reports';
                            break;
                    }
                });
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
            
            // Chart.js untuk visualisasi keuangan
            const ctx = document.getElementById('financeChart').getContext('2d');
            const financeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Nov', 'Des', 'Jan', 'Feb', 'Mar', 'Apr'],
                    datasets: [
                        {
                            label: 'Pemasukan',
                            data: [7200000, 7800000, 8000000, 8200000, 8100000, 8250000],
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Pengeluaran',
                            data: [5800000, 6200000, 5900000, 6100000, 5300000, 5120000],
                            backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR',
                                        minimumFractionDigits: 0
                                    }).format(context.parsed.y);
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000) + 'jt';
                                    }
                                    return 'Rp ' + value;
                                }
                            }
                        }
                    }
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
            
            // Simulasi data dari Laravel
            console.log("Template siap diintegrasikan dengan Laravel.");
            
            // Inisialisasi sidebar berdasarkan ukuran layar
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('sidebar-hidden');
            } else {
                sidebar.classList.add('sidebar-hidden');
            }
            
            // Animasi masuk untuk FAB (setelah halaman dimuat)
            setTimeout(() => {
                fabMain.style.transform = 'scale(1)';
            }, 500);
        });
    </script>
</body>
</html>