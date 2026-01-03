@extends('wallet::layouts.app')

@section('title', 'Kategori - ' . config('app.name', 'VickyServer'))

@use('Modules\Wallet\Enums\CategoryType')

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="page-title mb-2">
      <i class="bi bi-pie-chart me-2"></i>Kategori
    </h1>
    <p class="small text-muted mb-0">Kelola kategori pemasukan dan pengeluaran Anda</p>
  </div>
  <a href="{{ route('apps.categories.create') }}" class="btn btn-primary" role="button">
    <i class="bi bi-plus-circle me-2"></i>Create
  </a>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
  <div class="col-md-3 mb-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Total Kategori</h6>
            <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
          </div>
          <div class="card-icon bg-primary text-white">
            <i class="bi bi-tags"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Pemasukan</h6>
            <h3 class="mb-0">{{ $stats['income'] ?? 0 }}</h3>
          </div>
          <div class="card-icon bg-success text-white">
            <i class="bi bi-arrow-up-circle"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Pengeluaran</h6>
            <h3 class="mb-0">{{ $stats['expense'] ?? 0 }}</h3>
          </div>
          <div class="card-icon bg-danger text-white">
            <i class="bi bi-arrow-down-circle"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Aktif</h6>
            <h3 class="mb-0">{{ $stats['active'] ?? 0 }}</h3>
          </div>
          <div class="card-icon bg-warning text-white">
            <i class="bi bi-check-circle"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Budget Warnings -->
@php
  $budgetWarnings = app(\Modules\Wallet\Repositories\CategoryRepository::class)
    ->getBudgetWarnings(auth()->user());
@endphp
    
@if($budgetWarnings->count() > 0)
  <div class="row mb-4">
    <div class="col-12">
      <div class="card border-warning">
        <div class="card-header bg-warning text-white">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Peringatan Anggaran
        </div>
        <div class="card-body">
          <div class="row">
            @foreach($budgetWarnings as $warning)
            <div class="col-md-4 mb-3">
              <div class="d-flex align-items-center">
                <div class="transaction-icon me-3" style="background-color: #f8961e; color: white;">
                  <i class="bi bi-exclamation-circle"></i>
                </div>
                <div>
                  <h6 class="mb-1">{{ $warning['category']->name }}</h6>
                  <div class="progress" style="height: 8px;">
                    <div class="progress-bar 
                    @if($warning['is_exceeded']) bg-danger 
                    @else bg-warning
                    @endif" role="progressbar" style="width: {{ min($warning['usage_percentage'], 100) }}%"></div>
                  </div>
                  <small class="text-muted">
                    {{ number_format($warning['usage_percentage'], 1) }}% dari 
                    {{ $warning['formatted_budget_limit'] }}
                  </small>
                </div>
              </div>
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<!-- Categories Table -->
<div class="card">
  <div class="card-body">
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
          <i class="bi bi-grid me-1"></i> All
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="income-tab" data-bs-toggle="tab" data-bs-target="#income" type="button">
          <i class="bi bi-arrow-up-circle me-1 text-success"></i> Income
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="expense-tab" data-bs-toggle="tab" data-bs-target="#expense" type="button">
          <i class="bi bi-arrow-down-circle me-1 text-danger"></i> Expense
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="inactive-tab" data-bs-toggle="tab" data-bs-target="#inactive" type="button">
          <i class="bi bi-toggle-off text-danger">InActive</i>
        </button>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="categoryTabsContent">
      <div class="tab-pane fade show active" id="all" role="tabpanel">
        @if($categories->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th width="50">#</th>
                  <th>Nama Kategori</th>
                  <th>Tipe</th>
                  <th>Anggaran</th>
                  <th>Penggunaan Bulan Ini</th>
                  <th>Status</th>
                  <th width="150" class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody id="categoryTableBody">
                @foreach($categories as $index => $category)
                  @php
                  $monthlyTotal = $category->getMonthlyTotal();
                  $budgetUsage = $category->budget_usage_percentage;
                  $isExceeded = $category->has_budget_exceeded;
                  @endphp
                  <tr data-type="{{ $category->type }}" data-status="{{ $category->is_active ? 'active' : 'inactive' }}" class="{{ $isExceeded ? 'table-danger' : '' }}">
                    <td>{{ $index + 1 }}</td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="transaction-icon" style="background-color: {{ $category->color ?? ($category->type === CategoryType::INCOME ? '#10b981' : '#ef4444') }}; color: white;">
                          <i class="bi {{ $category->icon_class }}"></i>
                        </div>
                        <div class="ms-3">
                          <h6 class="mb-0">{{ $category->name }}</h6>
                          <small class="text-muted">{{ $category->description ?? 'Tidak ada deskripsi' }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      @if($category->type === CategoryType::INCOME)
                        <span class="badge bg-success">
                          <i class="bi bi-arrow-up-circle me-1"></i>Pemasukan
                        </span>
                      @else
                        <span class="badge bg-danger">
                          <i class="bi bi-arrow-down-circle me-1"></i>Pengeluaran
                        </span>
                      @endif
                    </td>
                    <td>
                      @if($category->budget_limit)
                        <div class="fw-bold">{{ $category->formatted_budget_limit }}</div>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @if($category->type === CategoryType::EXPENSE && $category->budget_limit)
                        <div class="d-flex align-items-center">
                          <div class="me-2" style="width: 100px;">
                            <div class="progress" style="height: 8px;">
                              <div class="progress-bar
                              @if($isExceeded) bg-danger
                              @elseif($budgetUsage >= 80) bg-warning
                              @else bg-success
                              @endif" role="progressbar" style="width: {{ min($budgetUsage, 100) }}%">
                              </div>
                            </div>
                          </div>
                          <small class="{{ $isExceeded ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ number_format($budgetUsage, 1) }}%
                          </small>
                        </div>
                        <small class="text-muted">
                          Rp {{ number_format($monthlyTotal, 0, ',', '.') }}
                        </small>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @if($category->is_active)
                        <span class="badge bg-success">Aktif</span>
                      @else
                        <span class="badge bg-secondary">Nonaktif</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <div class="btn-group" role="group">
                        <a href="{{ route('apps.categories.show', $category) }}" class="btn btn-sm btn-outline-info">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('apps.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('apps.categories.toggle-status', $category) }}" method="POST" class="d-inline">
                          @csrf
                          @method('PUT')
                          <button type="submit" class="btn btn-sm btn-outline-warning">
                            @if($category->is_active)
                            <i class="bi bi-toggle-off" title="Nonaktifkan"></i>
                            @else
                            <i class="bi bi-toggle-on" title="Aktifkan"></i>
                            @endif
                          </button>
                        </form>
                        <form action="{{ route('apps.categories.destroy', $category) }}" method="POST" class="d-inline">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kategori ini? Transaksi yang terkait akan tetap ada.')">
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
        @else
          <div class="text-center py-5">
            <i class="bi bi-pie-chart display-1 text-muted"></i>
            <h4 class="mt-3">Belum ada kategori</h4>
            <p class="text-muted">Mulai dengan membuat kategori pertama Anda</p>
            <a href="{{ route('apps.categories.create') }}" class="btn btn-primary" role="button">
              <i class="bi bi-plus-circle me-2"></i>Tambah Kategori
            </a>
          </div>
        @endif
      </div>

      <!-- Income Tab -->
      <div class="tab-pane fade" id="income" role="tabpanel">
        @php $incomeCategories = $categories->where('type', 'income'); @endphp
        @if($incomeCategories->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Nama Kategori</th>
                  <th>Icon</th>
                  <th>Status</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($incomeCategories as $category)
                  <tr>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="transaction-icon bg-success text-white me-3">
                          <i class="bi {{ $category->icon_class }}"></i>
                        </div>
                        <div>
                          <h6 class="mb-0">{{ $category->name }}</h6>
                          <small class="text-muted">{{ $category->description ?? 'Tidak ada deskripsi' }}</small>
                        </div>
                      </div>
                    </td>
                    <td><i class="bi {{ $category->icon_class }} fs-5"></i></td>
                    <td>
                      @if($category->is_active)
                      <span class="badge bg-success">Aktif</span>
                      @else
                      <span class="badge bg-secondary">Nonaktif</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <!-- Similar action buttons -->
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="text-center py-4">
            <p class="text-muted">Belum ada kategori pemasukan</p>
          </div>
        @endif
      </div>

      <!-- Expense Tab -->
      <div class="tab-pane fade" id="expense" role="tabpanel">
        @php $expenseCategories = $categories->where('type', 'expense'); @endphp
        @if($expenseCategories->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Nama Kategori</th>
                  <th>Anggaran</th>
                  <th>Penggunaan</th>
                  <th>Status</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($expenseCategories as $category)
                  @php
                  $budgetUsage = $category->budget_usage_percentage;
                  $isExceeded = $category->has_budget_exceeded;
                  @endphp
                  <tr class="{{ $isExceeded ? 'table-danger' : '' }}">
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="transaction-icon bg-danger text-white me-3">
                          <i class="bi {{ $category->icon_class }}"></i>
                        </div>
                        <div>
                          <h6 class="mb-0">{{ $category->name }}</h6>
                          <small class="text-muted">{{ $category->description ?? 'Tidak ada deskripsi' }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      @if($category->budget_limit)
                        <div class="fw-bold">{{ $category->formatted_budget_limit }}</div>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @if($category->budget_limit)
                        <div class="d-flex align-items-center">
                          <div class="me-2" style="width: 80px;">
                            <div class="progress" style="height: 6px;">
                              <div class="progress-bar 
                                @if($isExceeded) bg-danger 
                                @elseif($budgetUsage >= 80) bg-warning 
                                @else bg-success @endif" style="width: {{ min($budgetUsage, 100) }}%">
                              </div>
                            </div>
                          </div>
                          <small>{{ number_format($budgetUsage, 1) }}%</small>
                        </div>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @if($category->is_active)
                        <span class="badge bg-success">Aktif</span>
                      @else
                        <span class="badge bg-secondary">Nonaktif</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <!-- Similar action buttons -->
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="text-center py-4">
            <p class="text-muted">Belum ada kategori pengeluaran</p>
          </div>
        @endif
      </div>
      
      <!-- In Active Tab -->
      <div class="tab-pane fade" id="inactive" role="tabpanel"></div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
    .progress-thin {
        height: 6px;
        margin-top: 5px;
    }
    
    .budget-warning {
        background-color: rgba(248, 150, 30, 0.1);
        border-left: 4px solid #f8961e;
    }
    
    .budget-danger {
        background-color: rgba(239, 68, 68, 0.1);
        border-left: 4px solid #ef4444;
    }
</style>
@endpush

@push('scripts')
<script>
    // Show budget warnings
    document.getElementById('showBudgetWarnings')?.addEventListener('click', function(e) {
        e.preventDefault();
        const warnings = document.querySelector('.card.border-warning');
        if (warnings) {
            warnings.scrollIntoView({ behavior: 'smooth' });
            warnings.classList.add('animate__animated', 'animate__pulse');
            setTimeout(() => {
                warnings.classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        }
    });

    // Search Categories
    document.getElementById('searchCategories')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#categoryTableBody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
</script>
@endpush