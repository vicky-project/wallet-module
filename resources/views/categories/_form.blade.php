@php
  // Determine if we're in edit mode
  $isEdit = isset($category);
  $action = $isEdit 
    ? route('apps.categories.update', $category)
    : route('apps.categories.store');
  $method = $isEdit ? 'PUT' : 'POST';
@endphp

@use('Modules\Wallet\Enums\CategoryType')
@use('Modules\Wallet\Helpers\Helper')

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
    <label for="icon" class="form-label">Icon Kategori</label>
    
    <div class="d-flex">
        <!-- Input Text -->
        <div class="flex-grow-1 me-2">
            <input type="text" 
                   class="form-control @error('icon') is-invalid @enderror" 
                   id="icon" 
                   name="icon" 
                   value="{{ old('icon', $category->icon ?? 'bi-tag') }}" 
                   placeholder="bi-cash-stack" 
                   readonly>
        </div>
        
        <!-- Tombol Dropdown -->
        <div class="dropdown">
            <button type="button" 
                    class="btn btn-outline-secondary dropdown-toggle" 
                    id="iconPickerButton" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false"
                    style="min-width: 60px;">
                <i id="selectedIconPreview" class="bi {{ $category->icon ?? 'bi-tag' }}"></i>
            </button>
            
            <!-- Simple Grid -->
            <div class="dropdown-menu p-2" style="width: 260px;height: 300px;overflow-y: scroll;">
                <div class="d-flex flex-wrap">
                    @foreach(Helper::categoriesIconList() as $icon)
                    <button type="button" 
                            class="btn btn-sm btn-outline-secondary m-1 icon-simple-option" 
                            data-icon="{{ $icon }}"
                            style="width: 40px; height: 40px;">
                        <i class="bi {{ $icon }}"></i>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    @error('icon')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const options = document.querySelectorAll('.icon-simple-option');
    const preview = document.getElementById('selectedIconPreview');
    const input = document.getElementById('icon');
    
    options.forEach(button => {
        button.addEventListener('click', function() {
            const iconClass = this.getAttribute('data-icon');
            preview.className = `bi ${iconClass}`;
            input.value = iconClass;
            
            // Close dropdown
            bootstrap.Dropdown.getInstance(document.getElementById('iconPickerButton')).hide();
        });
    });
});
</script>