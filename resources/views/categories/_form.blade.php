@php
  // Determine if we're in edit mode
  $isEdit = isset($category);
  $action = $isEdit 
    ? route('apps.categories.update', $category)
    : route('apps.categories.store');
  $method = $isEdit ? 'PUT' : 'POST';
@endphp

@use('Modules\Wallet\Enums\CategoryType')

<form action="{{ $action }}" method="POST" id="categoryForm">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="mb-3">
    <label for="name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name ?? '') }}" placeholder="Contoh: Belanja, Gaji, Transportasi" required>
    @error('name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="type" class="form-label">Tipe <span class="text-danger">*</span></label>
    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
      <option value="">Pilih tipe...</option>
      @foreach (CategoryType::cases() as $type)
      <option value="{{ $type->value}}" @selected(old('type', $category->type ?? '') == $type->value)>{{ $type->name }}</option>
      @endforeach
    </select>
    @error('type')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="row">
    <div class="col-md-6">
<div class="mb-4">
    <label for="icon" class="form-label">Icon Kategori <span class="text-danger">*</span></label>
    
    <!-- Selected Icon Preview -->
    <div class="d-flex align-items-center mb-3 p-3 border rounded bg-light">
        <div class="me-3">
            <div class="icon-preview-large" style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i id="selectedIconPreview" class="bi {{ $category->icon ?? 'bi-tag' }} text-white fs-3"></i>
            </div>
        </div>
        <div class="flex-grow-1">
            <h6 class="mb-1">Icon Terpilih</h6>
            <p class="text-muted mb-0" id="selectedIconName">{{ $category->icon ?? 'bi-tag' }}</p>
        </div>
        <button type="button" id="iconPickerButton" class="btn btn-primary" data-bs-toggle="dropdown">
            <i class="bi bi-palette me-2"></i>Pilih Icon
        </button>
    </div>
    
    <!-- Icon Picker Dropdown -->
    <div class="dropdown-menu p-3 shadow-lg" id="iconPickerDropdown" style="width: 500px; max-width: 90vw;">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0"><i class="bi bi-palette me-2"></i>Pilih Icon</h6>
            <button type="button" class="btn-close" data-bs-dismiss="dropdown" aria-label="Close"></button>
        </div>
        
        <!-- Search Bar -->
        <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="iconSearch" placeholder="Cari icon...">
            <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                <i class="bi bi-x"></i>
            </button>
        </div>
        
        <!-- Popular Icons -->
        <div class="mb-3">
            <small class="text-muted d-block mb-2">Ikon Populer:</small>
            <div class="row g-2" id="popularIcons">
                <!-- Popular icons will be loaded here -->
            </div>
        </div>
        
        <!-- Category Filters -->
        <div class="mb-3">
            <small class="text-muted d-block mb-2">Kategori:</small>
            <div class="d-flex flex-wrap gap-1" id="iconCategoryFilters">
                <!-- Category buttons will be loaded here -->
            </div>
        </div>
        
        <!-- Icons Grid -->
        <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
            <div class="row g-2" id="iconGrid">
                <!-- Icons will be loaded here -->
            </div>
        </div>
        
        <!-- Hidden Input -->
        <input type="hidden" 
               class="form-control @error('icon') is-invalid @enderror" 
               id="icon" 
               name="icon" 
               value="{{ old('icon', $category->icon ?? 'bi-tag') }}" 
               required>
    </div>
    
    @error('icon')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    
    <small class="text-muted">Pilih icon yang merepresentasikan kategori keuangan Anda</small>
</div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label for="budget_limit" class="form-label">Batas Anggaran (Opsional)</label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="number" class="form-control @error('budget_limit') is-invalid @enderror" id="budget_limit" name="budget_limit" value="{{ old('budget_limit', $category->budget_limit ?? '') }}" placeholder="0" min="0" step="1000">
        </div>
        <small class="text-muted">Hanya untuk kategori pengeluaran. Biarkan kosong jika tidak ada batasan.</small>
        @error('budget_limit')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>

  <div class="mb-3">
    <div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
        {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
      <label class="form-check-label" for="is_active">
        Aktifkan kategori
      </label>
    </div>
  </div>

  @if($isEdit)
  <div class="alert alert-info">
    <i class="bi bi-info-circle"></i> 
    Kategori yang sudah digunakan dalam transaksi tidak dapat dihapus, hanya dapat dinonaktifkan.
  </div>
  @endif
</form>

@push('styles')
<style>
    .dropdown-menu#iconPickerDropdown {
        z-index: 1060;
    }
    
    #iconGrid .col-2 {
        padding: 8px;
    }
    
    #iconGrid .col-2:hover {
        background-color: rgba(67, 97, 238, 0.1);
        border-radius: 6px;
    }
    
    #iconCategoryFilters .btn.active {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    #popularIcons .col {
        padding: 10px;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    #popularIcons .col:hover {
        background-color: rgba(67, 97, 238, 0.1);
        transform: scale(1.1);
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/category-icon-picker.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    new CategoryIconPicker();
  });
  

</script>
@endpush