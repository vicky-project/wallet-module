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
            transition: all var(--transition-speed);
            z-index: 1000;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar.collapsed {
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
        
        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed);
            min-height: 100vh;
        }
        
        .main-content.expanded {
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
            z-index: 999;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
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
        
        /* Theme Toggle */
        .theme-toggle {
            width: 60px;
            height: 30px;
            background-color: #e9ecef;
            border-radius: 15px;
            position: relative;
            cursor: pointer;
            transition: background-color var(--transition-speed);
        }
        
        .theme-toggle::after {
            content: '';
            position: absolute;
            width: 26px;
            height: 26px;
            background-color: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform var(--transition-speed);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .theme-toggle.active {
            background-color: var(--primary-color);
        }
        
        .theme-toggle.active::after {
            transform: translateX(30px);
        }
        
        /* Dark Theme */
        body[data-bs-theme="dark"] {
            background-color: #121212;
            color: #f8f9fa;
        }
        
        body[data-bs-theme="dark"] .header {
            background-color: #1e1e1e;
            color: #f8f9fa;
        }
        
        body[data-bs-theme="dark"] .card {
            background-color: #1e1e1e;
            color: #f8f9fa;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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
        
        /* Quick Actions */
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px 10px;
            border-radius: 12px;
            background-color: rgba(67, 97, 238, 0.05);
            border: 1px solid rgba(67, 97, 238, 0.1);
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .quick-action-btn:hover {
            background-color: rgba(67, 97, 238, 0.1);
            transform: translateY(-3px);
        }
        
        body[data-bs-theme="dark"] .quick-action-btn {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block !important;
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
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h3><i class="bi bi-wallet2"></i> FinTrack</h3>
            <small class="text-light opacity-75">Manajemen Keuangan Pribadi</small>
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
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary sidebar-toggle d-none me-3" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h4 class="mb-0">Dashboard Keuangan</h4>
            </div>
            
            <div class="header-actions">
                <div class="d-flex align-items-center">
                    <span class="me-2 d-none d-md-inline">Mode Gelap</span>
                    <div class="theme-toggle" id="themeToggle"></div>
                </div>
                
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> John Doe
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
            
            <!-- Quick Actions and Budget -->
            <div class="row">
                <!-- Quick Actions -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Aksi Cepat</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-plus-circle display-6 text-primary mb-2"></i>
                                        <span>Tambah Pemasukan</span>
                                    </a>
                                </div>
                                <div class="col-6 col-md-3">
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-dash-circle display-6 text-danger mb-2"></i>
                                        <span>Tambah Pengeluaran</span>
                                    </a>
                                </div>
                                <div class="col-6 col-md-3">
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-repeat display-6 text-success mb-2"></i>
                                        <span>Transaksi Rutin</span>
                                    </a>
                                </div>
                                <div class="col-6 col-md-3">
                                    <a href="#" class="quick-action-btn">
                                        <i class="bi bi-file-earmark-text display-6 text-warning mb-2"></i>
                                        <span>Laporan</span>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h6>Tips Manajemen Keuangan</h6>
                                <div class="alert alert-info mt-2">
                                    <small>
                                        <i class="bi bi-lightbulb me-2"></i>
                                        <strong>Catat semua transaksi</strong> sekecil apapun untuk mendapatkan gambaran keuangan yang akurat.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Budget Overview -->
                <div class="col-lg-6 mb-4">
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
            // Toggle Sidebar untuk mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-open');
                });
            }
            
            // Toggle tema gelap/terang
            const themeToggle = document.getElementById('themeToggle');
            const currentTheme = localStorage.getItem('theme') || 'light';
            
            // Set tema awal
            if (currentTheme === 'dark') {
                document.body.setAttribute('data-bs-theme', 'dark');
                themeToggle.classList.add('active');
            }
            
            themeToggle.addEventListener('click', function() {
                if (document.body.getAttribute('data-bs-theme') === 'light') {
                    document.body.setAttribute('data-bs-theme', 'dark');
                    themeToggle.classList.add('active');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.body.setAttribute('data-bs-theme', 'light');
                    themeToggle.classList.remove('active');
                    localStorage.setItem('theme', 'light');
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
            
            // Simulasi data dari Laravel (dalam implementasi nyata, ini akan diambil dari API)
            console.log("Template siap diintegrasikan dengan Laravel. Data keuangan akan di-generate oleh Laravel.");
            
            // Event listener untuk quick action buttons
            document.querySelectorAll('.quick-action-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const actionText = this.querySelector('span').textContent;
                    console.log(`Aksi: ${actionText} - akan diarahkan ke halaman yang sesuai`);
                    // Dalam implementasi nyata, ini akan mengarahkan ke route Laravel yang sesuai
                });
            });
        });
    </script>
</body>
</html>