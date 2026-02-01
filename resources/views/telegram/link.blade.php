@extends('wallet::layouts.app')

@section('title', 'Telegram Integration')

@section('content')
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">📱 Integrasi Telegram</h5>
        </div>

        <div class="card-body">
          <!-- Status Section -->
          <div class="mb-4" id="status-section">
            @if(auth()->user()->hasLinkedTelegram())
              <div class="alert alert-success">
                <h6>✅ Akun Telegram Terhubung</h6>
                <p class="mb-1">Chat ID: {{ auth()->user()->telegram_chat_id }}</p>
                @if(auth()->user()->telegram_username)
                  <p class="mb-1">Username: <span>@</span>{{ auth()->user()->telegram_username }}</p>
                @endif
              </div>
              <button onclick="unlinkAccount()" class="btn btn-outline-danger">
                ✗ Putuskan Koneksi
              </button>
            @else
              <div class="alert alert-warning">
                <h6>⚠️ Belum Terhubung</h6>
                <p>Hubungkan akun Telegram Anda untuk menambah transaksi via bot</p>
              </div>
            @endif
          </div>

          <!-- Generate Code Section -->
          <div class="mb-4">
            <h6>Langkah-langkah:</h6>
            <ol>
              <li>Klik tombol "Generate Kode" di bawah</li>
              <li>Buka Telegram dan cari bot: <strong>{{ $botUsername }}</strong></li>
              <li>Kirim perintah: <code>/link &lt;kode&gt;</code></li>
              <li>Tunggu konfirmasi dari bot</li>
            </ol>

            <button onclick="generateCode()" class="btn btn-primary" id="generate-btn">
              🔑 Generate Kode Verifikasi
            </button>
          </div>

          <!-- Code Display -->
          <div id="code-section" class="d-none">
            <div class="alert alert-info">
              <h6>Kode Verifikasi Anda:</h6>
              <div class="text-center my-3">
                <div id="code-display" class="display-4 font-weight-bold"></div>
                <small class="text-muted">Berlaku sampai: <span id="expiry-time"></span></small>
              </div>
              <code id="instructions" class="mb-0"></code>
            </div>
          </div>

          <!-- Settings Section -->
          @if(auth()->user()->hasLinkedTelegram())
            <div class="mt-4">
              <h6>⚙️ Pengaturan Notifikasi</h6>
              <form id="settings-form">
                <div class="form-check mb-2">
                  <input type="checkbox" class="form-check-input" id="notifications" checked>
                  <label class="form-check-label" for="notifications">Aktifkan Notifikasi</label>
                  <small class="form-text text-muted d-block">Jika di nonaktifkan, semua notifikasi akan dimatikan</small>
                </div>
                <div class="form-check mb-2">
                  <input type="checkbox" class="form-check-input" id="daily_report">
                  <label class="form-check-label" for="daily_report">Laporan Harian</label>
                </div>
                <div class="form-check mb-2">
                  <input type="checkbox" class="form-check-input" id="budget_alerts" checked>
                  <label class="form-check-label" for="budget_alerts">Peringatan Budget</label>
                </div>
                <div class="form-check mb-2">
                  <input type="checkbox" class="form-check-input" id="low_balance_alerts" checked>
                  <label class="form-check-label" for="low_balance_alerts">Peringatan Saldo Rendah</label>
                </div>
                <button type="button" onclick="saveSettings()" class="btn btn-sm btn-success mt-2">
                  💾 Simpan Pengaturan
                </button>
              </form>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function generateCode() {
    const btn = document.getElementById('generate-btn');
    btn.disabled = true;
    btn.innerHTML = '⏳ Membuat kode...';

    fetch('{{ secure_url(config("app.url")) }}/telegram/generate-code', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('code-display').textContent = data.code;
            document.getElementById('expiry-time').textContent = data.expires_at;
            document.getElementById('instructions').textContent = data.instructions;
            document.getElementById('code-section').classList.remove('d-none');
            
            // Auto refresh status after 2 minutes
            setTimeout(checkStatus, 120000);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Coba lagi.', error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '🔑 Generate Kode Verifikasi';
    });
}

function unlinkAccount() {
    if (!confirm('Yakin ingin memutuskan koneksi Telegram?')) return;

    fetch('{{ config("app.url") }}/telegram/unlink', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function saveSettings() {
    const settings = {
        notifications: document.getElementById('notifications').checked,
        daily_report: document.getElementById('daily_report').checked,
        budget_alerts: document.getElementById('budget_alerts').checked,
        low_balance_alerts: document.getElementById('low_balance_alerts').checked
    };

    fetch('{{ config("app.url") }}/telegram/settings', {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    });
}

function checkStatus() {
    fetch('{{ route("telegram.status") }}')
    .then(response => response.json())
    .then(data => {
        if (data.linked) {
            location.reload();
        }
    });
}
</script>
@endpush