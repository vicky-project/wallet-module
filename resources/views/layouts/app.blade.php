<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', config('app.name', 'Vicky Server'))</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
  <!-- Chart.js untuk visualisasi -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Telegram WebApp SDK -->
  <script src="https://telegram.org/js/telegram-web-app.js?61"></script>

  @include('wallet::partials.styles')
  @stack('styles')
</head>
<body class="{{ request()->has('token') ? 'telegram-app' : ''}}">
  <!-- Sidebar Navigation -->
  @include('wallet::partials.sidebar')

  <!-- Overlay untuk menutup sidebar di mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Main Content -->
  <div class="main-content" id="mainContent">
    <!-- Header -->
    @include('wallet::partials.header')

    <!-- Main Dashboard Content -->
    <div class="container-fluid p-4 mb-4">
      @include('wallet::partials.flash-message')
      @yield('content')
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
    // Elemen DOM
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.getElementById('mainContent');

    // Elemen Tema
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    // Deteksi Telegram Mini App
    const tg = window.Telegram?.WebApp;
    if (tg?.initData) {
    tg.ready();
    tg.expand();
    }

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
    sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // Tutup sidebar ketika klik di luar (mobile)
    document.addEventListener('click', function(e) {
    if (window.innerWidth >= 992) return;
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

    // Toggle tema gelap/terang (hanya jika bukan Telegram)
    if (!document.body.classList.contains('telegram-app')) {
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.body.setAttribute('data-bs-theme', currentTheme);
    updateThemeIcon(currentTheme === 'dark');

    if (themeToggle) {
    themeToggle.addEventListener('click', function() {
    const newTheme = document.body.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
    document.body.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme === 'dark');
    });
    }
    } else {
    // Di Telegram, toggle tema disembunyikan via CSS, jadi tidak perlu event listener
    // Sebaliknya, kita perlu mendekorasi ulang navigasi link
    const initData = window.Telegram?.WebApp?.initData || @json(request()->get("initData", ""));
    if(!initData) return;

    const token = localStorage.getItem("telegram_token") || '{{ request()->get("token") }}' || "";
    if(!token) return;

    const navLinks = document.querySelectorAll("a");
    navLinks.forEach(function(link) {
    const urlObj = new URL(link.href, window.location.origin);
    urlObj.searchParams.set("token", token);
    urlObj.searchParams.set("initData", initData);
    link.href = urlObj.toString();
    });
    }

    // Responsif: saat resize window
    window.addEventListener('resize', function() {
    if (window.innerWidth >= 992) {
    sidebar.classList.remove('sidebar-hidden', 'sidebar-mobile-open');
    sidebarOverlay.classList.remove('active');
    document.body.style.overflow = '';
    } else {
    if (!sidebar.classList.contains('sidebar-mobile-open')) {
    sidebar.classList.add('sidebar-hidden');
    }
    }
    });

    // Format currency
    document.querySelectorAll('.currency').forEach(element => {
    const value = parseFloat(element.textContent.replace(/[^0-9.-]+/g, ""));
    if (!isNaN(value)) {
    element.textContent = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
    }).format(value);
    }
    });

    // Inisialisasi sidebar
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