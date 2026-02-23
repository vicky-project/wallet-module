@extends('core::layouts.app')

@section('title', 'Keuangan')

@section('content')
<div class="row">
  <div class="col-12">
        <!-- Ringkasan Saldo (di tengah) -->
        <div class="text-center mt-2 mb-4">
          <small class="text-uppercase" style="letter-spacing: 1px; color: var(--tg-theme-subtitle-text-color);">Total Saldo</small>
          <h1 class="display-1 fw-bold currency" style="color: var(--tg-theme-text-color);">Rp {{ $dashboardData['total_balance'] }}</h1>
        </div>

        <!-- Daftar Akun (maksimal 5) -->
        <div class="section-card mb-4" style="background-color: var(--tg-theme-section-bg-color);border-radius: 12px;padding: 16px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold mb-0" style="color: var(--tg-theme-section-header-text-color);">Akun</h5>
              @if($dashboardData['accounts']->count() > 5)
                <a href="{{ route('apps.accounts.index') }}" class="small" style="color: var(--tg-theme-link-color);" onclick="showToast('Lihat semua akun', 'info')">Lihat semua</a>
              @endif
            </div>
            @if($dashboardData['accounts']->count() > 0)
              <div class="row g-3">
                @foreach($dashboardData['accounts']->take(5) as $account)
                <div class="col-6 col-md-4 col-lg-3">
                  <div class="card border-0 h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: {{ $account->color }}20; color: {{ $account->color }};">
                          <i class="bi {{ $account->icon }}"></i>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="mb-0 text-truncate" style="color: var(--tg-theme-text-color);">{{ $account->name }}</h6>
                          @if($account->is_default)
                            <span class="badge bg-info" style="font-size: 0.6rem;">Utama</span>
                          @endif
                        </div>
                      </div>
                      <p class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">
                        Rp {{ number_format($account->balance, 0, ',', '.') }}
                      </p>
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
            @else
              <div class="text-center py-4">
                <i class="bi bi-wallet2 display-6" style="color: var(--tg-theme-hint-color);"></i>
                <p class="mt-2" style="color: var(--tg-theme-hint-color);">Belum ada akun. Tambahkan akun baru.</p>
                <a href="{{ route('apps.accounts.create') }}" class="btn btn-sm mt-2" style="background-color: var(--tg-theme-button-color);color: var(--tg-theme-button-text-color);border: none;">
                  <i class="bi bi-plus-circle me-1"></i> Tambah akun
                </a>
              </div>
            @endif
        </div>

        <!-- Transaksi Terbaru -->
        <div class="section-card mb-4" style="background-color: var(--tg-theme-section-bg-color);border-radius: 12px;padding: 16px;">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">Transaksi Terbaru</h5>
            @if(count($dashboardData['recent_transactions']) > 0)
              <a href="#" class="small" style="color: var(--tg-theme-link-color);" onclick="showToast('Lihat semua transaksi', 'info')">Lihat semua</a>
            @endif
          </div>
          @forelse($dashboardData['recent_transactions'] as $transaction)
            <div class="card border-0 mb-2" style="background-color: var(--tg-theme-secondary-bg-color);">
              <div class="card-body p-3">
                <div class="d-flex align-items-center">
                  <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: {{ $transaction->category?->color ?? '#6c757d' }}20; color: {{ $transaction->category?->color ?? '#6c757d' }};">
                    <i class="bi {{ $transaction->category?->icon ?? 'bi-tag' }}"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                      <div>
                        <h6 class="mb-0" style="color: var(--tg-theme-text-color);">{{ $transaction->description }}</h6>
                        <small style="color: var(--tg-theme-subtitle-text-color);">{{ $transaction->account->name }} • {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M H:i') }}</small>
                      </div>
                      <div class="text-end">
                        <span class="fw-bold" style="color: {{ $transaction->type == 'income' ? 'var(--tg-theme-accent-text-color)' : 'var(--tg-theme-destructive-text-color)' }};">
                          {{ $transaction->type == 'income' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center py-4">
              <i class="bi bi-receipt display-6" style="color: var(--tg-theme-hint-color);"></i>
              <p class="mt-2" style="color: var(--tg-theme-hint-color);">Belum ada transaksi.</p>
              <a href="{{ route('apps.transactions.create') }}" class="btn btn-sm mt-2" style="background-color: var(--tg-theme-button-color);color: var(--tg-theme-button-text-color);border: none;" role="button">
                <i class="bi bi-plus-circle me-1"></i> Tambah Transaksi
              </a>
            </div>
          @endforelse
        </div>
  </div>
</div>

<!-- Multi Action Floating Button -->
<div class="fab-container position-fixed" style="bottom: 20px;right: 20px;z-index: 1000;">
  <div class="fab-options" id="fabOptions" style="display: none; margin-bottom: 10px;">
    <div class="d-flex flex-column align-items-end gap-2">
      <button class="btn rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none;" onclick="window.location.href='{{ route('apps.transactions.create') }}'" title="Tambah Transaksi">
        <i class="bi bi-plus-lg"></i>
      </button>
      <button class="btn rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none;" onclick="window.location.href='{{ route('apps.upload') }}'" title="Upload Transaksi">
        <i class="bi bi-upload"></i>
      </button>
      <button class="btn rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none;" onclick="window.location.href='{{ route('apps.reports') }}'" title="Laporan Transaksi">
        <i class="bi bi-file-earmark-bar-graph"></i>
      </button>
    </div>
  </div>
  <button class="btn rounded-circle shadow-lg d-flex align-items-center justify-content-center" id="fabMain" style="width: 56px;height: 56px;background-color: var(--tg-theme-button-color);color: var(--tg-theme-button-text-color);border: none;">
    <i class="bi bi-plus-lg fs-4" id="fabIcon"></i>
  </button>
</div>
@endsection

@push('styles')
<style>
    /* Tambahkan padding-bottom agar konten tidak tertutup FAB */
    .content {
        padding-bottom: 100px;
    }
    .section-card {
      transition: background-color 0.2s;
    }
    .fab-container {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
    }
    .fab-options {
      transition: all 0.2s ease;
    }
    .fab-options .btn {
      animation: slideIn 0.2s ease;
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
</style>
@endpush

@push('scripts')
<script>
  // Pastikan showToast tersedia (fallback jika belum)
  if (typeof showToast !== 'function') {
    window.showToast = function(message, type) {
      alert(message);
    };
  }
    
  document.addEventListener('DOMContentLoaded', function() {
      // Format semua currency
    document.querySelectorAll('.currency').forEach(element => {
        const value = element.textContent;
        if (!isNaN(value)) {
            element.textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        }
    });
    
    const fabMain = document.getElementById('fabMain');
    const fabOptions = document.getElementById('fabOptions');
    const fabIcon = document.getElementById('fabIcon');
    let isOpen = false;
    
    fabMain.addEventListener('click', function() {
      isOpen = !isOpen;
      if(isOpen) {
        fabOptions.style.display = 'block';
        fabIcon.className = 'bi bi-x-lg fs-4';
      } else {
        fabOptions.style.display = 'none';
        fabIcon.className = 'bi bi-plus-lg fs-4';
      }
    }):
    
    document.addEventListener('click', function(event) {
      if(!fabMain.contains(event.target) && !fabOptions.contains(event.target)) {
        isOpen = false;
        fabOptions.style.display = 'none';
        fabIcon.className = 'bi bi-plus-lg fs-4';
      }
    });
  });
</script>
@endpush