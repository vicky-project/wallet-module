@extends('wallet::layouts.app')

@section('title', $account->name . ' - Detail Akun')

@use('Modules\Wallet\Helpers\Helper')

@push('styles')
<style>
    .account-header-card {
        border-left: 4px solid {{ $account->color }};
        background: linear-gradient(135deg, {{ $account->color }}20, transparent);
    }
    
    .account-detail-icon {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin: 0 auto;
    }
    
    .balance-badge {
        font-size: 1.1rem;
        padding: 8px 16px;
        border-radius: 12px;
    }
    
    .detail-card {
        height: 100%;
        transition: transform 0.2s;
    }
    
    .detail-card:hover {
        transform: translateY(-5px);
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 12px 20px;
    }
    
    .info-label {
        font-weight: 600;
        color: #6c757d;
    }
    
    .info-value {
        font-weight: 500;
    }
    
    .action-buttons {
        position: sticky;
        bottom: 20px;
        z-index: 100;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }
    
    body[data-bs-theme="dark"] .action-buttons {
        background: rgba(33, 37, 41, 0.95);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
            gap: 8px;
        }
        
        .action-buttons {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 12px 12px 0 0;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<!-- Account Header -->
<div class="row mb-4">
  <div class="col">
    <div class="card account-header-card">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="d-flex align-items-center">
              <div class="account-detail-icon me-4" style="background-color: {{ $account->color }}20; color: {{ $account->color }}">
                <i class="{{ $account->icon }}"></i>
              </div>
              <div>
                <h1 class="mb-2">{{ $account->name }}</h1>
                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                  <span class="badge bg-secondary">{{ $account->type->label() }}</span>
                  @if($account->is_default)
                    <span class="badge bg-warning">
                      <i class="bi bi-star-fill me-1"></i> Default
                    </span>
                  @endif
                  @if($account->is_active)
                    <span class="badge bg-success">
                      <i class="bi bi-check-circle me-1"></i> Aktif
                    </span>
                  @else
                    <span class="badge bg-danger">
                      <i class="bi bi-x-circle me-1"></i> Nonaktif
                    </span>
                  @endif
                  <span class="badge bg-info">{{ $account->currency }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="balance-badge d-inline-block border p-3 rounded-3">
              <div class="text-muted small">Saldo Saat Ini</div>
              <div class="h3 mb-0 currency">{{ $account->balance->getAmount()->toInt() }}</div>
              <div class="small text-muted mt-1">
                <i class="bi bi-calendar me-1"></i>Per {{ now()->translatedFormat('d F Y') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
  <div class="col-md-6 mb-3">
    <div class="card detail-card border-start border-success border-4">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="me-3">
            <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
              <i class="bi bi-arrow-down-circle fs-4"></i>
            </div>
          </div>
          <div>
            <h6 class="text-muted mb-1">Pemasukan</h6>
            <h4 class="mb-0 currency">{{ Helper::toMoney($account->getIncomeForPeriod(now()->startOfMonth(), now()->endOfMonth()), $account->currency)->getAmount()->toInt() }}</h4>
            <small class="text-muted">Bulan ini</small>
          </div>
        </div>
      </div>
    </div>
  </div>
        
  <div class="col-md-6 mb-3">
    <div class="card detail-card border-start border-danger border-4">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="me-3">
            <div class="rounded-circle bg-danger bg-opacity-10 p-3 text-danger">
              <i class="bi bi-arrow-up-circle fs-4"></i>
            </div>
          </div>
          <div>
            <h6 class="text-muted mb-1">Pengeluaran</h6>
            <h4 class="mb-0 currency">{{ Helper::toMoney($account->getExpenseForPeriod(now()->startOfMonth(), now()->endOfMonth()), $account->currency) }}</h4>
            <small class="text-muted">Bulan ini</small>
          </div>
        </div>
      </div>
    </div>
  </div>
        
  <div class="col-md-6 mb-3">
    <div class="card detail-card border-start border-info border-4">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="me-3">
            <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info">
              <i class="bi bi-arrow-left-right fs-4"></i>
            </div>
          </div>
          <div>
            <h6 class="text-muted mb-1">Aliran Bersih</h6>
            @php
              $income = $account->getIncomeForPeriod(now()->startOfMonth(), now()->endOfMonth());
              $expense = $account->getExpenseForPeriod(now()->startOfMonth(), now()->endOfMonth());
              $netFlow = Helper::toMoney($income - $expense, $account->currency);
            @endphp
            <h4 class="mb-0 currency {{ $netFlow->getAmount()->toInt() >= 0 ? 'text-success' : 'text-danger' }}">
              {{ $netFlow->getAmount()->toInt() }}
            </h4>
            <small class="text-muted">Bulan ini</small>
          </div>
        </div>
      </div>
    </div>
  </div>
        
  <div class="col-md-6 mb-3">
    <div class="card detail-card border-start border-primary border-4">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="me-3">
            <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
              <i class="bi bi-clock-history fs-4"></i>
            </div>
          </div>
          <div>
            <h6 class="text-muted mb-1">Total Transaksi</h6>
            <h4 class="mb-0">{{ $account->transactions_count ?? 0 }}</h4>
            <small class="text-muted">Semua waktu</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Account Details -->
  <div class="col-lg-8 mb-4">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-info-circle me-2"></i>Detail Informasi
        </h5>
      </div>
      <div class="card-body">
        <div class="info-grid mb-4">
          <div class="info-label">Saldo Awal</div>
          <div class="info-value currency">{{ $account->initial_balance->getAmount()->toInt() }}</div>
                        
          <div class="info-label">Mata Uang</div>
          <div class="info-value">
            <span class="badge bg-info">{{ $account->currency }}</span>
          </div>
                        
          @if($account->account_number)
            <div class="info-label">Nomor Akun</div>
            <div class="info-value">
              <code>{{ $account->account_number }}</code>
            </div>
          @endif
                        
          @if($account->bank_name)
            <div class="info-label">Nama Bank</div>
            <div class="info-value">{{ $account->bank_name }}</div>
          @endif
                        
          <div class="info-label">Status</div>
          <div class="info-value">
            @if($account->is_active)
              <span class="badge bg-success">
                <i class="bi bi-check-circle me-1"></i> Aktif
              </span>
            @else
              <span class="badge bg-danger">
                <i class="bi bi-x-circle me-1"></i> Nonaktif
              </span>
            @endif
          </div>
                        
          <div class="info-label">Default Akun</div>
          <div class="info-value">
            @if($account->is_default)
              <span class="badge bg-warning">
                <i class="bi bi-star-fill me-1"></i> Ya
              </span>
            @else
              <span class="badge bg-secondary">Tidak</span>
            @endif
          </div>
                        
          <div class="info-label">Tanggal Dibuat</div>
          <div class="info-value">
            {{ $account->created_at->translatedFormat('d F Y H:i') }}
          </div>
                        
          <div class="info-label">Terakhir Diubah</div>
          <div class="info-value">
            {{ $account->updated_at->translatedFormat('d F Y H:i') }}
          </div>
        </div>
                    
        @if($account->notes)
          <div class="mb-4">
            <h6 class="text-muted mb-3">Catatan</h6>
            <div class="card bg-light">
              <div class="card-body">
                <p class="mb-0">{{ $account->notes }}</p>
              </div>
            </div>
          </div>
        @endif
                    
        <!-- Quick Actions -->
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a href="{{ route('apps.accounts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
          </a>
          <a href="{{ route('apps.accounts.edit', $account) }}" class="btn btn-outline-warning">
            <i class="bi bi-pencil me-1"></i> Edit
          </a>
          <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transferModal">
            <i class="bi bi-arrow-left-right me-1"></i>Transfer Dana
          </button>
          <form method="POST" action="{{ route('apps.accounts.recalculate', $account) }}">
            @csrf
            <button type="submit" class="btn btn-outline-info" onclick="return confirm('Hitung ulang saldo berdasarkan transaksi?')">
              <i class="bi bi-arrow-clockwise me-1"></i>Hitung Ulang
            </button>
          </form>
          @if(!$account->is_default)
            <a href="{{ route('apps.accounts.set-default', $account) }}" class="btn btn-outline-warning" onclick="return confirm('Set sebagai akun default?')">
              <i class="bi bi-star me-1"></i>Set Default
            </a>
          @endif
        </div>
      </div>
    </div>
  </div>
        
  <!-- Recent Transactions & Quick Stats -->
  <div class="col-lg-4 mb-4">
    <!-- Account Type Info -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="bi bi-tag me-2"></i>Informasi Tipe Akun
        </h6>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="account-icon me-3" style="background-color: {{ $account->color }}20; color: {{ $account->color }}">
            <i class="{{ $account->icon }}"></i>
          </div>
          <div>
            <h5 class="mb-1">{{ $account->type->label() }}</h5>
            <p class="text-muted small mb-0">
              @php
                $typeDescriptions = [
                  'cash' => 'Akun tunai fisik untuk transaksi sehari-hari',
                  'bank' => 'Akun bank tradisional dengan nomor rekening',
                  'ewallet' => 'Dompet digital seperti OVO, GoPay, Dana',
                  'credit_card' => 'Kartu kredit dengan limit tertentu',
                  'investment' => 'Akun investasi seperti saham, reksadana',
                  'savings' => 'Akun tabungan dengan bunga',
                  'loan' => 'Pinjaman atau hutang',
                  'other' => 'Jenis akun lainnya'
                ];
              @endphp
              {{ $typeDescriptions[$account->type->value] ?? 'Tidak ada deskripsi' }}
            </p>
          </div>
        </div>
      </div>
    </div>
            
    <!-- Account Metadata -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="bi bi-gear me-2"></i>Metadata Akun
        </h6>
      </div>
      <div class="card-body">
        <div class="stats-grid">
          <div class="text-center">
            <div class="rounded-circle bg-light p-3 mb-2">
              <i class="bi bi-palette text-primary fs-4"></i>
            </div>
            <div class="small text-muted">Warna</div>
            <div class="fw-semibold">
              <input type="color" value="{{ $account->color }}" disabled style="width: 30px; height: 30px; border: none; background: none;">
            </div>
          </div>
                        
          <div class="text-center">
            <div class="rounded-circle bg-light p-3 mb-2">
              <i class="bi bi-image text-success fs-4"></i>
            </div>
            <div class="small text-muted">Ikon</div>
            <div class="fw-semibold">
              <i class="{{ $account->icon }} fs-5"></i>
            </div>
          </div>
                        
          <div class="text-center">
            <div class="rounded-circle bg-light p-3 mb-2">
              <i class="bi bi-clock text-warning fs-4"></i>
            </div>
            <div class="small text-muted">Usia Akun</div>
            <div class="fw-semibold">
              {{ $account->created_at->diffForHumans() }}
            </div>
          </div>
                        
          <div class="text-center">
            <div class="rounded-circle bg-light p-3 mb-2">
              <i class="bi bi-activity text-danger fs-4"></i>
            </div>
            <div class="small text-muted">Status</div>
            <div class="fw-semibold">
              @if($account->is_active)
                <span class="badge bg-success">Aktif</span>
              @else
                <span class="badge bg-danger">Nonaktif</span>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format currency
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
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Transfer modal form handling
        const transferForm = document.getElementById('transferForm');
        if (transferForm) {
            transferForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';
                submitBtn.disabled = true;
            });
        }
    });
</script>
@endpush