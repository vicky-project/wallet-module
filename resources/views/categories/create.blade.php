@extends('wallet::layouts.app')

@section('title', isset($category) ? 'Edit Kategori' : 'Tambah Kategori')

@use('Modules\Wallet\Enums\CategoryType')

@section('content')
@include('wallet::partials.fab')
<div class="row justify-content-center">
  <div class="col-lg-10 col-xl-8">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center text-end">
          <a href="{{ route('apps.categories.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
          </a>
          <h4 class="mb-0 ms-auto">
            <i class="bi bi-{{ isset($category) ? 'pencil-square' : 'plus-circle' }} me-2"></i>
            {{ isset($category) ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
          </h4>
        </div>
      </div>
            
      <div class="card-body">
        <form action="{{ isset($category) ? route('apps.categories.update', $category) : route('apps.categories.store') }}" method="POST" id="categoryForm">
          @csrf
          @if(isset($category))
            @method('PUT')
          @endif
                    
          <div class="row">
            <!-- Left Column: Form Inputs -->
            <div class="col-md-8">
              <!-- Type Selection -->
              <div class="form-section">
                <div class="form-section-title">
                  <i class="bi bi-tag"></i>
                  Tipe Kategori
                </div>
                <div class="d-flex type-switch mb-4">
                  <div class="type-option expense {{ (!isset($category) && request('type') == CategoryType::EXPENSE->value) || (isset($category) && $category->type == CategoryType::EXPENSE) ? 'active' : '' }}" data-type="expense">
                    <i class="bi bi-arrow-up-right"></i>
                    <span>Expense</span>
                  </div>
                  <div class="type-option income {{ (!isset($category) && request('type') == CategoryType::INCOME->value) || (isset($category) && $category->type == CategoryType::INCOME) ? 'active' : '' }}" data-type="income">
                    <i class="bi bi-arrow-down-left"></i>
                    <span>Income</span>
                  </div>
                </div>
                <input type="hidden" name="type" id="categoryType" value="{{ isset($category) ? $category->type : (request('type') ?: CategoryType::EXPENSE->value) }}">
                <p class="text-muted mb-0">
                  <small>
                    @if(isset($category) && $category->type == CategoryType::EXPENSE)
                      <i class="bi bi-info-circle me-1"></i>Kategori pengeluaran digunakan untuk mencatat pengeluaran dan dapat memiliki budget.
                    @elseif(isset($category) && $category->type == CategoryType::INCOME)
                      <i class="bi bi-info-circle me-1"></i>Kategori pemasukan digunakan untuk mencatat pendapatan.
                    @else
                      <i class="bi bi-info-circle me-1"></i>Pilih tipe kategori sesuai dengan penggunaannya.
                    @endif
                  </small>
                </p>
              </div>
                            
              <!-- Basic Information -->
              <div class="form-section">
                <div class="form-section-title">
                  <i class="bi bi-card-text"></i>
                  Informasi Dasar
                </div>
                                
                <!-- Name -->
                <div class="mb-3">
                  <label for="name" class="form-label">
                    Nama Kategori <span class="text-danger">*</span>
                  </label>
                  <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name ?? '') }}" placeholder="Contoh: Makanan, Transportasi, Gaji" required>
                  @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <div class="form-text">
                    Berikan nama yang jelas dan deskriptif untuk kategori ini.
                  </div>
                </div>
                                
                <!-- Description -->
                <div class="mb-3">
                  <label for="description" class="form-label">Deskripsi (Opsional)</label>
                  <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Tambahkan deskripsi untuk kategori ini...">{{ old('description', $category->description ?? '') }}</textarea>
                  @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <div class="form-text">
                    Deskripsi membantu mengingat tujuan kategori ini.
                  </div>
                </div>
                                
                <!-- Slug (Auto-generated) -->
                @if(isset($category))
                  <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $category->slug ?? '') }}" placeholder="slug-otomatis" readonly>
                    @error('slug')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                      Identifier unik untuk kategori. Biarkan kosong untuk generate otomatis.
                    </div>
                  </div>
                @endif
              </div>
                            
              <!-- Icon Selection -->
              <div class="form-section">
                <div class="form-section-title">
                  <i class="bi bi-emoji-smile"></i>
                  Ikon Kategori
                </div>
                                
                @include('wallet::partials.categories.icon')
              </div>
                            
              <!-- Status Settings -->
              <div class="form-section">
                <div class="form-section-title">
                  <i class="bi bi-gear"></i>
                  Pengaturan
                </div>
                <div class="row">
                  <!-- Status -->
                  <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', isset($category) ? $category->is_active : true))>
                      <label class="form-check-label" for="is_active">
                        <strong>Aktif</strong>
                      </label>
                    </div>
                    <div class="form-text">
                      Kategori nonaktif tidak akan muncul di daftar pilihan transaksi.
                    </div>
                  </div>
                                    
                  <!-- Budgetable (Only for expense) -->
                  <div class="col-md-6 mb-3">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="is_budgetable" name="is_budgetable" value="1" @checked(old('is_budgetable', isset($category) ? $category->is_budgetable : false))>
                      <label class="form-check-label" for="is_budgetable">
                        <strong>Dapat di-budget</strong>
                      </label>
                    </div>
                    <div class="form-text">
                      Izinkan pembuatan budget untuk kategori ini (hanya pengeluaran).
                    </div>
                  </div>
                </div>
              </div>
            </div>
                        
            <!-- Right Column: Preview -->
            <div class="col-md-4">
              <div class="sticky-top" style="top: 20px;">
                <div class="preview-card">
                  <h5 class="text-center mb-3">Pratinjau Kategori</h5>
                                    
                  <!-- Preview Icon -->
                  <div id="previewIconLarge" class="preview-icon" style="background-color: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary);">
                    <i class="bi bi-{{ old('icon', $category->icon ?? 'tag') }}"></i>
                  </div>
                                    
                  <!-- Preview Details -->
                  <div class="text-center mb-3">
                    <h4 id="previewName">{{ old('name', $category->name ?? 'Nama Kategori') }}</h4>
                    <div class="mb-2">
                      <span id="previewTypeBadge" class="badge {{ (isset($category) && $category->type == CategoryType::INCOME) || (!isset($category) && request('type') == CategoryType::INCOME->value) ? 'bg-success' : 'bg-danger' }}">
                        <i class="bi bi-{{ (isset($category) && $category->type == CategoryType::INCOME) || (!isset($category) && request('type') == CategoryType::INCOME->value) ? 'arrow-down-left' : 'arrow-up-right' }} me-1"></i>
                        <span id="previewTypeText">{{ (isset($category) && $category->type == CategoryType::INCOME) || (!isset($category) && request('type') == CategoryType::INCOME->value) ? 'Pemasukan' : 'Pengeluaran' }}</span>
                      </span>
                    </div>
                    <p class="text-muted mb-0" id="previewDescription">
                      {{ old('description', $category->description ?? 'Deskripsi akan muncul di sini...') ?: 'Deskripsi akan muncul di sini...' }}
                    </p>
                  </div>
                                    
                  <hr>
                                    
                  <!-- Status Preview -->
                  <div class="d-flex justify-content-between mb-2">
                    <span>Status:</span>
                    <span>
                      <span id="previewStatus" class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i>Aktif
                      </span>
                    </span>
                  </div>
                                    
                  <div class="d-flex justify-content-between">
                    <span>Budgetable:</span>
                    <span>
                      <span id="previewBudgetable" class="badge bg-secondary">
                        <i class="bi bi-x-circle me-1"></i>Tidak
                      </span>
                    </span>
                  </div>
                </div>
                                
                <!-- Form Actions -->
                <div class="d-grid gap-2 mt-3">
                  <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-{{ isset($category) ? 'check-circle' : 'plus-circle' }} me-2"></i>
                    {{ isset($category) ? 'Update Kategori' : 'Simpan Kategori' }}
                  </button>
                  <a href="{{ route('apps.categories.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Batal
                  </a>
                </div>
                                
                <!-- Quick Tips -->
                <div class="alert alert-info mt-3">
                  <div class="d-flex">
                    <div class="flex-shrink-0">
                      <i class="bi bi-lightbulb"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                      <small>
                        <strong>Tips:</strong><br>
                                                • Gunakan nama yang jelas dan deskriptif<br>
                                                • Pilih ikon yang mudah dikenali<br>
                                                • Atur budget untuk kategori pengeluaran penting
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const categoryTypeInput = document.getElementById('categoryType');
        const typeOptions = document.querySelectorAll('.type-option');
        const nameInput = document.getElementById('name');
        const descriptionInput = document.getElementById('description');
        const iconInput = document.getElementById('icon');
        const isActiveCheckbox = document.getElementById('is_active');
        const isBudgetableCheckbox = document.getElementById('is_budgetable');
        
        // Preview elements
        const previewIcon = document.getElementById('selectedIconPreview');
        const previewIconLarge = document.getElementById('previewIconLarge');
        const previewName = document.getElementById('previewName');
        const previewDescription = document.getElementById('previewDescription');
        const previewTypeBadge = document.getElementById('previewTypeBadge');
        const previewTypeText = document.getElementById('previewTypeText');
        const previewStatus = document.getElementById('previewStatus');
        const previewBudgetable = document.getElementById('previewBudgetable');
        
        // Type selection
        typeOptions.forEach(option => {
            option.addEventListener('click', function() {
                const selectedType = this.dataset.type;
                
                // Update active class
                typeOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                // Update hidden input
                categoryTypeInput.value = selectedType;
                
                // Update preview
                updateTypePreview(selectedType);
                
                if (selectedType === '{{ CategoryType::INCOME->value }}') {
                // Disable budgetable for income
                    isBudgetableCheckbox.checked = false;
                    isBudgetableCheckbox.disabled = true;
                    updateBudgetablePreview(false);
                } else {
                    isBudgetableCheckbox.disabled = false;
                }
            });
        });
        
        // Name input live update
        nameInput.addEventListener('input', function() {
            previewName.textContent = this.value || 'Nama Kategori';
        });
        
        // Description input live update
        descriptionInput.addEventListener('input', function() {
            previewDescription.textContent = this.value || 'Deskripsi akan muncul di sini...';
        });
        
        // Icon input live update
        document.addEventListener('iconSelected', function(e) {
          updateIconPreview(e.detail.iconClass || 'bi-tag');
        });
        
        // Status checkbox live update
        isActiveCheckbox.addEventListener('change', function() {
            updateStatusPreview(this.checked);
        });
        
        // Budgetable checkbox live update
        isBudgetableCheckbox.addEventListener('change', function() {
            updateBudgetablePreview(this.checked);
        });
        
        // Initialize previews
        function initializePreviews() {
            const currentType = categoryTypeInput.value;
            updateTypePreview(currentType);
            updateIconPreview(iconInput.value || 'tag');
            updateStatusPreview(isActiveCheckbox.checked);
            updateBudgetablePreview(isBudgetableCheckbox.checked);
            
            // Disable budgetable for income
            if (currentType === 'income') {
                isBudgetableCheckbox.disabled = true;
            }
        }
        
        // Update type preview
        function updateTypePreview(type) {
            if (type === 'income') {
                previewTypeBadge.className = 'badge bg-success';
                previewTypeBadge.innerHTML = '<i class="bi bi-arrow-down-left me-1"></i>Pemasukan';
                previewTypeText.textContent = 'Pemasukan';
            } else {
                previewTypeBadge.className = 'badge bg-danger';
                previewTypeBadge.innerHTML = '<i class="bi bi-arrow-up-right me-1"></i>Pengeluaran';
                previewTypeText.textContent = 'Pengeluaran';
            }
        }
        
        // Update icon preview
        function updateIconPreview(iconName) {
            const iconClass = iconName.startsWith('bi-') ? iconName : `bi-${iconName}`;
            previewIcon.className = `bi ${iconClass}`;
            previewIconLarge.innerHTML = `<i class="bi ${iconClass}"></i>`;
        }
        
        // Update status preview
        function updateStatusPreview(isActive) {
            if (isActive) {
                previewStatus.className = 'badge bg-success';
                previewStatus.innerHTML = '<i class="bi bi-check-circle me-1"></i>Aktif';
            } else {
                previewStatus.className = 'badge bg-danger';
                previewStatus.innerHTML = '<i class="bi bi-x-circle me-1"></i>Nonaktif';
            }
        }
        
        // Update budgetable preview
        function updateBudgetablePreview(isBudgetable) {
            if (isBudgetable) {
                previewBudgetable.className = 'badge bg-primary';
                previewBudgetable.innerHTML = '<i class="bi bi-check-circle me-1"></i>Ya';
            } else {
                previewBudgetable.className = 'badge bg-secondary';
                previewBudgetable.innerHTML = '<i class="bi bi-x-circle me-1"></i>Tidak';
            }
        }
        
        // Form validation
        const form = document.getElementById('categoryForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Clear previous errors
                document.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
                
                // Validate name
                if (!nameInput.value.trim()) {
                    e.preventDefault();
                    nameInput.classList.add('is-invalid');
                    nameInput.focus();
                    
                    // Show toast error
                    showToast('danger', 'Error', 'Nama kategori wajib diisi');
                    return false;
                }
                
                // Validate icon
                if (!iconInput.value.trim()) {
                    iconInput.value = 'tag';
                }
                
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';
                submitBtn.disabled = true;
                
                // Allow form submission
                return true;
            });
        }
        
        // Initialize
        initializePreviews();
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
        
        toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', function () {
            toastContainer.remove();
        });
    }
</script>
@endpush

@push('styles')
<style>
    /* Custom styling for category form */
    .icon-preview {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        transition: all 0.3s ease;
    }
    
    .icon-preview:hover {
        transform: scale(1.05);
    }
    
    .icon-selector {
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .icon-selector:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .icon-selector.selected {
        border: 2px solid var(--bs-primary);
        background-color: rgba(var(--bs-primary-rgb), 0.1);
    }
    
    .type-switch {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #dee2e6;
    }
    
    .type-option {
        padding: 12px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .type-option.active {
        background-color: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
    }
    
    .type-option.expense {
        border-right: 1px solid #dee2e6;
    }
    
    .type-option.active.expense {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    .type-option.active.income {
        background-color: #198754;
        border-color: #198754;
    }
    
    .form-section {
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #dee2e6;
    }
    
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .preview-card {
        border-radius: 12px;
        padding: 1.5rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6;
    }
    
    .preview-icon {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin: 0 auto 1rem;
    }
    
    /* Dark theme adjustments */
    body[data-bs-theme="dark"] .form-section {
        border-color: #495057;
        background-color: rgba(33, 37, 41, 0.5);
    }
    
    body[data-bs-theme="dark"] .preview-card {
        background: linear-gradient(135deg, #2d3338 0%, #212529 100%);
        border-color: #495057;
    }
    
    body[data-bs-theme="dark"] .type-switch {
        border-color: #495057;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .icon-preview {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
        }
        
        .preview-icon {
            width: 60px;
            height: 60px;
            font-size: 2rem;
        }
    }
</style>
@endpush