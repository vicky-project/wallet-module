@extends('core::layouts.app')

@section('title', 'Keuangan')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Total Saldo -->
    <div class="text-center my-4">
      <small class="text-uppercase" style="color: var(--tg-theme-hint-color);">Total Saldo</small>
      <h1 class="display-1 fw-bold" style="color: var(--tg-theme-text-color);">Rp {{ number_format($dashboardData['total_balance'], 0, ',', '.') }}</h1>
    </div>

    <!-- Widget Stats -->
    <div class="row g-3 mb-4">
      <!-- Akun Widget -->
      <div class="col-md-4">
        <a href="{{ route('apps.accounts.index') }}" class="text-decoration-none">
          <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
            <div class="card-body p-3">
              <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle p-2 me-2" style="background-color: rgba(64, 167, 227, 0.1); color: #40a7e3;">
                  <i class="bi bi-wallet2"></i>
                </div>
                <h6 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">Akun</h6>
              </div>
              <p class="fw-bold mb-1" style="color: var(--tg-theme-text-color); font-size: 1.2rem;">{{ $dashboardData['account_stats']['total'] }}</p>
              @if($dashboardData['account_stats']['active'])
                <small style="color: var(--tg-theme-hint-color);">Active: {{ $dashboardData['account_stats']['active'] }}</small>
              @else
                <small style="color: var(--tg-theme-hint-color);">Belum ada akun aktif</small>
              @endif
            </div>
          </div>
        </a>
      </div>

      <!-- Kategori Widget -->
      <div class="col-md-4">
        <a href="{{ route('apps.categories.index') }}" class="text-decoration-none">
          <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
            <div class="card-body p-3">
              <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle p-2 me-2" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
                  <i class="bi bi-tags"></i>
                </div>
                <h6 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">Kategori</h6>
              </div>
              <p class="fw-bold mb-1" style="color: var(--tg-theme-text-color); font-size: 1.2rem;">{{ $dashboardData['category_stats']['total'] }}</p>
              <small style="color: var(--tg-theme-hint-color);">{{ $dashboardData['category_stats']['expense'] }} pengeluaran, {{ $dashboardData['category_stats']['income'] }} pemasukan</small>
            </div>
          </div>
        </a>
      </div>

      <!-- Anggaran Widget -->
      <div class="col-md-4">
        <a href="{{ route('apps.budgets.index') }}" class="text-decoration-none">
          <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
            <div class="card-body p-3">
              <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle p-2 me-2" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
                  <i class="bi bi-pie-chart"></i>
                </div>
                <h6 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">Anggaran</h6>
              </div>
              <p class="fw-bold mb-1" style="color: var(--tg-theme-text-color); font-size: 1.2rem;">{{ $dashboardData['budget_stats']['total'] }}</p>
              @if($dashboardData['budget_stats']['total'] > 0)
                @php $percentage = $budgetStats['total_amount'] > 0 ? round(($dashboardData['budget_stats']['total_spent'] / $dashboardData['budget_stats']['total_amount']) * 100) : 0; @endphp
                <small style="color: var(--tg-theme-hint-color);">Terpakai {{ $percentage }}%</small>
              @else
                <small style="color: var(--tg-theme-hint-color);">Belum ada anggaran</small>
              @endif
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- Transaksi Terbaru -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">Transaksi Terbaru</h5>
          <a href="{{ route('apps.transactions.index') }}" class="small" style="color: var(--tg-theme-button-color);">Lihat semua</a>
        </div>
        @forelse($dashboardData['recent_transactions'] as $transaction)
          <div class="card border-0 shadow-sm mb-2" style="background-color: var(--tg-theme-secondary-bg-color);">
            <div class="card-body p-3">
              <div class="d-flex align-items-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #6c757d20; color: #6c757d;">
                  <i class="bi {{ $transaction['category_icon'] ?? 'bi-tag' }}"></i>
                </div>
                <div class="flex-grow-1" style="min-width: 0;">
                  <div class="row g-0">
                    <div class="col-8 col-sm-9 col-md-10">
                      <h6 class="mb-0 text-truncate" style="color: var(--tg-theme-text-color);">{{ $transaction['description'] }}</h6>
                      <small class="text-truncate d-block" style="color: var(--tg-theme-hint-color);">{{ $transaction['account_name'] }} • {{ \Carbon\Carbon::parse($transaction['transaction_date'])->format('d M H:i') }}</small>
                    </div>
                    <div class="col-4 col-sm-3 col-md-2 text-end">
                      <span class="fw-bold {{ $transaction['type'] == 'income' ? 'text-success' : 'text-danger' }}">
                        {{ $transaction['type'] == 'income' ? '+' : '-' }} Rp {{ number_format($transaction['amount'], 0, ',', '.') }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="text-center py-4" style="color: var(--tg-theme-hint-color);">
            <i class="bi bi-receipt display-6"></i>
            <p class="mt-2">Belum ada transaksi.</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>

<!-- Container untuk FAB dan menu -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1000;">
  <!-- Menu FAB -->
  <div class="d-flex flex-column align-items-end gap-2 mb-2" id="fabMenu" style="display: none;">
    <a href="{{ route('apps.transactions.create') }}" class="btn rounded-pill shadow-sm" style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none; padding: 10px 20px;">
      <i class="bi bi-plus-circle me-2"></i>Transaksi Baru
    </a>
    <a href="{{ route('apps.uploads') }}" class="btn rounded-pill shadow-sm" style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none; padding: 10px 20px;">
      <i class="bi bi-upload me-2"></i>Upload File
    </a>
    <a href="{{ route('apps.reports') }}" class="btn rounded-pill shadow-sm" style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none; padding: 10px 20px;">
      <i class="bi bi-bar-chart me-2"></i>Laporan
    </a>
  </div>
  <!-- Tombol FAB utama -->
  <button class="btn rounded-circle shadow-lg" style="width: 56px; height: 56px; background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none;" onclick="toggleFabMenu()">
    <i class="bi bi-plus-lg fs-4" id="fabIcon"></i>
  </button>
</div>
@endsection

@push('styles')
<style>
    .content {
        padding-bottom: 80px;
    }
    .card {
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .text-truncate {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }
    
    @media (min-width: 768px) {
      .text-truncate {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
      }
    }
</style>
@endpush

@push('scripts')
<script>
  function toggleFabMenu() {
    const menu = document.getElementById('fabMenu');
    const icon = document.getElementById('fabIcon');
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'flex';
        icon.className = 'bi bi-x-lg fs-4';
    } else {
        menu.style.display = 'none';
        icon.className = 'bi bi-plus-lg fs-4';
    }
  }

// Klik di luar untuk menutup menu
document.addEventListener('click', function(event) {
    const fabContainer = document.querySelector('.position-fixed.bottom-0.end-0.p-3');
    if (fabContainer && !fabContainer.contains(event.target)) {
        document.getElementById('fabMenu').style.display = 'none';
        document.getElementById('fabIcon').className = 'bi bi-plus-lg fs-4';
    }
});
</script>
@endpush