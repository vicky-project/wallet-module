@extends('wallet::layouts.app')

@section('title', 'Edit Kategori')

@use('Modules\Wallet\Enums\CategoryType')
@use('Modules\Wallet\Helpers\Helper')

@push('styles')
<style>
    .category-type-card {
        border: 2px solid transparent;
        transition: all 0.3s ease;
        cursor: pointer;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .category-type-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .category-type-card.selected {
        border-color: var(--bs-primary);
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }
    
    .category-type-card.income.selected {
        border-color: var(--bs-success);
        background-color: rgba(var(--bs-success-rgb), 0.05);
    }
    
    .category-type-card.expense.selected {
        border-color: var(--bs-danger);
        background-color: rgba(var(--bs-danger-rgb), 0.05);
    }
    
    .type-icon {
        width: 70px;
        height: 70px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    
    .type-icon-income {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }
    
    .type-icon-expense {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .icon-preview {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        margin-right: 10px;
    }
    
    .icon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 10px;
        max-height: 200px;
        overflow-y: auto;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background-color: #f8f9fa;
    }
    
    .icon-item {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        background-color: white;
        border: 2px solid transparent;
    }
    
    .icon-item:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
    }
    
    .icon-item.selected {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        border-color: var(--bs-primary);
        color: var(--bs-primary);
    }
    
    .form-section {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e9ecef;
    }
    
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--bs-primary);
        color: var(--bs-primary);
    }
    
    .form-section-title.income {
        border-bottom-color: var(--bs-success);
        color: var(--bs-success);
    }
    
    .form-section-title.expense {
        border-bottom-color: var(--bs-danger);
        color: var(--bs-danger);
    }
    
    /* Statistics cards */
    .stat-card {
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .stat-card .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .stat-card.budget {
        background-color: rgba(13, 110, 253, 0.1);
        border-left: 4px solid #0d6efd;
    }
    
    .stat-card.transactions {
        background-color: rgba(108, 117, 125, 0.1);
        border-left: 4px solid #6c757d;
    }
    
    .stat-card.usage {
        background-color: rgba(40, 167, 69, 0.1);
        border-left: 4px solid #28a745;
    }
    
    .stat-card.usage.warning {
        background-color: rgba(255, 193, 7, 0.1);
        border-left: 4px solid #ffc107;
    }
    
    .stat-card.usage.danger {
        background-color: rgba(220, 53, 69, 0.1);
        border-left: 4px solid #dc3545;
    }
    
    /* Dark mode adjustments */
    body[data-bs-theme="dark"] .form-section {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    body[data-bs-theme="dark"] .icon-grid {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    body[data-bs-theme="dark"] .icon-item {
        background-color: #374151;
        border-color: #4b5563;
    }
    
    body[data-bs-theme="dark"] .icon-item:hover {
        background-color: #4b5563;
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="row mb-4">
  <div class="col">
    <h2 class="page-title mb-2">
      <i class="bi bi-pencil-square me-2"></i>Edit Kategori
    </h2>
  </div>
  <div class="col-auto">
    <div class="btn-group">
      <a href="{{ route('apps.categories.show', $category) }}" class="btn btn-outline-info">
        <i class="bi bi-eye me-1"></i>Detail
      </a>
      <a href="{{ route('apps.categories.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
      </a>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8">
    <!-- Statistics Cards -->
    @if($category->type === CategoryType::EXPENSE)
      <div class="row mb-4">
        <div class="col-md-4 mb-3">
          <div class="stat-card budget">
            <div class="stat-value">
              {{ Helper::formatMoney(Helper::toMoney($category->current_budget->amount ?? 0)->getAmount()->toInt()) }}
            </div>
            <div class="stat-label">Budget Bulan Ini</div>
            <small class="text-muted">
              @if($category->current_budget)
              Sampai {{ \Carbon\Carbon::parse($category->current_budget->end_date)->format('d M') }}
              @else
              Tidak ada budget
              @endif
            </small>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <div class="stat-card transactions">
            <div class="stat-value">
              {{ $category->transactions_count ?? 0 }}
            </div>
            <div class="stat-label">Total Transaksi</div>
            <small class="text-muted">
              {{ Helper::toMoney($category->transactions_sum_amount ?? 0)->getAmount()->toInt() }} total
            </small>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          @php
            $usageClass = '';
            if ($category->has_budget_exceeded ?? false) {
              $usageClass = 'danger';
            } elseif (($category->budget_usage_percentage ?? 0) >= 80) {
              $usageClass = 'warning';
            } else {
              $usageClass = '';
            }
          @endphp
          <div class="stat-card usage {{ $usageClass }}">
            <div class="stat-value">
              {{ number_format($category->budget_usage_percentage ?? 0, 1) }}%
            </div>
            <div class="stat-label">Penggunaan Budget</div>
            <small class="text-muted">
              {{ Helper::toMoney($category->current_spent ?? 0)->getAmount()->toInt() }} terpakai
            </small>
          </div>
        </div>
      </div>
    @endif

    <!-- Main Form -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-pencil-square me-2"></i>Form Edit Kategori
        </h5>
      </div>
      <div class="card-body">
        <form action="{{ route('apps.categories.update', $category) }}" method="POST" id="editCategoryForm">
          @csrf
          @method('PUT')

          <!-- Basic Information -->
          <div class="form-section">
            <h6 class="form-section-title {{ $category->type === CategoryType::INCOME ? 'income' : 'expense' }}">
              Informasi Kategori {{ $category->type === CategoryType::INCOME ? 'Pemasukan' : 'Pengeluaran' }}
            </h6>

            <div class="row g-3">
              <div class="col-md-8">
                <label for="name" class="form-label">
                  <i class="bi bi-tag me-1"></i>Nama Kategori
                  <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Makanan, Transportasi, Gaji" value="{{ old('name', $category->name) }}" required>
                <div class="form-text">
                  Berikan nama yang jelas dan mudah diingat.
                </div>
                @error('name')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-4">
                <label for="slug" class="form-label">
                  <i class="bi bi-link me-1"></i>Slug URL
                </label>
                <input type="text" class="form-control" id="slug" name="slug" placeholder="otomatis-terisi" value="{{ $category->slug }}" readonly disabled>
                <div class="form-text">
                  URL-friendly identifier (Auto-generate).
                </div>
                @error('slug')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12">
                <label for="description" class="form-label">
                  <i class="bi bi-text-paragraph me-1"></i>Deskripsi
                </label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Deskripsi singkat tentang kategori ini...">{{ old('description', $category->description) }}</textarea>
                <div class="form-text">
                  Deskripsi opsional untuk memberikan detail tambahan.
                </div>
              </div>
            </div>
          </div>

          <!-- Icon Selection -->
          <div class="form-section">
            <h6 class="form-section-title">Ikon Kategori</h6>
            <p class="text-muted mb-3">Pilih ikon yang mewakili kategori Anda.</p>
                            
            @include('wallet::partials.categories.icon')
          </div>

          <!-- Additional Settings -->
          <div class="form-section">
            <h6 class="form-section-title">Pengaturan Tambahan</h6>
                            
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                  <label class="form-check-label" for="is_active">
                    <i class="bi bi-toggle-on me-1"></i>Kategori Aktif
                  </label>
                  <div class="form-text">
                    Nonaktifkan untuk menyembunyikan kategori dari daftar.
                  </div>
                </div>
              </div>
                                
              <div class="col-md-6">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="is_budgetable" name="is_budgetable" value="1" @checked(old('is_budgetable', $category->is_budgetable)) @disabled(!$category->is_budgetable)>
                  <label class="form-check-label" for="is_budgetable">
                    <i class="bi bi-cash-coin me-1"></i>{{ $category->is_budgetable ? 'Dapat' : 'Tidak dapat'}} Diberi Budget
                  </label>
                  <div class="form-text">
                    Izinkan pengaturan budget untuk kategori ini.
                  </div>
                </div>
              </div>

              @if($category->type === CategoryType::EXPENSE && $category->current_budget)
                <div class="col-12 mt-3">
                  <div class="alert alert-info">
                    <div class="d-flex">
                      <div class="flex-shrink-0">
                        <i class="bi bi-info-circle"></i>
                      </div>
                      <div class="flex-grow-1 ms-3">
                        <h6 class="alert-heading">Budget Aktif</h6>
                        <p class="mb-0">
                          Kategori ini memiliki budget aktif sebesar 
                          <strong>{{ Helper::formatMoney(Helper::toMoney($category->current_budget->amount)->getAmount()->toInt()) }}</strong> 
                          hingga {{ \Carbon\Carbon::parse($category->current_budget->end_date)->format('d M Y') }}.
                        </p>
                        <div class="mt-2">
                          <a href="{{ route('wallet.budgets.edit', $category->current_budget) }}" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-pencil me-1"></i>Edit Budget
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endif
            </div>
          </div>

          <!-- Danger Zone -->
          <div class="form-section border-danger">
            <h6 class="form-section-title text-danger">
              <i class="bi bi-exclamation-triangle me-1"></i>Zona Berbahaya
            </h6>
                            
            <div class="alert alert-danger">
              <div class="d-flex">
                <div class="flex-shrink-0">
                  <i class="bi bi-exclamation-octagon"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="alert-heading">Hapus Kategori</h6>
                  <p class="mb-2">
                    Menghapus kategori akan menghapus semua data terkait termasuk transaksi dan budget.
                    <strong class="d-block mt-1">Aksi ini tidak dapat dibatalkan!</strong>
                  </p>
                  @if($category->transactions_count > 0)
                    <div class="alert alert-warning mb-2">
                      <i class="bi bi-exclamation-triangle me-1"></i>
                      Kategori ini memiliki {{ $category->transactions_count }} transaksi.
                      Anda tidak dapat menghapus kategori yang memiliki transaksi.
                    </div>
                  @endif
                  <div class="mt-3">
                    @if($category->transactions_count === 0)
                      <button type="button" class="btn btn-danger" id="deleteCategoryBtn">
                        <i class="bi bi-trash me-1"></i>Hapus Kategori
                      </button>
                    @else
                      <button type="button" class="btn btn-danger" disabled>
                        <i class="bi bi-trash me-1"></i>Hapus Kategori
                      </button>
                      <small class="text-muted ms-2">(Tidak tersedia karena ada transaksi)</small>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <div>
              <a href="{{ route('apps.categories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Batal
              </a>
            </div>
            <div>
              <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="bi bi-check-circle me-1"></i>Update Kategori
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
        
  <!-- Sidebar -->
  <div class="col-lg-4">
    <!-- Category Info -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-info-circle me-2"></i>Informasi Kategori
        </h5>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="icon-preview" style="width: 60px; height: 60px; font-size: 1.75rem;">
            <i class="bi {{ $category->icon }}"></i>
          </div>
          <div class="ms-3">
            <h5 class="mb-1">{{ $category->name }}</h5>
            <div class="d-flex align-items-center">
              @if($category->type === CategoryType::INCOME)
                <span class="badge bg-success me-2">Pemasukan</span>
              @else
                <span class="badge bg-danger me-2">Pengeluaran</span>
              @endif
              @if($category->is_active)
                <span class="badge bg-success">Aktif</span>
              @else
                <span class="badge bg-secondary">Nonaktif</span>
              @endif
            </div>
          </div>
        </div>
                    
        @if($category->description)
          <div class="mb-3">
            <h6 class="fw-semibold">Deskripsi:</h6>
            <p class="text-muted mb-0">{{ $category->description }}</p>
          </div>
        @endif
                    
        <div class="row g-2">
          <div class="col-6">
            <div class="text-center p-2 bg-secondary rounded">
              <div class="fw-semibold">Dibuat</div>
              <small class="text-muted">{{ $category->created_at->format('d M Y') }}</small>
            </div>
          </div>
          <div class="col-6">
            <div class="text-center p-2 bg-secondary rounded">
              <div class="fw-semibold">Diupdate</div>
              <small class="text-muted">{{ $category->updated_at->format('d M Y') }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions -->
    @if($category->transactions_count > 0)
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>Transaksi Terbaru
          </h5>
          <a href="{{ route('apps.transactions.index', ['category_id' => $category->id]) }}" class="btn btn-sm btn-outline-primary">
            Lihat Semua
          </a>
        </div>
        <div class="card-body">
          <div class="list-group list-group-flush">
            @forelse($category->recentTransactions as $transaction)
              <div class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <div class="fw-semibold">{{ $transaction->description ?: 'Tanpa deskripsi' }}</div>
                    <small class="text-muted">{{ $transaction->transaction_date->format('d M') }}</small>
                  </div>
                  <div class="text-end">
                    <div class="fw-semibold {{ $category->type === CategoryType::INCOME ? 'text-success' : 'text-danger' }}">
                      {{ $category->type === CategoryType::INCOME ? '+' : '-' }}{{ Helper::formatMoney(Helper::toMoney($transaction->amount)->getAmount()->toInt()) }}
                    </div>
                    <small class="text-muted">{{ $transaction->account->name ?? '-' }}</small>
                  </div>
                </div>
              </div>
            @empty
              <div class="text-center py-3 text-muted">
                <i class="bi bi-receipt display-6"></i>
                <p class="mt-2 mb-0">Belum ada transaksi</p>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    @endif

    <!-- Quick Actions -->
    <div class="card mt-4">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-lightning me-2"></i>Aksi Cepat
        </h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="{{ route('apps.transactions.create', ['category_id' => $category->id]) }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi
          </a>

          @if($category->type === 'expense')
            <a href="{{ route('apps.budgets.create', ['category_id' => $category->id]) }}" class="btn btn-primary">
              <i class="bi bi-cash-coin me-2"></i>Buat Budget
            </a>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-danger">
        <h5 class="modal-title text-danger">
          <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Penghapusan
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <div class="icon-preview mx-auto bg-danger bg-opacity-10 text-danger" style="width: 80px; height: 80px; font-size: 2rem;">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
        </div>

        <h5 class="text-center mb-3">Hapus Kategori "{{ $category->name }}"?</h5>
        <div class="alert alert-danger">
          <div class="d-flex">
            <div class="flex-shrink-0">
              <i class="bi bi-exclamation-octagon"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <p class="mb-0">
                <strong>Perhatian:</strong> Aksi ini akan menghapus:
              </p>
              <ul class="mb-0 mt-2">
                <li>Kategori "{{ $category->name }}"</li>
                @if($category->budgets_count > 0)
                  <li>{{ $category->budgets_count }} budget terkait</li>
                @endif
              </ul>
              <p class="mb-0 mt-2">
                <strong class="text-danger">Aksi ini tidak dapat dibatalkan!</strong>
              </p>
            </div>
          </div>
        </div>

        <div class="form-check mt-3">
          <input class="form-check-input" type="checkbox" id="confirmDelete">
          <label class="form-check-label" for="confirmDelete">
            Saya mengerti dan ingin melanjutkan penghapusan
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <form method="POST" action="{{ route('apps.categories.destroy', $category) }}" id="deleteForm">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled>
            <i class="bi bi-trash me-1"></i>Hapus Permanen
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize
        setupEventListeners();
        
        function updateIconPreview(iconName) {
          const previewIcon = document.getElementById('previewIcon');
          const iconClass = iconName.startsWith('bi-') ? iconName : `bi-${iconName}`;
            previewIcon.className = `bi ${iconClass}`;
        }
        
        function setupEventListeners() {
            // Name input change for preview
            const nameInput = document.getElementById('name');
            if (nameInput) {
                nameInput.addEventListener('input', function() {
                    // Update preview
                    document.getElementById('previewName').textContent = this.value;
                    
                    // Auto-generate slug if empty
                    const slugInput = document.getElementById('slug');
                    if (slugInput && !slugInput.value) {
                        const slug = this.value.toLowerCase()
                            .replace(/[^\w\s]/gi, '')
                            .replace(/\s+/g, '-');
                        slugInput.value = slug;
                    }
                });
            }
            
            // Delete category button
            const deleteBtn = document.getElementById('deleteCategoryBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    modal.show();
                });
            }
            
            // Confirm delete checkbox
            const confirmCheckbox = document.getElementById('confirmDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            if (confirmCheckbox && confirmDeleteBtn) {
                confirmCheckbox.addEventListener('change', function() {
                    confirmDeleteBtn.disabled = !this.checked;
                });
            }
            
            // Form submission
            const editForm = document.getElementById('editCategoryForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    const submitBtn = document.getElementById('submitBtn');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';
                        submitBtn.disabled = true;
                    }
                });
            }
        }
        
        // Initialize icon preview
        // Icon input live update
        document.addEventListener('iconSelected', function(e) {
          updateIconPreview(e.detail.iconClass || 'bi-tag');
        });
        
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
            
            toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', function() {
                toastContainer.remove();
            });
        }
    });
</script>
@endpush