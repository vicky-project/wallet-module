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
      {{-- Ganti bagian input icon lama dengan ini --}}
<div class="mb-3">
    <label for="icon" class="form-label">Icon</label>
    <div class="input-group">
        <!-- Tombol untuk membuka picker & preview ikon -->
        <button type="button" id="iconPickerButton" class="btn btn-outline-secondary" data-bs-toggle="dropdown" aria-expanded="false">
            <i id="selectedIconPreview" class="bi {{ $category->icon ?? 'bi-tag' }}"></i>
        </button>
        <!-- Input tersembunyi untuk menyimpan nilai (contoh: "bi-cash-stack") -->
        <input type="text" 
               class="form-control @error('icon') is-invalid @enderror" 
               id="icon" 
               name="icon" 
               value="{{ old('icon', $category->icon ?? '') }}" 
               readonly 
               placeholder="Klik untuk memilih icon">
        <!-- Kontainer untuk icon picker itu sendiri -->
        <div class="dropdown-menu p-3" id="iconPickerDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
            <div class="row" id="iconGrid">
                <!-- Grid ikon akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>
    @error('icon')
        <div class="invalid-feedback">{{ $message }}</div>
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

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const iconGrid = document.getElementById('iconGrid');
    const selectedIconPreview = document.getElementById('selectedIconPreview');
    const iconInput = document.getElementById('icon');
    const dropdown = new bootstrap.Dropdown(document.getElementById('iconPickerButton'));

    // Daftar ikon Bootstrap yang relevan untuk aplikasi keuangan
    // Kamu dapat menambah atau mengurangi dari daftar ini
    const financeIcons = [
        'bi-cash-stack', 'bi-wallet', 'bi-wallet2', 'bi-graph-up', 'bi-graph-down',
        'bi-piggy-bank', 'bi-coin', 'bi-cash-coin', 'bi-bank', 'bi-cart',
        'bi-cart-check', 'bi-cart-x', 'bi-bag', 'bi-bag-check', 'bi-bag-x',
        'bi-tag', 'bi-tags', 'bi-receipt', 'bi-receipt-cutoff',
        'bi-arrow-up-circle', 'bi-arrow-down-circle', 'bi-arrow-left-right',
        'bi-calendar', 'bi-calendar-check', 'bi-calendar-week',
        'bi-house', 'bi-house-door', 'bi-house-check',
        'bi-car-front', 'bi-fuel-pump', 'bi-train-front',
        'bi-egg-fried', 'bi-cup', 'bi-cup-straw',
        'bi-heart-pulse', 'bi-capsule', 'bi-hospital',
        'bi-phone', 'bi-wifi', 'bi-lightning-charge', 'bi-droplet',
        'bi-film', 'bi-music-note-beamed', 'bi-controller',
        'bi-book', 'bi-pencil', 'bi-laptop',
        'bi-gift', 'bi-balloon', 'bi-balloon-heart',
        'bi-gear', 'bi-tools', 'bi-shield-check'
    ];

    // Isi grid dengan ikon
    financeIcons.forEach(iconClass => {
        const col = document.createElement('div');
        col.className = 'col-3 text-center mb-3';
        
        const iconElement = document.createElement('i');
        iconElement.className = `bi ${iconClass} fs-3`;
        iconElement.style.cursor = 'pointer';
        
        col.appendChild(iconElement);
        iconGrid.appendChild(col);

        // Tambahkan event listener untuk memilih ikon
        iconElement.addEventListener('click', function() {
            const selectedClass = `bi ${iconClass}`;
            selectedIconPreview.className = selectedClass;
            iconInput.value = `bi-${iconClass}`; // Simpan nilai untuk form
            
            // Tutup dropdown setelah memilih
            dropdown.hide();
        });
    });
});
</script>
@endpush