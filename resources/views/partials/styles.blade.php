<style>
:root {
  --primary-color: #4361ee;
  --secondary-color: #3f37c9;
  --success-color: #4cc9f0;
  --danger-color: #f72585;
  --warning-color: #f8961e;
  --bg-color: #f8f9fa;
  /* solid light background */
  --text-color: #212529;
  /* dark text */
  --text-muted: #6c757d;
  --card-bg: #ffffff;
  --card-border: 1px solid #dee2e6;
  --header-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --sidebar-bg: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
  --sidebar-text: #ffffff;
  --border-color: rgba(0, 0, 0, 0.05);
  --hover-bg: rgba(0, 0, 0, 0.02);
  --fab-bg: #ffffff;
  --fab-text: #212529;
  --fab-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  --transition-speed: 0.3s;
  --body-bg: #f8f9fa; /* solid light */
  --body-bg-attachment: scroll;
  --card-blur: none;
  --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  --input-bg: #ffffff;
  --input-border: #ced4da;
  --input-color: #212529;
  --label-color: #495057;
  }

  body[data-bs-theme="dark"] {
  --primary-color: #5a67d8;
  --secondary-color: #4c51bf;
  --success-color: #38b2ac;
  --danger-color: #f56565;
  --warning-color: #ed8936;
  --bg-color: #1a202c; /* solid dark */
  --text-color: #e9ecef;
  --text-muted: #a0aec0;
  --card-bg: #2d3748;
  --card-border: 1px solid #4a5568;
  --header-bg: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  --sidebar-bg: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
  --sidebar-text: #e9ecef;
  --border-color: rgba(255, 255, 255, 0.1);
  --hover-bg: rgba(255, 255, 255, 0.05);
  --fab-bg: #2d3748;
  --fab-text: #e9ecef;
  --fab-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  --body-bg: #1a202c;
  --body-bg-attachment: scroll;
  --card-blur: none;
  --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  --input-bg: #2d3748;
  --input-border: #4a5568;
  --input-color: #e9ecef;
  --label-color: #cbd5e0;
  }

  body.telegram-app {
  --primary-color: var(--tg-theme-button-color, #4361ee);
  --secondary-color: var(--tg-theme-button-color, #3f37c9);
  --bg-color: var(--tg-theme-bg-color, #f8f9fa);
  --text-color: var(--tg-theme-text-color, #212529);
  --card-bg: var(--tg-theme-bg-color, #ffffff);
  --card-border: 1px solid var(--tg-theme-hint-color, rgba(0,0,0,0.05));
  --header-bg: var(--tg-theme-header-bg-color, #ffffff);
  --sidebar-bg: var(--tg-theme-secondary-bg-color, linear-gradient(180deg, #4361ee 0%, #3f37c9 100%));
  --sidebar-text: var(--tg-theme-text-color, #ffffff);
  --border-color: var(--tg-theme-hint-color, rgba(0,0,0,0.05));
  --hover-bg: var(--tg-theme-hint-color, rgba(0,0,0,0.02));
  --fab-bg: var(--tg-theme-bg-color, #ffffff);
  --fab-text: var(--tg-theme-text-color, #212529);
  --fab-shadow: 0 4px 12px var(--tg-theme-hint-color, rgba(0,0,0,0.1));
  --body-bg: var(--tg-theme-bg-color, #f8f9fa);
  --body-bg-attachment: scroll;
  --card-blur: none;
  --card-shadow: none;
  --input-bg: var(--tg-theme-bg-color, #ffffff);
  --input-border: var(--tg-theme-hint-color, #ced4da);
  --input-color: var(--tg-theme-text-color, #212529);
  --label-color: var(--tg-theme-hint-color, #495057);
  }

  /* Sembunyikan sidebar dan header saat diakses dari Telegram Mini App */
  body.telegram-app .sidebar,
  body.telegram-app .header,
  body.telegram-app .sidebar-overlay,
  body.telegram-app .sidebar-toggle {
  display: none !important;
  }

  body.telegram-app .main-content {
  margin-left: 0 !important;
  }

  body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--body-bg);
  color: var(--text-color);
  transition: background-color var(--transition-speed), color var(--transition-speed);
  overflow-x: hidden;
  min-height: 100vh;
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

  .sidebar-nav a:hover,
  .sidebar-nav a.active {
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

  /* Header Styling - menggunakan gradien */
  .header {
  height: var(--header-height);
  background: var(--header-bg);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 1030;
  /* Hilangkan blur, biarkan solid */
  }

  body[data-bs-theme="dark"] .header {
  /* header sudah menggunakan gradien gelap dari variabel */
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

  /* Tombol di dalam header harus kontras */
  .header .btn, .header .profile-btn, .header .theme-btn {
  color: white; /* teks putih di atas gradien */
  border-color: rgba(255,255,255,0.3);
  }
  .header .btn:hover, .header .profile-btn:hover, .header .theme-btn:hover {
  background-color: rgba(255,255,255,0.2);
  border-color: white;
  color: white;
  }

  /* Card Styling */
  .card {
  background: var(--card-bg);
  border: var(--card-border);
  border-radius: 1rem;
  box-shadow: var(--card-shadow);
  transition: transform 0.2s, box-shadow 0.2s;
  height: 100%;
  }

  .card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
  }

  .card-header {
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
  color: white;
  border-bottom: none;
  font-weight: 600;
  font-size: 1.25rem;
  padding: 1rem 1.5rem;
  border-radius: 1rem 1rem 0 0;
  box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.1);
  }

  .card-header i {
  color: white;
  margin-right: 8px;
  }

  .card-body {
  padding: 1.5rem;
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

  /* Theme Toggle Button (di header) */
  .theme-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid currentColor;
  background-color: transparent;
  transition: all 0.3s ease;
  }

  body.telegram-app .theme-btn {
  display: none;
  }

  /* Profile Button */
  .profile-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid currentColor;
  background-color: transparent;
  transition: all 0.3s ease;
  padding: 0;
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

  /* Form Elements */
  .form-label {
  font-weight: 500;
  color: var(--label-color);
  }

  .form-control, .input-group-text {
  background-color: var(--input-bg);
  border-color: var(--input-border);
  color: var(--input-color);
  }

  .form-control:focus {
  background-color: var(--input-bg);
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
  color: var(--input-color);
  }

  .input-group .btn-outline-secondary {
  border-color: var(--input-border);
  background-color: var(--input-bg);
  color: var(--text-muted);
  }

  .input-group .btn-outline-secondary:hover {
  background-color: var(--hover-bg);
  color: var(--primary-color);
  }

  .input-group-text {
  border-right: none;
  color: var(--text-muted);
  }

  .input-group .form-control {
  border-left: none;
  }

  .input-group .form-control:focus {
  box-shadow: none;
  border-color: var(--primary-color);
  }

  .btn-primary {
  background-color: #0d6efd;
  border: none;
  padding: 0.6rem 1.2rem;
  font-weight: 500;
  border-radius: 25px;
  color: white;
  }
  .btn-primary:hover {
  background-color: #0b5ed7;
  }

  .btn-link {
  color: var(--text-muted);
  text-decoration: none;
  }
  .btn-link:hover {
  color: var(--primary-color);
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
  background: transparent;
  border: 1px solid currentColor;
  color: inherit;
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