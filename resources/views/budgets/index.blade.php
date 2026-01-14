@extends('wallet::layouts.app')

@section('title', 'Kelola Budget')

@use('Modules\Wallet\Helpers\Helper')

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="row mb-4">
  <div class="col">
    <h2 class="page-title mb-2">
      <i class="bi bi-cash-coin me-2"></i>Kelola Budget
    </h2>
    <p class="text-muted mb-0">Kelola anggaran keuangan Anda. Pantau pengeluaran, atur periode, dan optimalkan pengelolaan uang.</p>
  </div>
  <div class="col-auto">
    <a href="{{ route('apps.budgets.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i>Create
    </a>
  </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="quick-stat" style="border-left: 4px solid #0d6efd;">
      <div class="stat-value">@money($stats['total_amount'])</div>
      <div class="stat-label">Total Budget Aktif</div>
      <small class="text-muted">{{ $stats['current'] ?? 0 }} budget aktif</small>
    </div>
  </div>
  
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="quick-stat" style="border-left: 4px solid #28a745;">
      <div class="stat-value">@money($stats['total_spent'])</div>
      <div class="stat-label">Total Terpakai</div>
      <small class="text-muted">{{ number_format($stats['overall_usage'] ?? 0, 1) }}% dari total</small>
    </div>
  </div>
  
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="quick-stat" style="border-left: 4px solid #ffc107;">
      <div class="stat-value">@money($stats['total_remaining'])</div>
      <div class="stat-label">Total Sisa</div>
      <small class="text-muted">{{ $stats['days_in_month'] ?? 30 }} hari tersisa</small>
    </div>
  </div>
  
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="quick-stat" style="border-left: 4px solid #dc3545;">
      <div class="stat-value">{{ $stats['over_budget'] ?? 0 }}</div>
      <div class="stat-label">Melebihi Budget</div>
      <small class="text-muted">{{ $stats['current'] ? round(($stats['over_budget'] / $stats['current']) * 100, 0) : 0 }}% dari total</small>
    </div>
  </div>
</div>

<!-- Filter Section -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="bi bi-funnel me-2"></i>Filter & Pencarian
    </h5>
  </div>
  <div class="card-body">
    <form action="{{ route('apps.budgets.index') }}" method="GET" id="filterForm">
      <div class="row g-3">
        <!-- Category Filter -->
        <div class="col-md-3">
          <label for="category_id" class="form-label">Kategori</label>
          <select class="form-select" id="category_id" name="category_id">
            <option value="">Semua Kategori</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
              </option>
            @endforeach
          </select>
        </div>

        <!-- Period Type Filter -->
        <div class="col-md-3">
          <label for="period_type" class="form-label">Tipe Periode</label>
          <select class="form-select" id="period_type" name="period_type">
            <option value="">Semua Tipe</option>
            @foreach($periodTypes as $type)
              <option value="{{ $type->value }}" {{ request('period_type') == $type->value ? 'selected' : '' }}>
                {{ ucfirst($type->value) }}
              </option>
            @endforeach
          </select>
        </div>

        <!-- Status Filter -->
        <div class="col-md-2">
          <label for="is_active" class="form-label">Status</label>
          <select class="form-select" id="is_active" name="is_active">
            <option value="">Semua</option>
            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
          </select>
        </div>

        <!-- Year Filter -->
        <div class="col-md-2">
          <label for="year" class="form-label">Tahun</label>
          <select class="form-select" id="year" name="year">
            <option value="">Semua</option>
            @for($i = date('Y') + 1; $i >= date('Y') - 2; $i--)
              <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>
                {{ $i }}
              </option>
            @endfor
          </select>
        </div>

        <!-- Search -->
        <div class="col-md-2">
          <label for="search" class="form-label">Cari</label>
          <div class="input-group">
            <input type="text" class="form-control" id="search" name="search" 
                   placeholder="Cari..." value="{{ request('search') }}">
          </div>
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-12">
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary me-2">
              <i class="bi bi-search me-1"></i>Terapkan Filter
            </button>
            <a href="{{ route('apps.budgets.index') }}" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-clockwise me-1"></i>Reset
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Period Quick Filter -->
<div class="period-filter mb-4">
  <h6 class="fw-semibold mb-3">Periode Cepat:</h6>
  <div class="d-flex flex-wrap gap-2">
    <a href="{{ route('apps.budgets.index', ['period_type' => 'monthly', 'period_value' => date('m'), 'year' => date('Y')]) }}" 
       class="btn btn-sm btn-outline-primary">
      Bulan Ini
    </a>
    <a href="{{ route('apps.budgets.index', ['period_type' => 'monthly', 'period_value' => date('m', strtotime('+1 month')), 'year' => date('Y')]) }}" 
       class="btn btn-sm btn-outline-secondary">
      Bulan Depan
    </a>
    <a href="{{ route('apps.budgets.index', ['period_type' => 'quarterly', 'period_value' => ceil(date('m') / 3), 'year' => date('Y')]) }}" 
       class="btn btn-sm btn-outline-success">
      Quarter Ini
    </a>
    <a href="{{ route('apps.budgets.index', ['period_type' => 'yearly', 'period_value' => 1, 'year' => date('Y')]) }}" 
       class="btn btn-sm btn-outline-warning">
      Tahun Ini
    </a>
    <a href="{{ route('apps.budgets.index', ['is_active' => 1, 'end_date' => date('Y-m-d', strtotime('+7 days'))]) }}" 
       class="btn btn-sm btn-outline-danger">
      Berakhir 7 Hari Lagi
    </a>
  </div>
</div>

<!-- Budgets List -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="bi bi-list-ul me-2"></i>Daftar Budget
      <span class="badge bg-secondary ms-2">{{ $budgets->total() }}</span>
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
      <a href="{{ route('apps.budgets.update-spent') }}" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Will re-calculate all spent budgets ?')">
        <i class="bi bi-arrow-clockwise"></i>
      </a>
    </div>
  </div>
        
  <div class="card-body">
    @if($budgets->isEmpty())
      <!-- Empty State -->
      <div class="text-center py-5">
        <div class="mb-4">
          <i class="bi bi-cash-coin display-1 text-muted"></i>
        </div>
        <h5 class="mb-3">Belum ada budget</h5>
        <p class="text-muted mb-4">Mulai dengan membuat budget pertama Anda untuk mengontrol pengeluaran.</p>
        <a href="{{ route('apps.budgets.create') }}" class="btn btn-primary">
          <i class="bi bi-plus-circle me-2"></i>Buat Budget Pertama
        </a>
      </div>
    @else
      <!-- Budgets Table -->
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th width="40">
                <input type="checkbox" class="form-check-input" id="selectAllBudgets">
              </th>
              <th>Kategori & Periode</th>
              <th>Akun</th>
              <th>Budget & Penggunaan</th>
              <th>Status</th>
              <th>Periode</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($budgets as $budget)
              @php
                // Determine status
                $statusClass = '';
                if (!$budget->is_active) {
                  $statusClass = 'budget-status-expired';
                } elseif ($budget->is_over_budget) {
                  $statusClass = 'budget-status-danger';
                } elseif ($budget->usage_percentage >= 80) {
                  $statusClass = 'budget-status-warning';
                } else {
                  $statusClass = 'budget-status-on-track';
                }
                
                // Progress bar color
                $progressColor = 'bg-success';
                if ($budget->is_over_budget) {
                  $progressColor = 'bg-danger';
                } elseif ($budget->usage_percentage >= 80) {
                  $progressColor = 'bg-warning';
                }
                
                // Status text
                $statusText = 'On Track';
                $statusBadge = 'bg-success';
                if (!$budget->is_active) {
                  $statusText = 'Nonaktif';
                  $statusBadge = 'bg-secondary';
                } elseif ($budget->is_over_budget) {
                  $statusText = 'Over Budget';
                  $statusBadge = 'bg-danger';
                } elseif ($budget->usage_percentage >= 80) {
                  $statusText = 'Warning';
                  $statusBadge = 'bg-warning';
                }
              @endphp
              <tr class="budget-card {{ $statusClass }}">
                <td>
                  <input type="checkbox" class="form-check-input budget-checkbox" value="{{ $budget->id }}">
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="budget-icon me-3" 
                         style="background-color: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary);">
                      <i class="bi {{ $budget->category->icon ?? 'bi-tag' }}"></i>
                    </div>
                    <div>
                      <strong class="d-block">{{ $budget->category->name }}</strong>
                      <small class="text-muted">
                        @if($budget->name)
                          {{ $budget->name }}
                        @else
                          Budget {{ ucfirst($budget->period_type->name) }}
                        @endif
                      </small>
                    </div>
                  </div>
                </td>
                <td>
                  @if($budget->accounts->isNotEmpty())
                    <div class="d-flex flex-wrap gap-1">
                      @foreach($budget->accounts->take(2) as $account)
                        <span class="accounts-badge" title="{{ $account->name }}">
                          <i class="bi {{ $account->icon ?? 'bi-wallet' }} me-1"></i>
                          {{ Str::limit($account->name, 10) }}
                        </span>
                      @endforeach
                      @if($budget->accounts->count() > 2)
                        <span class="accounts-badge">
                          +{{ $budget->accounts->count() - 2 }}
                        </span>
                      @endif
                    </div>
                  @else
                    <span class="text-muted">Semua Akun</span>
                  @endif
                </td>
                <td>
                  <div class="mb-2">
                    <div class="d-flex justify-content-between">
                      <span class="fw-semibold">{{ Helper::formatMoney($budget->spent->getAmount()->toInt()) }}</span>
                      <span class="text-muted">/ {{ Helper::formatMoney($budget->amount->getAmount()->toInt()) }}</span>
                    </div>
                    <div class="progress progress-budget mt-1">
                      <div class="progress-bar {{ $progressColor }}" 
                           style="width: {{ min($budget->usage_percentage, 100) }}%">
                      </div>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between">
                    <small class="text-muted">Sisa: {{ Helper::formatMoney($budget->remaining) }}</small>
                    <small class="fw-semibold {{ $budget->is_over_budget ? 'text-danger' : 'text-success' }}">
                      {{ number_format($budget->usage_percentage, 1) }}%
                    </small>
                  </div>
                </td>
                <td>
                  <span class="badge {{ $statusBadge }}">{{ $statusText }}</span>
                  @if($budget->days_left < 7 && $budget->days_left > 0)
                    <small class="d-block text-muted">{{ $budget->days_left }} hari lagi</small>
                  @endif
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ $budget->period_label }}</span>
                    <small class="text-muted">
                      {{ $budget->start_date->format('d M') }} - {{ $budget->end_date->format('d M Y') }}
                    </small>
                  </div>
                </td>
                <td class="text-end">
                  <div class="action-buttons d-flex justify-content-end">
                    <a href="{{ route('apps.budgets.show', $budget) }}" class="btn btn-outline-info btn-sm me-2">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('apps.budgets.edit', $budget) }}" class="btn btn-outline-warning btn-sm me-2">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" action="{{ route('apps.budgets.destroy', $budget) }}" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm" 
                        onclick="return confirm('Apakah Anda yakin ingin menghapus budget ini?')">
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
      
      <!-- Pagination -->
      @if($budgets->hasPages())
      <div class="d-flex justify-content-between align-items-center mt-4">
        <div>
          <p class="mb-0 text-muted">
            Menampilkan {{ $budgets->firstItem() }} - {{ $budgets->lastItem() }} dari {{ $budgets->total() }} budget
          </p>
        </div>
        <nav>
          {{ $budgets->links() }}
        </nav>
      </div>
      @endif
    @endif
  </div>
</div>

<!-- Bulk Actions -->
<div class="card mt-4">
  <div class="card-body">
    <div class="row align-items-center">
      <div class="col-md-3 mb-3">
        <select class="form-select" id="bulkAction">
          <option value="">Aksi Massal</option>
          <option value="activate">Aktifkan</option>
          <option value="deactivate">Nonaktifkan</option>
          <option value="duplicate">Duplikat</option>
          <option value="delete">Hapus</option>
        </select>
      </div>
      <div class="col-md-9 mb-3">
        <div class="d-flex justify-content-end">
          <button class="btn btn-outline-primary me-2" id="applyBulkAction">
            <i class="bi bi-check-circle me-1"></i>Terapkan
          </button>
          <button class="btn btn-outline-secondary" id="clearSelection">
            <i class="bi bi-x-circle me-1"></i>Bersihkan Pilihan
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bulk selection
    const selectAllCheckbox = document.getElementById('selectAllBudgets');
    const budgetCheckboxes = document.querySelectorAll('.budget-checkbox');
    const bulkActionSelect = document.getElementById('bulkAction');
    const applyBulkActionBtn = document.getElementById('applyBulkAction');
    const clearSelectionBtn = document.getElementById('clearSelection');
    
    // Select all checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            budgetCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Clear selection
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function() {
            budgetCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
        });
    }
    
    // Apply bulk action
    if (applyBulkActionBtn) {
        applyBulkActionBtn.addEventListener('click', function() {
            const action = bulkActionSelect.value;
            if (!action) {
                alert('Pilih aksi terlebih dahulu');
                return;
            }
            
            const selectedIds = [];
            budgetCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedIds.push(checkbox.value);
                }
            });
            
            if (selectedIds.length === 0) {
                alert('Pilih minimal satu budget');
                return;
            }
            
            if (action === 'delete') {
                if (!confirm(`Apakah Anda yakin ingin menghapus ${selectedIds.length} budget?`)) {
                    return;
                }
            }
            
            // Submit bulk action
            fetch('{{ route("api.apps.budgets.bulk-update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    budget_ids: selectedIds,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Berhasil', data.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('danger', 'Gagal', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', 'Error', 'Terjadi kesalahan');
            });
        });
    }
    
    // Toast notification
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
        
        toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', function() {
            toastContainer.remove();
        });
    }
});
</script>
@endpush

@push('styles')
<style>
    .budget-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    
    .budget-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .budget-status-on-track {
        border-left-color: #28a745 !important;
    }
    
    .budget-status-warning {
        border-left-color: #ffc107 !important;
    }
    
    .budget-status-danger {
        border-left-color: #dc3545 !important;
    }
    
    .budget-status-expired {
        border-left-color: #6c757d !important;
    }
    
    .progress-budget {
        height: 10px;
        border-radius: 5px;
    }
    
    .progress-budget .progress-bar {
        border-radius: 5px;
    }
    
    .budget-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .period-badge {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 20px;
    }
    
    .accounts-badge {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 10px;
        background-color: #e9ecef;
        color: #495057;
    }
    
    /* Quick stats cards */
    .quick-stat {
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .quick-stat .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .quick-stat .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    /* Period filter */
    .period-filter {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Dark mode adjustments */
    body[data-bs-theme="dark"] .period-filter {
        background-color: #2d3748;
    }
    
    body[data-bs-theme="dark"] .accounts-badge {
        background-color: #4a5568;
        color: #e2e8f0;
    }
    
    /* Calendar view */
    .calendar-day {
        border: 1px solid #dee2e6;
        height: 100px;
        padding: 5px;
        position: relative;
    }
    
    .calendar-day.today {
        background-color: rgba(13, 110, 253, 0.1);
        border-color: #0d6efd;
    }
    
    .calendar-day.has-budget {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .calendar-day.over-budget {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .day-number {
        font-size: 0.75rem;
        font-weight: bold;
    }
    
    /* Budget summary */
    .summary-card {
        border-radius: 10px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .summary-card .summary-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .summary-card .summary-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
</style>
@endpush