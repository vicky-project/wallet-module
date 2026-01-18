@extends('wallet::layouts.app')

@section('title', 'Kelola Kategori')

@use('Modules\Wallet\Enums\CategoryType')
@use('Modules\Wallet\Helpers\Helper')

@push('styles')
<style>
    /* Custom styling for categories page */
    .filter-card {
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .filter-card.collapsed {
        height: 60px;
        overflow: hidden;
    }
    
    .category-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    
    .category-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    
    .category-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .category-type-badge {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 20px;
    }
    
    .budget-display {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .budget-usage {
        font-size: 0.875rem;
    }
    
    .progress-thin {
        height: 6px;
        border-radius: 3px;
    }
    
    .progress-thin .progress-bar {
        border-radius: 3px;
    }
    
    .usage-warning {
        color: #fd7e14;
    }
    
    .usage-danger {
        color: #dc3545;
    }
    
    .usage-success {
        color: #28a745;
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
    
    body[data-bs-theme="dark"] .category-card {
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
    
    .category-card:hover .action-buttons {
        opacity: 1;
    }
    
    /* Custom badge colors for category types */
    .badge-expense {
        background-color: #dc3545;
        color: white;
    }
    
    .badge-income {
        background-color: #198754;
        color: white;
    }
    
    /* Budget status colors */
    .budget-safe {
        border-left-color: #28a745 !important;
    }
    
    .budget-warning {
        border-left-color: #fd7e14 !important;
    }
    
    .budget-danger {
        border-left-color: #dc3545 !important;
    }
    
    .budget-none {
        border-left-color: #6c757d !important;
    }
    
    /* Color coding for budget usage */
    .usage-low { color: #28a745; }
    .usage-medium { color: #fd7e14; }
    .usage-high { color: #dc3545; }
    
    /* Transaction count badge */
    .transaction-badge {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 10px;
    }
    
    /* Responsive table */
    @media (max-width: 768px) {
        .category-card .row > div {
            margin-bottom: 1rem;
        }
        
        .action-buttons {
            opacity: 1;
            margin-top: 1rem;
            justify-content: center !important;
        }
        
        .budget-display {
            font-size: 0.9rem;
        }
        
        .category-icon {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
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
      <i class="bi bi-tags me-2"></i>Categories
    </h2>
    <p class="text-muted mb-0">Kelola semua kategori transaksi Anda. Atur budget, pantau pengeluaran, dan organisasi keuangan.</p>
  </div>
  <div class="col-auto">
    <div class="btn-group">
      <a href="{{ route('apps.categories.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Create
      </a>
      <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
        <span class="visually-hidden">Toggle Dropdown</span>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="{{ route('apps.categories.create') }}?type=expense"><i class="bi bi-arrow-up-right text-danger me-2"></i>Kategori Pengeluaran</a></li>
        <li><a class="dropdown-item" href="{{ route('apps.categories.create') }}?type=income"><i class="bi bi-arrow-down-left text-success me-2"></i>Kategori Pemasukan</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload me-2"></i>Import Kategori</a></li>
      </ul>
    </div>
  </div>
</div>

<!-- Budget Warnings Alert -->
@if($budgetWarnings && $budgetWarnings->count() > 0)
<div class="alert alert-warning alert-dismissible fade show" role="alert">
  <div class="d-flex">
    <div class="flex-shrink-0">
      <i class="bi bi-exclamation-triangle-fill fs-4"></i>
    </div>
    <div class="flex-grow-1 ms-3">
      <h5 class="alert-heading mb-2">
        <i class="bi bi-exclamation-triangle"></i> Peringatan Budget!
      </h5>
      <p class="mb-1">{{ $budgetWarnings->count() }} kategori telah melebihi atau mendekati limit budget bulan ini:</p>
      <div class="mt-2">
        @foreach($budgetWarnings->take(3) as $warning)
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle me-2 mb-1">
          {{ $warning['category_name'] }} ({{ number_format($warning['usage_percentage'], 0) }}%)
        </span>
        @endforeach
        @if($budgetWarnings->count() > 3)
        <span class="badge bg-secondary">+{{ $budgetWarnings->count() - 3 }} lainnya</span>
        @endif
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <div class="mt-3">
    <a href="{{ route('apps.budgets.index') }}" class="btn btn-warning btn-sm">
      <i class="bi bi-pencil-square me-1"></i>Kelola Budget
    </a>
  </div>
</div>
@endif

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
      <form action="{{ route('apps.categories.index') }}" method="GET" id="filterForm">
        <div class="row g-3">
          <!-- Type Filter -->
          <div class="col-md-4">
            <label for="type" class="form-label">Tipe Kategori</label>
            <select class="form-select" id="type" name="type">
              <option value="">Semua Tipe</option>
              @foreach(CategoryType::cases() as $type)
              <option value="{{ $type->value}}" @selected(request('type') == $type->value)>{{ $type->name }}</option>
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

          <!-- Budget Filter -->
          <div class="col-md-4">
            <label for="has_budget" class="form-label">Status Budget</label>
            <select class="form-select" id="has_budget" name="has_budget">
              <option value="">Semua</option>
              <option value="1" {{ request('has_budget') === '1' ? 'selected' : '' }}>Memiliki Budget</option>
              <option value="0" {{ request('has_budget') === '0' ? 'selected' : '' }}>Tanpa Budget</option>
            </select>
          </div>

          <!-- Search -->
          <div class="col-md-6">
            <label for="search" class="form-label">Cari</label>
            <div class="input-group">
              <input type="text" class="form-control" id="search" name="search" placeholder="Cari nama atau deskripsi kategori..." value="{{ request('search') }}">
              <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>

          <!-- Sort By -->
          <div class="col-md-3">
            <label for="sort_by" class="form-label">Urutkan Berdasarkan</label>
            <select class="form-select" id="sort_by" name="sort_by">
              <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Nama</option>
              <option value="type" {{ request('sort_by') == 'type' ? 'selected' : '' }}>Tipe</option>
              <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Tanggal Dibuat</option>
              <option value="transactions_count" {{ request('sort_by') == 'transactions_count' ? 'selected' : '' }}>Jumlah Transaksi</option>
            </select>
          </div>

          <!-- Sort Order -->
          <div class="col-md-3">
            <label for="sort_order" class="form-label">Urutan</label>
            <select class="form-select" id="sort_order" name="sort_order">
              <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Naik (A-Z)</option>
              <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Turun (Z-A)</option>
            </select>
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-12">
            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary me-2">
                <i class="bi bi-search me-1"></i>Terapkan Filter
              </button>
              <a href="{{ route('apps.categories.index') }}" class="btn btn-outline-secondary">
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
            <h6 class="text-muted fw-semibold mb-2">Total Kategori</h6>
            <h2 class="mb-0">{{ $stats['total'] ?? 0 }}</h2>
          </div>
          <div class="col-auto">
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
              <i class="bi bi-tags"></i>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-muted">
            <i class="bi bi-check-circle me-1"></i>{{ $stats['active'] ?? 0 }} aktif
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
            <h6 class="text-muted fw-semibold mb-2">Kategori Pemasukan</h6>
            <h2 class="mb-0">{{ $stats['income'] ?? 0 }}</h2>
          </div>
          <div class="col-auto">
            <div class="stat-icon bg-success bg-opacity-10 text-success">
              <i class="bi bi-arrow-down-left"></i>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-success">
            <i class="bi bi-graph-up me-1"></i>Untuk pendapatan
          </small>
        </div>
      </div>
    </div>
  </div>
        
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-start border-danger border-4 h-100">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col">
            <h6 class="text-muted fw-semibold mb-2">Kategori Pengeluaran</h6>
            <h2 class="mb-0">{{ $stats['expense'] ?? 0 }}</h2>
          </div>
          <div class="col-auto">
            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
              <i class="bi bi-arrow-up-right"></i>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-danger">
            <i class="bi bi-graph-down me-1"></i>Untuk pengeluaran
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
            <h6 class="text-muted fw-semibold mb-2">Kategori dengan Budget</h6>
            <h2 class="mb-0">{{ $stats['with_budget'] ?? 0 }}</h2>
          </div>
          <div class="col-auto">
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
              <i class="bi bi-cash-coin"></i>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <small class="text-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ $stats['budget_exceeded'] ?? 0 }} melebihi budget
          </small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Categories List -->
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center text-start">
    <h5 class="mb-0 me-auto">
      <i class="bi bi-list-ul me-2"></i>Daftar Kategori
      <span class="badge bg-secondary ms-2">{{ $categories->total() }}</span>
    </h5>
    <div class="d-flex align-items-center">
      <div class="input-group me-2" style="width: 150px;">
        <span class="input-group-text"><i class="bi bi-list-check"></i></span>
        <select class="form-select" id="bulkAction">
          <option value="">Bulk Action</option>
          <option value="activate">Aktifkan</option>
          <option value="deactivate">Nonaktifkan</option>
          <option value="delete">Hapus</option>
        </select>
      </div>
      <div class="dropdown me-2">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
          <i class="bi bi-download"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
          <li><a class="dropdown-item" href="#"><i class="bi bi-filetype-pdf me-2"></i>PDF</a></li>
          <li><a class="dropdown-item" href="#"><i class="bi bi-filetype-xlsx me-2"></i>Excel</a></li>
        </ul>
      </div>
      <button class="btn btn-outline-secondary btn-sm" id="refreshCategories">
        <i class="bi bi-arrow-clockwise"></i>
      </button>
    </div>
  </div>
        
  <div class="card-body">
    @if($categories->isEmpty())
      <!-- Empty State -->
      <div class="empty-state">
        <div class="empty-state-icon">
          <i class="bi bi-tags"></i>
        </div>
        <h5 class="mb-3">Belum ada kategori</h5>
        <p class="text-muted mb-4">Mulai dengan menambahkan kategori pertama Anda untuk mengorganisir transaksi keuangan.</p>
        <div class="d-flex justify-content-center gap-2">
          <a href="{{ route('apps.categories.create') }}?type=expense" class="btn btn-danger">
            <i class="bi bi-arrow-up-right me-2"></i>Tambah Pengeluaran
          </a>
          <a href="{{ route('apps.categories.create') }}?type=income" class="btn btn-success">
            <i class="bi bi-arrow-down-left me-2"></i>Tambah Pemasukan
          </a>
        </div>
      </div>
    @else
      <!-- Categories List -->
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th width="40">
                <input type="checkbox" class="form-check-input" id="selectAllCategories">
              </th>
              <th width="300">Nama Kategori</th>
              <th>Tipe</th>
              <th>Budget</th>
              <th>Penggunaan</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($categories as $category)
              @php
                // Determine budget status
                $budgetStatus = 'budget-none';
                $usagePercentage = $category->budget_usage_percentage ?? 0;
                $hasBudget = isset($category->current_budget);
                $isExceeded = $category->has_budget_exceeded ?? false;
                
                if ($hasBudget) {
                  if ($isExceeded) {
                    $budgetStatus = 'budget-danger';
                  } elseif ($usagePercentage >= 80) {
                    $budgetStatus = 'budget-warning';
                  } else {
                    $budgetStatus = 'budget-safe';
                  }
                }
                
                // Usage text class
                $usageClass = 'usage-low';
                if ($usagePercentage >= 80) {
                  $usageClass = 'usage-high';
                } elseif ($usagePercentage >= 50) {
                  $usageClass = 'usage-medium';
                }
              @endphp
              <tr class="category-card {{ $budgetStatus }}" onclick="window.location.href='{{ route('apps.categories.show', $category) }}'">
                <td>
                  <input type="checkbox" class="form-check-input category-checkbox" value="{{ $category->id }}" data-name="{{ $category->name }}">
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="category-icon me-3" style="background-color: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary);">
                      <i class="bi {{ $category->icon ?? 'bi-tag' }}"></i>
                    </div>
                    <div>
                      <strong class="d-block">{{ $category->name }}</strong>
                      @if($category->description)
                        <small class="text-muted">{{ Str::limit($category->description, 50) }}</small>
                      @endif
                      @if($category->transactions_count > 0)
                        <small class="badge bg-info-subtle text-info-emphasis border border-info-subtle transaction-badge">
                          {{ $category->transactions_count }} transaksi
                        </small>
                      @endif
                    </div>
                  </div>
                </td>
                <td>
                  @if($category->type === CategoryType::INCOME)
                    <span class="badge badge-income category-type-badge">
                      <i class="bi bi-arrow-down-left me-1"></i>Pemasukan
                    </span>
                  @else
                    <span class="badge badge-expense category-type-badge">
                      <i class="bi bi-arrow-up-right me-1"></i>Pengeluaran
                    </span>
                  @endif
                </td>
                <td>
                  @if($hasBudget && $category->current_budget)
                    <div class="budget-display">
                      {{ Helper::toMoney($category->current_spent ?? 0)->getAmount()->toInt() }} / {{ Helper::toMoney($category->current_budget->amount)->getAmount()->toInt() }}
                    </div>
                    <div class="d-flex align-items-center">
                      <div class="progress progress-thin flex-grow-1 me-2">
                        <div class="progress-bar 
                          @if($isExceeded) bg-danger
                          @elseif($usagePercentage >= 80) bg-warning
                          @else bg-success @endif" 
                          style="width: {{ min($usagePercentage, 100) }}%">
                        </div>
                      </div>
                      <span class="budget-usage {{ $usageClass }} fw-semibold">
                        {{ number_format($usagePercentage, 1) }}%
                      </span>
                    </div>
                    <small class="text-muted d-block mt-1">
                      Sisa: {{ Helper::formatMoney(Helper::toMoney($category->budget_remaining ?? 0)->getAmount()->toInt()) }}
                    </small>
                  @else
                    <div class="text-muted">
                      <i class="bi bi-dash-circle"></i> Tidak ada budget
                    </div>
                    @if($category->is_budgetable)
                    <a href="{{ route('apps.budgets.create', ['category_id' => $category->id]) }}" class="btn btn-outline-primary btn-sm mt-1">
                      <i class="bi bi-plus-circle"></i> Buat Budget
                    </a>
                    @endif
                  @endif
                </td>
                <td>
                  <div class="budget-display">
                    {{ Helper::formatMoney(Helper::toMoney($category->monthly_total ?? 0)->getAmount()->toInt()) }}
                  </div>
                  <div class="budget-usage text-muted">
                    <small>
                      Bulan {{ now()->translatedFormat('F') }}
                    </small>
                  </div>
                </td>
                <td>
                  @if($category->is_active)
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
                <td class="text-end">
                  <div class="action-buttons d-flex justify-content-end">
                    <a href="{{ route('apps.categories.show', $category) }}" class="btn btn-outline-info btn-sm me-2 view-category" data-bs-toggle="tooltip" data-bs-title="Lihat detail">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('apps.categories.edit', $category) }}" class="btn btn-outline-warning btn-sm me-2 edit-category" data-bs-toggle="tooltip" data-bs-title="Edit kategori">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" action="{{ route('apps.categories.destroy', $category) }}" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm delete-category" 
                        data-bs-toggle="tooltip" 
                        data-bs-title="Hapus kategori"
                        onclick="return confirm('Apakah Anda yakin ingin menghapus kategori: {{ $category->name }}?')">
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
      @if($categories->hasPages())
      <div class="d-flex justify-content-between align-items-center mt-4">
        <div>
          <p class="mb-0 text-muted">
            Menampilkan {{ $categories->firstItem() }} - {{ $categories->lastItem() }} dari {{ $categories->total() }} kategori
          </p>
        </div>
        <nav>
          {{ $categories->links() }}
        </nav>
      </div>
      @endif
    @endif
  </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('apps.categories.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Import Kategori</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="importFile" class="form-label">File CSV/Excel</label>
            <input type="file" class="form-control" id="importFile" name="file" accept=".csv,.xlsx,.xls" required>
            <div class="form-text">
              Format file: CSV atau Excel dengan kolom: name, type, icon, description
            </div>
          </div>
          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="skipDuplicates" name="skip_duplicates" checked>
              <label class="form-check-label" for="skipDuplicates">
                Lewati duplikat berdasarkan nama
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-upload me-1"></i>Import
          </button>
        </div>
      </form>
    </div>
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
        
        // Refresh categories button
        const refreshBtn = document.getElementById('refreshCategories');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                window.location.reload();
            });
        }
        
        // Bulk selection
        const selectAllCheckbox = document.getElementById('selectAllCategories');
        const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
        const bulkActionSelect = document.getElementById('bulkAction');
        
        // Select all checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                categoryCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
            });
        }
        
        // Bulk action handler
        if (bulkActionSelect) {
            bulkActionSelect.addEventListener('change', function() {
                const action = this.value;
                if (!action) return;
                
                const selectedIds = [];
                const selectedNames = [];
                
                categoryCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedIds.push(checkbox.value);
                        selectedNames.push(checkbox.dataset.name);
                    }
                });
                
                if (selectedIds.length === 0) {
                    alert('Pilih minimal satu kategori untuk melakukan aksi massal.');
                    this.value = '';
                    return;
                }
                
                let confirmationMessage = '';
                let apiEndpoint = '';
                let method = 'POST';
                
                switch(action) {
                    case 'activate':
                        confirmationMessage = `Aktifkan ${selectedIds.length} kategori?`;
                        apiEndpoint = '{{ route("apps.categories.bulk-update") }}';
                        break;
                    case 'deactivate':
                        confirmationMessage = `Nonaktifkan ${selectedIds.length} kategori?`;
                        apiEndpoint = '{{ route("apps.categories.bulk-update") }}';
                        break;
                    case 'delete':
                        confirmationMessage = `Hapus ${selectedIds.length} kategori?\n\n${selectedNames.join('\n')}\n\nAksi ini tidak dapat dibatalkan!`;
                        apiEndpoint = '{{ route("apps.categories.bulk-delete") }}';
                        method = 'DELETE';
                        break;
                }
                
                if (confirm(confirmationMessage)) {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    selectedIds.forEach(id => {
                        formData.append('category_ids[]', id);
                    });
                    
                    if (action !== 'delete') {
                        formData.append('is_active', action === 'activate' ? '1' : '0');
                    }
                    
                    fetch(apiEndpoint, {
                        method: method,
                        body: formData
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
                        showToast('danger', 'Error', 'Terjadi kesalahan saat memproses.');
                    });
                }
                
                // Reset select
                this.value = '';
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
        
        // Quick status toggle
        document.querySelectorAll('.status-toggle').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const categoryId = this.dataset.id;
                const currentStatus = this.dataset.status === 'active';
                
                fetch(`{{ config('app.url') }}/apps/categories/${categoryId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Berhasil', 'Status kategori berhasil diubah.');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('danger', 'Error', 'Terjadi kesalahan saat mengubah status.');
                });
            });
        });
        
        // Quick edit with modal
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                // Will open edit modal or redirect to edit page
            });
        });
    });
</script>
@endpush