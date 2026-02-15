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
        }
        .menu-item {
            background-color: var(--tg-theme-secondary-bg-color, #f0f0f0);
            border-radius: 16px;
            padding: 20px 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s, opacity 0.2s;
            color: var(--tg-theme-text-color, #000);
            text-decoration: none;
            display: block;
        }
        .menu-item:hover {
            transform: scale(1.02);
            opacity: 0.8;
        }
        .menu-item i {
            font-size: 2.5rem;
            color: var(--tg-theme-button-color, #40a7e3);
            margin-bottom: 10px;
            display: block;
        }
        .menu-item span {
            font-size: 1rem;
            font-weight: 500;
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
        <div class="app-description text-center">
          <small>
            Satu aplikasi untuk semua fitur tersedia.
          </small>
        </div>

        <!-- Menu Utama -->
        <div class="container text-center">
          <div class="col-6 col-md-3">
            <a onclick="handleMenuClick('keuangan');" class="menu-item">
              <i class="bi bi-cash-stack"></i>
              <span>Keuangan</span>
            </a>
          </div>
          <div class="col-6 col-md-3">
            <a onclick="handleMenuClick('pengaturan');" class="menu-item">
                <i class="bi bi-gear"></i>
                <span>Pengaturan</span>
            </a>
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
    <script src="https://telegram.org/js/telegram-web-app.js?59"></script>
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