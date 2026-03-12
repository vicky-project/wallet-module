<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- di dalam head tag -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>
    @yield('title', config('app.name', 'Vicky Server'))
  </title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
  <!-- Chart.js untuk visualisasi -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Telegram WebApp SDK -->
  <script src="https://telegram.org/js/telegram-web-app.js?59"></script>
  <style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --success-color: #4cc9f0;
    --danger-color: #f72585;
    --warning-color: #f8961e;
    --bg-color: #f8f9fa;
    --text-color: #333;
    --card-bg: #ffffff;
    --header-bg: #ffffff;
    --sidebar-bg: linear-gradient(180deg var(--primary-color) 0%, var(--secondary-color) 100%);
    --sidebar-text: #ffffff;
    --border-color: rgba(0, 0, 0, 0.05);
    --hover-bg: rgb(0, 0, 0, 0.02);
    --fab-bg: #ffffff;
    --fab-text: #333;
    --fab-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    --transition-speed: 0.3s;
  }

    body[data-bs-theme="dark"] {
      --primary-color: #5a67d8;
      --secondary-color: #4c51bf;
      --success-color: #38b2ac;
      --danger-color: #f56565;
      --warning-color: #ed8936;
      --bg-color: #121212;
      --text-color: #f8f9fa;
      --card-bg: #1e1e1e;
      --header-bg: #1e1e1e;
      --sidebar-bg: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
      --sidebar-text: #f8f9fa;
      --border-color: rgba(255, 255, 255, 0.05);
      --hover-bg: rgba(255, 255, 255, 0.05);
      --fab-bg: #2d3748;
      --fab-text: #f8f9fa;
      --fab-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      }

      body.telegram-app {
      --primary-color: var(--tg-theme-button-color, #4361ee);
      --secondary-color: var(--tg-theme-button-color, #3f37c9);
      --bg-color: var(--tg-theme-bg-color, #f8f9fa);
      --text-color: var(--tg-theme-text-color, #333);
      --card-bg: var(--tg-theme-bg-color, #ffffff);
      --header-bg: var(--tg-theme-header-bg-color, #ffffff);
      --sidebar-bg: var(--tg-theme-secondary-bg-color, linear-gradient(180deg, #4361ee 0%, #3f37c9 100%));
      --sidebar-text: var(--tg-theme-text-color, #ffffff);
      --border-color: var(--tg-theme-hint-color, rgba(0,0,0,0.05));
      --hover-bg: var(--tg-theme-hint-color, rgba(0,0,0,0.02));
      --fab-bg: var(--tg-theme-bg-color, #ffffff);
      --fab-text: var(--tg-theme-text-color, #333);
      --fab-shadow: 0 4px 12px var(--tg-theme-hint-color, rgba(0,0,0,0.1));
      }

      body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      transition: background-color var(--transition-speed), color var(--transition-speed);
      overflow-x: hidden;
      }

      /* Sidebar Styling */
      .sidebar {
      width: var(--sidebar-width);
      height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      background: var(--sidebar-bg);
      color: var(--sidebar-text);
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
      color: var(--sidebar-text);
      text-decoration: none;
      transition: all 0.2s;
      border-left: 4px solid transparent;
      opacity: 0.9;
      }

      .sidebar-nav a:hover, .sidebar-nav a.active {
      background-color: rgba(255, 255, 255, 0.1);
      color: var(--sidebar-text);
      border-left-color: var(--sidebar-text);
      opacity: 1;
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
      background-color: var(--header-bg);
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
      background-color: var(--card-bg);
      border: 1px solid var(--border-color);
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
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      }

      /* Theme Toggle Button */
      .theme-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--border-color);
      background-color: transparent;
      color: var(--text-color);
      transition: all 0.3s ease;
      }

      .theme-btn:hover {
      background-color: var(--hover-bg);
      border-color: var(--text-color);
      }

      body.telegram-app .theme-btn {
      display: none; /* Sembunyikan toggle tema di Telegram Mini App */
      }

      /* Profile Button */
      .profile-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--border-color);
      background-color: transparent;
      color: var(--text-color);
      transition: all 0.3s ease;
      padding: 0;
      }

      .profile-btn:hover {
      background-color: var(--hover-bg);
      border-color: var(--text-color);
      }

      /* Transaction List */
      .transaction-item {
      display: flex;
      align-items: center;
      padding: 15px;
      border-bottom: 1px solid var(--border-color);
      transition: background-color 0.2s;
      }

      .transaction-item:hover {
      background-color: var(--hover-bg);
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
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
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
      background-color: var(--fab-bg);
      color: var(--fab-text);
      padding: 12px 20px;
      border-radius: 50px;
      box-shadow: var(--fab-shadow);
      cursor: pointer;
      transition: all 0.3s ease;
      white-space: nowrap;
      text-decoration: none;
      transform: translateX(10px);
      opacity: 0;
      border: 1px solid var(--border-color);
      }

      .fab-menu.active .fab-item {
      transform: translateX(0);
      opacity: 1;
      }

      .fab-menu.active .fab-item:nth-child(1) { transition-delay: 0.05s; }
      .fab-menu.active .fab-item:nth-child(2) { transition-delay: 0.1s; }
      .fab-menu.active .fab-item:nth-child(3) { transition-delay: 0.15s; }
      .fab-menu.active .fab-item:nth-child(4) { transition-delay: 0.2s; }

      .fab-item:hover {
      transform: translateX(-5px) !important;
      background-color: var(--hover-bg);
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

      .fab-income { color: #10b981; }
      .fab-expense { color: #ef4444; }
      .fab-recurring { color: #3b82f6; }
      .fab-report { color: #f59e0b; }

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
      .text-income { color: #10b981; }
      .text-expense { color: #ef4444; }
      .bg-income { background-color: rgba(16, 185, 129, 0.1); }
      .bg-expense { background-color: rgba(239, 68, 68, 0.1); }
      .border-radius-12 { border-radius: 12px; }
      .h-100 { height: 100%; }
      .w-100 { width: 100%; }

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

      /* Page Title */
      .page-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: inherit;
      }
      </style>

      @stack('styles')
      </head>
      <body>
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

      // Deteksi Telegram Mini App
      const tg = window.Telegram?.WebApp;
      if (tg) {
      document.body.classList.add('telegram-app');
      tg.ready();
      tg.expand();

      // Terapkan tema Telegram ke CSS variables via class
      // CSS sudah menggunakan var(--tg-theme-*) dengan fallback
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
      sidebarOverlay.addEventListener('click', closeSidebar());
      }

      // Tutup sidebar ketika klik di luar sidebar (untuk desktop)
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

      const token = localStorage.getItem("telegram_token") || '{{ request()->get("token") }}';
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