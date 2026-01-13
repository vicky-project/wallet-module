{{-- resources/views/wallet/accounts/index.blade.php --}}
@extends('wallet::layouts.app')

@section('title', 'Kelola Akun')

@push('styles')
<style>
    /* Custom styling for accounts page */
    .filter-card {
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .filter-card.collapsed {
        height: 60px;
        overflow: hidden;
    }
    
    .account-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    
    .account-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .account-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .account-type-badge {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 20px;
    }
    
    .balance-display {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .balance-change {
        font-size: 0.875rem;
    }
    
    .empty-state {
        padding: 4rem 1rem;
        text-align: center;
        color: #6c757d;
    }
    
    .empty-state-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    /* Dark theme adjustments */
    body[data-bs-theme="dark"] .empty-state {
        color: #adb5bd;
    }
    
    body[data-bs-theme="dark"] .account-card {
        border-left-color: #495057 !important;
    }
    
    /* Status indicators */
    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    
    .status-active {
        background-color: #28a745;
        box-shadow: 0 0 8px rgba(40, 167, 69, 0.5);
    }
    
    .status-inactive {
        background-color: #dc3545;
        box-shadow: 0 0 8px rgba(220, 53, 69, 0.5);
    }
    
    /* Action buttons */
    .action-buttons {
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .account-card:hover .action-buttons {
        opacity: 1;
    }
    
    /* Custom badge colors for account types */
    .badge-cash {
        background-color: #20c997;
        color: white;
    }
    
    .badge-bank {
        background-color: #0d6efd;
        color: white;
    }
    
    .badge-ewallet {
        background-color: #6f42c1;
        color: white;
    }
    
    .badge-credit-card {
        background-color: #fd7e14;
        color: white;
    }
    
    .badge-investment {
        background-color: #17a2b8;
        color: white;
    }
    
    .badge-savings {
        background-color: #198754;
        color: white;
    }
    
    /* Responsive table */
    @media (max-width: 768px) {
        .account-card .row > div {
            margin-bottom: 1rem;
        }
        
        .action-buttons {
            opacity: 1;
            margin-top: 1rem;
            justify-content: center !important;
        }
        
        .balance-display {
            font-size: 1.1rem;
        }
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="row mb-4">
  <div class="col">
    <h2 class="page-title mb-2">
      <i class="bi bi-wallet2 me-2"></i>Kelola Akun
    </h2>
    <p class="text-muted mb-0">Kelola semua akun keuangan Anda di satu tempat. Pantau saldo, transaksi, dan kinerja akun.</p>
  </div>
  <div class="col-auto">
    <a href="{{ route('apps.accounts.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i>
    </a>
  </div>
</div>

<!-- Filter Section -->
<div class="card mb-4 filter-card" id="filterCard">
  <div class="card-header cursor-pointer d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
    <h5 class="mb-0">
      <i class="bi bi-funnel me-2"></i>Filter Pencarian
    </h5>
    <i class="bi bi-chevron-down transition-rotate"></i>
  </div>

  <div class="collapse" id="filterCollapse">
    <div class="card-body">
      <form action="{{ route('apps.accounts.index') }}" method="GET" id="filterForm">
        <div class="row g-3">
          <!-- Type Filter -->
          <div class="col-md-4">
            <label for="type" class="form-label">Tipe Akun</label>
            <select class="form-select" id="type" name="type">
              <option value="">Semua Tipe</option>
              @foreach(\Modules\Wallet\Enums\AccountType::cases() as $type)
                <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>
                  {{ $type->label() }}
                </option>
              @endforeach
            </select>
          </div>

          <!-- Status Filter -->
          <div class="col-md-4">
            <label for="is_active" class="form-label">Status</label>
            <select class="form-select" id="is_active" name="is_active">
              <option value="">Semua Status</option>
              <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
              <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
          </div>

          <!-- Search -->
          <div class="col-md-4">
            <label for="search" class="form-label">Cari</label>
            <div class="input-group">
              <input type="text" class="form-control" id="search" name="search" placeholder="Cari nama atau nomor akun..." value="{{ request('search') }}">
              <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-12">
            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary me-2">
                <i class="bi bi-search me-1"></i>Terapkan Filter
              </button>
              <a href="{{ route('apps.accounts.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>Reset
              </a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-start border-primary border-4 h-100">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col">
            <h6 class="text-muted fw-semibold mb-2">Total Akun</h6>
            <h2 class="mb-0">{{ $stats['total_accounts'] ?? 0 }}</h2>
          </div>
          <div class="col-auto">
            <div class="account-icon bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-muted">
            <i class="bi bi-check-circle me-1"></i>{{ $summary['active'] ?? 0 }} aktif
          </small>
        </div>
      </div>
    </div>
  </div>
        
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-start border-success border-4 h-100">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col">
            <h6 class="text-muted fw-semibold mb-2">Total Saldo</h6>
            <h3 class="mb-0 currency">{{ $stats['total_balance'] ?? 0 }}</h3>
          </div>
          <div class="col-auto">
            <div class="account-icon bg-success bg-opacity-10 text-success">
              <i class="bi bi-cash-stack"></i>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-success">
            <i class="bi bi-arrow-up-right me-1"></i>Semua mata uang
          </small>
        </div>
      </div>
    </div>
  </div>
        
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-start border-info border-4 h-100">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col">
            <h6 class="text-muted fw-semibold mb-2">Total Aset</h6>
            <h3 class="mb-0 currency">{{ $stats['asset_balance'] ?? 0 }}</h3>
          </div>
          <div class="col-auto">
            <div class="account-icon bg-info bg-opacity-10 text-info">
              <i class="bi bi-graph-up"></i>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-info">
            <i class="bi bi-building me-1"></i>{{ $summary['asset_accounts'] ?? 0 }} akun
          </small>
        </div>
      </div>
    </div>
  </div>
        
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-start border-warning border-4 h-100">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col">
            <h6 class="text-muted fw-semibold mb-2">Total Liabilitas</h6>
            <h3 class="mb-0 currency">{{ abs($summary['liability_balance'] ?? 0) }}</h3>
          </div>
          <div class="col-auto">
            <div class="account-icon bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-credit-card"></i>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-warning">
            <i class="bi bi-calculator me-1"></i>Kartu kredit
          </small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Accounts List -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="bi bi-list-ul me-2"></i>Daftar Akun
      <span class="badge bg-secondary ms-2">{{ $accounts->count() }}</span>
    </h5>
    <div class="d-flex align-items-center">
      <div class="dropdown me-2">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
          <i class="bi bi-download me-1"></i>Ekspor
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
          <li><a class="dropdown-item" href="#"><i class="bi bi-filetype-pdf me-2"></i>PDF</a></li>
          <li><a class="dropdown-item" href="#"><i class="bi bi-filetype-xlsx me-2"></i>Excel</a></li>
        </ul>
      </div>
      <button class="btn btn-outline-secondary btn-sm" id="refreshAccounts">
        <i class="bi bi-arrow-clockwise"></i>
      </button>
    </div>
  </div>
        
  <div class="card-body">
    @if($accounts->isEmpty())
      <!-- Empty State -->
      <div class="empty-state">
        <div class="empty-state-icon">
          <i class="bi bi-wallet2"></i>
        </div>
        <h5 class="mb-3">Belum ada akun</h5>
        <p class="text-muted mb-4">Mulai dengan menambahkan akun keuangan pertama Anda untuk melacak pengeluaran dan pemasukan.</p>
        <a href="{{ route('apps.accounts.create') }}" class="btn btn-primary">
          <i class="bi bi-plus-circle me-2"></i>Tambah Akun Baru
        </a>
      </div>
    @else
      <!-- Accounts List -->
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th width="300">Nama Akun</th>
              <th>Tipe</th>
              <th>Saldo</th>
              <th>Status</th>
              <th>Default</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($accounts as $account)
              <tr class="cursor-pointer" onclick="window.location.href='{{ route('apps.accounts.show', $account) }}'">
                <td>
                  <div class="d-flex align-items-center">
                    <div class="account-icon me-3" style="background-color: {{ $account->color }}20; color: {{ $account->color }}">
                      <i class="{{ $account->icon }}"></i>
                    </div>
                    <div>
                      <strong class="d-block">{{ $account->name }}</strong>
                      @if($account->account_number)
                        <small class="text-muted">{{ $account->account_number }}</small>
                      @endif
                      @if($account->bank_name)
                        <small class="text-muted d-block">{{ $account->bank_name }}</small>
                      @endif
                    </div>
                  </div>
                </td>
                <td>
                  @php
                    $badgeClass = 'badge-cash';
                    switch($account->type->value) {
                      case 'cash': $badgeClass = 'badge-cash'; break;
                      case 'bank': $badgeClass = 'badge-bank'; break;
                      case 'ewallet': $badgeClass = 'badge-ewallet'; break;
                      case 'credit_card': $badgeClass = 'badge-credit-card'; break;
                      case 'investment': $badgeClass = 'badge-investment'; break;
                      case 'savings': $badgeClass = 'badge-savings'; break;
                    }
                  @endphp
                  <span class="badge {{ $badgeClass }} account-type-badge">
                    {{ $account->type->label() }}
                  </span>
                </td>
                <td>
                  <div class="balance-display currency">{{ $account->balance->getAmount()->toInt() }}</div>
                  <div class="balance-change text-muted">
                    <small>
                      Mata uang: {{ $account->currency }}
                    </small>
                  </div>
                </td>
                <td>
                  @if($account->is_active)
                    <span class="d-flex align-items-center">
                      <span class="status-indicator status-active"></span>
                      <span class="text-success">Aktif</span>
                    </span>
                  @else
                    <span class="d-flex align-items-center">
                      <span class="status-indicator status-inactive"></span>
                      <span class="text-danger">Nonaktif</span>
                    </span>
                  @endif
                </td>
                <td>
                  @if($account->is_default)
                    <span class="badge bg-primary">Default</span>
                  @else
                    <button class="btn btn-outline-primary btn-sm set-default" data-id="{{ $account->id }}" data-bs-toggle="tooltip" data-bs-title="Set sebagai akun default">
                      <i class="bi bi-star"></i>
                    </button>
                  @endif
                </td>
                <td class="text-end">
                  <div class="action-buttons d-flex justify-content-end">
                    <a href="{{ route('apps.accounts.show', $account) }}" class="btn btn-outline-info btn-sm me-2 view-account" data-bs-toggle="tooltip" data-bs-title="Lihat detail">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('apps.accounts.edit', $account) }}" class="btn btn-outline-warning btn-sm me-2 edit-account" data-bs-toggle="tooltip" data-bs-title="Edit akun">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" action="{{ route('apps.accounts.destroy', $account) }}">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm delete-account" data-bs-toggle="tooltip" data-bs-title="Hapus akun" onclick="return confirm('Are you sure to delete account: {{ $account->name }}');">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Format currency for all elements with .currency class
        document.querySelectorAll('.currency').forEach(element => {
            const value = element.textContent;
            if (!isNaN(value)) {
                // Divide by 100 because we store in minor units (cents)
                element.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(value);
            }
        });
        
        // Filter collapse functionality
        const filterCollapse = document.getElementById('filterCollapse');
        const filterCard = document.getElementById('filterCard');
        
        if (filterCollapse) {
            filterCollapse.addEventListener('show.bs.collapse', function () {
                filterCard.classList.remove('collapsed');
            });
            
            filterCollapse.addEventListener('hide.bs.collapse', function () {
                filterCard.classList.add('collapsed');
            });
        }
        
        // Clear search button
        const clearSearchBtn = document.getElementById('clearSearch');
        const searchInput = document.getElementById('search');
        
        if (clearSearchBtn && searchInput) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
            });
        }
        
        // Refresh accounts button
        const refreshBtn = document.getElementById('refreshAccounts');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                window.location.reload();
            });
        }
        
        // Toast notification function
        function showToast(type, title, message) {
            const toastContainer = document.createElement('div');
            toastContainer.innerHTML = `
                <div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}:</strong> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(toastContainer);
            
            const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
            toast.show();
            
            toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', function () {
                toastContainer.remove();
            });
        }
        
        // Handle filter form submission
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';
                submitBtn.disabled = true;
            });
        }
    });
</script>
@endpush