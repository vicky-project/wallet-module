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
                <div class="form-check mb-3">
                  <input type="checkbox" class="form-check-input" id="notifications" @checked(auth()->user()->telegram_notifications)>
                  <label class="form-check-label font-weight-bold" for="notifications">Aktifkan Semua Notifikasi</label>
                  <small class="form-text text-muted d-block">Jika di nonaktifkan, semua notifikasi akan dimatikan</small>
                </div>
                
                <!-- Transaction -->
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">💰 Transaksi</h6>
                  </div>
                  <div class="card-body">
                    <div class="form-check mb-2">
                      <input type="checkbox" class="form-check-input setting-toggle" id="new_transaction" data-setting="new_transaction" @checked($settings['new_transaction'] ?? true)>
                      <label class="form-check-label" for="new_transaction">
                        Notifikasi Transaksi Baru
                      </label>
                      <small class="form-text text-muted d-block">
                        Dapatkan notifikasi setiap menambah transaksi
                      </small>
                    </div>
                  </div>
                </div>

                <!-- Daily/Weekly Reports -->
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">📊 Laporan</h6>
                  </div>
                  <div class="card-body">
                    <div class="form-check mb-2">
                      <input type="checkbox" class="form-check-input setting-toggle" id="daily_summary" data-setting="daily_summary" @checked($settings['daily_summary'] ?? false)>
                      <label class="form-check-label" for="daily_summary">
                        Laporan Harian
                      </label>
                      <small class="form-text text-muted d-block">
                        Dikirim setiap jam 20:00
                      </small>
                    </div>
                
                    <div class="form-check mb-2">
                      <input type="checkbox" class="form-check-input setting-toggle" id="weekly_summary" data-setting="weekly_summary" @checked($settings['weekly_summary'] ?? true)>
                      <label class="form-check-label" for="weekly_summary">
                        Laporan Mingguan
                      </label>
                      <small class="form-text text-muted d-block">
                        Dikirim setiap Minggu jam 19:00
                      </small>
                    </div>
                  </div>
                </div>
                
                <!-- Budget Alerts -->
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">⚠️ Peringatan Budget</h6>
                  </div>
                  <div class="card-body">
                    <div class="form-check mb-2">
                      <input type="checkbox" class="form-check-input setting-toggle" id="budget_warning" data-setting="budget_warning" @checked($settings['budget_warning'] ?? true)>
                      <label class="form-check-label" for="budget_warning">
                        Peringatan Budget (80-100%)
                      </label>
                      <small class="form-text text-muted d-block">
                        Dikirim saat budget hampir habis
                      </small>
                    </div>
                
                    <div class="form-check mb-2">
                      <input type="checkbox" class="form-check-input setting-toggle" id="budget_exceeded" data-setting="budget_exceeded" @checked($settings['budget_exceeded'] ?? true)>
                      <label class="form-check-label" for="budget_exceeded">
                        Budget Terlampaui
                      </label>
                      <small class="form-text text-muted d-block">
                        Dikirim saat budget sudah terlampaui
                      </small>
                    </div>
                  </div>
                </div>

                <!-- Account Alerts -->
                <div class="card mb-3">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">🏦 Peringatan Akun</h6>
                  </div>
                  <div class="card-body">
                    <div class="form-check mb-2">
                      <input type="checkbox" class="form-check-input setting-toggle" id="low_balance" data-setting="low_balance" @checked($settings['low_balance'] ?? true)>
                      <label class="form-check-label" for="low_balance">
                        Saldo Rendah
                      </label>
                      <small class="form-text text-muted d-block">
                        Dikirim saat saldo di bawah Rp 100.000
                      </small>
                    </div>
                  </div>
                </div>

                <div>
                  <button type="button" onclick="saveSettings()" class="btn btn-sm btn-success mt-2">
                    💾 Simpan Pengaturan
                  </button>
                </div>
                <div id="settings-status" class="mt-3"></div>
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