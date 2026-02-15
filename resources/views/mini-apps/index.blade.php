<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Mini App</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: var(--tg-theme-bg-color, #fff);
            color: var(--tg-theme-text-color, #000);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .app-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--tg-theme-button-color, #40a7e3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            color: white;
            font-size: 3rem;
        }
        .app-name {
            color: var(--tg-theme-text-color, #000);
        }
        .app-description {
            color: var(--tg-theme-hint-color, #999);
            margin-bottom: 2rem;
            padding: 0 20px;
        }
        .menu-item {
            background-color: var(--tg-theme-secondary-bg-color, #f0f0f0);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .menu-item:hover {
            opacity: 0.8;
        }
        .menu-item i:first-child {
            font-size: 1.5rem;
            margin-right: 15px;
            color: var(--tg-theme-button-color, #40a7e3);
        }
        .menu-item .menu-text {
            flex-grow: 1;
        }
        .menu-item .menu-title {
            font-weight: 600;
            color: var(--tg-theme-text-color, #000);
        }
        .menu-item .menu-subtitle {
            font-size: 0.85rem;
            color: var(--tg-theme-hint-color, #999);
        }
        .menu-item .bi-chevron-right {
            color: var(--tg-theme-hint-color, #999);
        }
        .btn-create {
            background-color: var(--tg-theme-button-color, #40a7e3);
            color: var(--tg-theme-button-text-color, white);
            border: none;
            border-radius: 12px;
            padding: 15px;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .btn-create i {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        .container {
            padding: 20px;
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo Lingkaran -->
        <div class="app-logo">
            <img src="/homeserver.png" alt="Logo Aplikasi" class="img-fluid rounded-circle">
        </div>

        <!-- Nama Aplikasi -->
        <div class="app-name h4 fw-bold text-center">
            Vicky Server App
        </div>

        <!-- Deskripsi -->
        <div class="app-description">
          <small class="text-center">
            Satu aplikasi untuk semua fitur tersedia.
          </small>
        </div>

        <!-- Menu Utama -->
        <div class="mt-4">
            <!-- Menu 1: My Bots (seperti di screenshot) -->
            <div class="menu-item" onclick="handleMenuClick('mybots')">
                <i class="bi bi-robot"></i>
                <div class="menu-text">
                    <div class="menu-title">My Bots</div>
                    <div class="menu-subtitle">2 bot aktif</div>
                </div>
                <i class="bi bi-chevron-right"></i>
            </div>

            <!-- Menu 2: Create New Bot (tombol +) -->
            <div class="menu-item" onclick="handleMenuClick('create')">
                <i class="bi bi-plus-circle"></i>
                <div class="menu-text">
                    <div class="menu-title">Create New Bot</div>
                    <div class="menu-subtitle">Buat bot baru</div>
                </div>
                <i class="bi bi-chevron-right"></i>
            </div>

            <!-- Menu 3: Settings -->
            <div class="menu-item" onclick="handleMenuClick('settings')">
                <i class="bi bi-gear"></i>
                <div class="menu-text">
                    <div class="menu-title">Settings</div>
                    <div class="menu-subtitle">Preferensi aplikasi</div>
                </div>
                <i class="bi bi-chevron-right"></i>
            </div>

            <!-- Menu 4: Help -->
            <div class="menu-item" onclick="handleMenuClick('help')">
                <i class="bi bi-question-circle"></i>
                <div class="menu-text">
                    <div class="menu-title">Help</div>
                    <div class="menu-subtitle">FAQ dan dukungan</div>
                </div>
                <i class="bi bi-chevron-right"></i>
            </div>
        </div>

        <!-- Tombol Create New (alternatif) -->
        <button class="btn-create" onclick="handleMenuClick('create')">
            <i class="bi bi-plus-lg"></i> Create New App
        </button>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        // Inisialisasi Telegram WebApp
        const tg = window.Telegram.WebApp;
        tg.expand(); // Memperluas ke layar penuh

        // Terapkan tema Telegram ke CSS variables
        const theme = tg.themeParams;
        document.body.style.setProperty('--tg-theme-bg-color', theme.bg_color || '#ffffff');
        document.body.style.setProperty('--tg-theme-text-color', theme.text_color || '#000000');
        document.body.style.setProperty('--tg-theme-hint-color', theme.hint_color || '#999999');
        document.body.style.setProperty('--tg-theme-button-color', theme.button_color || '#40a7e3');
        document.body.style.setProperty('--tg-theme-button-text-color', theme.button_text_color || '#ffffff');
        document.body.style.setProperty('--tg-theme-secondary-bg-color', theme.secondary_bg_color || '#f0f0f0');

        // Fungsi untuk menangani klik menu
        function handleMenuClick(menu) {
            // Contoh: tampilkan notifikasi dengan Toast Bootstrap (tanpa alert)
            const toastMessage = `Menu ${menu} diklik. Fitur sedang dikembangkan.`;
            showToast(toastMessage);
            
            // Di sini nanti bisa ditambahkan navigasi ke halaman lain
            // Misalnya dengan memuat konten dinamis atau mengarahkan ke route baru
        }

        // Fungsi untuk menampilkan Toast Bootstrap (feedback)
        function showToast(message, type = 'success') {
            // Cek apakah elemen toast sudah ada, jika belum buat
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
                
                const toastEl = document.createElement('div');
                toastEl.id = 'liveToast';
                toastEl.className = 'toast';
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');
                toastEl.innerHTML = `
                    <div class="toast-header">
                        <strong class="me-auto">Notifikasi</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body"></div>
                `;
                toastContainer.appendChild(toastEl);
            }

            const toastEl = document.getElementById('liveToast');
            const toastBody = toastEl.querySelector('.toast-body');
            toastBody.textContent = message;

            // Warna latar belakang sesuai tipe
            toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');
            if (type === 'success') {
                toastEl.classList.add('bg-success', 'text-white');
            } else {
                toastEl.classList.add('bg-danger', 'text-white');
            }

            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }

        // Tampilkan data user di console untuk debugging (opsional)
        console.log('User:', tg.initDataUnsafe?.user);

        // Beri tahu Telegram bahwa halaman sudah siap
        tg.ready();
    </script>
</body>
</html>