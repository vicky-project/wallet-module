@extends('wallet::layouts.app')

@section('title', 'Kategori - ' . config('app.name', 'VickyServer'))

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="page-title mb-2">
      <i class="bi bi-pie-chart me-2"></i>Kategori Keuangan
    </h1>
    <p class="text-muted mb-0">Kelola kategori pemasukan dan pengeluaran Anda</p>
  </div>
  <a href="{{ route('apps.categories.create') }}" class="btn btn-primary" role="button">
    <i class="bi bi-plus-circle me-2"></i>Tambah Kategori
  </a>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
  <div class="col-md-3">
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
  <div class="col-md-3">
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
  <div class="col-md-3">
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
  <div class="col-md-3">
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

<!-- Filter Tabs -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
          <ul class="nav nav-tabs card-header-tabs" id="categoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                <i class="bi bi-grid me-1"></i> Semua
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="income-tab" data-bs-toggle="tab" data-bs-target="#income" type="button" role="tab">
                <i class="bi bi-arrow-up-circle me-1"></i> Pemasukan
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="expense-tab" data-bs-toggle="tab" data-bs-target="#expense" type="button" role="tab">
                <i class="bi bi-arrow-down-circle me-1"></i> Pengeluaran
              </button>
            </li>
          </ul>

          <div class="d-flex align-items-center">
            <div class="form-check form-switch me-3">
              <input class="form-check-input" type="checkbox" id="showInactive">
              <label class="form-check-label" for="showInactive">Tampilkan Nonaktif</label>
            </div>
            <div class="input-group" style="width: 250px;">
              <input type="text" class="form-control" placeholder="Cari kategori..." id="searchCategories">
              <button class="btn btn-outline-secondary" type="button">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Categories Table -->
<div class="tab-content" id="categoryTabsContent">
  <!-- All Categories Tab -->
  <div class="tab-pane fade show active" id="all" role="tabpanel">
    <div class="card">
      <div class="card-body">
        @if($categories->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th width="50">#</th>
                <th>Nama Kategori</th>
                <th>Tipe</th>
                <th>Icon</th>
                <th>Warna</th>
                <th>Anggaran</th>
                <th>Status</th>
                <th width="120" class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="categoryTableBody">
              @foreach($categories as $index => $category)
              <tr data-type="{{ $category->type }}" data-status="{{ $category->is_active ? 'active' : 'inactive' }}">
                <td>{{ $index + 1 }}</td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="transaction-icon" style="background-color: {{ $category->color ?? '#4361ee' }}; color: white;">
                      <i class="bi {{ $category->icon ?? 'bi-tag' }}"></i>
                    </div>
                    <div class="ms-3">
                      <h6 class="mb-0">{{ $category->name }}</h6>
                      <small class="text-muted">{{ $category->description ?? 'Tidak ada deskripsi' }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  @if($category->type === 'income')
                  <span class="badge bg-income">Pemasukan</span>
                  @else
                  <span class="badge bg-expense">Pengeluaran</span>
                  @endif
                </td>
                <td><i class="bi {{ $category->icon ?? 'bi-tag' }}"></i></td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="color-preview me-2" style="width: 20px; height: 20px; background-color: {{ $category->color ?? '#4361ee' }}; border-radius: 4px;"></div>
                    <span>{{ $category->color ?? '#4361ee' }}</span>
                  </div>
                </td>
                <td>
                  @if($category->budget_limit)
                  <span class="currency">{{ number_format($category->budget_limit, 0, ',', '.') }}</span>
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
                    <button class="btn btn-sm btn-outline-primary edit-category" data-id="{{ $category->id }}" data-bs-toggle="modal"  data-bs-target="#editCategoryModal">   <i class="bi bi-pencil"></i>
                    </button>
                    <form action="{{ route('wallet.categories.toggle-status', $category) }}" method="POST" class="d-inline">
                      @csrf
                      @method('PUT')
                      <button type="submit" class="btn btn-sm btn-outline-warning">
                        @if($category->is_active)
                        <i class="bi bi-toggle-off"></i>
                        @else
                        <i class="bi bi-toggle-on"></i>
                        @endif
                      </button>
                    </form>
                    <form action="{{ route('wallet.categories.destroy', $category) }}" method="POST" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kategori ini?')">
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
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
            <i class="bi bi-plus-circle me-2"></i>Tambah Kategori
          </button>
        </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Income Categories Tab -->
  <div class="tab-pane fade" id="income" role="tabpanel">
    <!-- Similar table structure filtered for income -->
  </div>

  <!-- Expense Categories Tab -->
  <div class="tab-pane fade" id="expense" role="tabpanel">
    <!-- Similar table structure filtered for expense -->
  </div>
</div>

<!-- Modal Create Category -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCategoryModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Kategori Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('wallet.categories.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="categoryName" name="name" required placeholder="Contoh: Belanja, Gaji, Transportasi">
                    </div>
                    <div class="mb-3">
                        <label for="categoryType" class="form-label">Tipe <span class="text-danger">*</span></label>
                        <select class="form-select" id="categoryType" name="type" required>
                            <option value="">Pilih tipe...</option>
                            <option value="income">Pemasukan</option>
                            <option value="expense">Pengeluaran</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoryIcon" class="form-label">Icon</label>
                                <select class="form-select" id="categoryIcon" name="icon">
                                    <option value="bi-tag">Tag</option>
                                    <option value="bi-bag">Belanja</option>
                                    <option value="bi-cash">Uang</option>
                                    <option value="bi-car">Transportasi</option>
                                    <option value="bi-house">Rumah</option>
                                    <option value="bi-phone">Telepon</option>
                                    <option value="bi-heart">Kesehatan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoryColor" class="form-label">Warna</label>
                                <input type="color" class="form-control form-control-color" id="categoryColor" name="color" value="#4361ee" title="Pilih warna">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="categoryBudget" class="form-label">Batas Anggaran (Opsional)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="categoryBudget" name="budget_limit" placeholder="0" min="0">
                        </div>
                        <small class="text-muted">Biarkan kosong jika tidak ada batasan</small>
                    </div>
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="categoryDescription" name="description" rows="3" placeholder="Deskripsi singkat tentang kategori ini"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="categoryActive" name="is_active" value="1" checked>
                        <label class="form-check-label" for="categoryActive">
                            Aktifkan kategori
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Search Categories
    document.getElementById('searchCategories')?.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#categoryTableBody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Toggle Inactive Categories
    document.getElementById('showInactive')?.addEventListener('change', function(e) {
        const showInactive = e.target.checked;
        const rows = document.querySelectorAll('#categoryTableBody tr');
        
        rows.forEach(row => {
            if (row.dataset.status === 'inactive') {
                row.style.display = showInactive ? '' : 'none';
            }
        });
    });

    // Format currency inputs
    document.querySelectorAll('.currency').forEach(element => {
        const value = parseFloat(element.textContent.replace(/[^0-9.-]+/g,""));
        if (!isNaN(value)) {
            element.textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        }
    });
</script>
@endpush