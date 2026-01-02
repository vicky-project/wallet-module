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
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name ?? '') }}" placeholder="Contoh: Belanja, Gaji, Transportasi" required oninput="updateIconPreview(this.value, document.getElementById('type').value)">
    @error('name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="type" class="form-label">Tipe <span class="text-danger">*</span></label>
    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required onchange="updateIconPreview(document.getElementById('name').value, this.value)">
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
      <div class="mb-3">
        <label for="icon" class="form-label">Icon</label>
        <div class="input-group">
          <span class="input-group-text">
            <i id="iconPreview" class="bi {{ $category->icon ?? 'bi-tag' }}"></i>
          </span>
          <select class="form-select @error('icon') is-invalid @enderror" id="icon" name="icon">
            <option value="">Pilih icon atau biarkan otomatis</option>
              <optgroup label="Pemasukan">
                <option value="bi-cash-stack" {{ old('icon', $category->icon ?? '') == 'bi-cash-stack' ? 'selected' : '' }}>Cash Stack</option>
                <option value="bi-graph-up" {{ old('icon', $category->icon ?? '') == 'bi-graph-up' ? 'selected' : '' }}>Graph Up</option>
                <option value="bi-laptop" {{ old('icon', $category->icon ?? '') == 'bi-laptop' ? 'selected' : '' }}>Laptop</option>
                <option value="bi-gift" {{ old('icon', $category->icon ?? '') == 'bi-gift' ? 'selected' : '' }}>Gift</option>
                <option value="bi-wallet" {{ old('icon', $category->icon ?? '') == 'bi-wallet' ? 'selected' : '' }}>Wallet</option>
              </optgroup>
              <optgroup label="Pengeluaran">
                <option value="bi-egg-fried" {{ old('icon', $category->icon ?? '') == 'bi-egg-fried' ? 'selected' : '' }}>Makanan</option>
                <option value="bi-car-front" {{ old('icon', $category->icon ?? '') == 'bi-car-front' ? 'selected' : '' }}>Transportasi</option>
                <option value="bi-film" {{ old('icon', $category->icon ?? '') == 'bi-film' ? 'selected' : '' }}>Hiburan</option>
                <option value="bi-cart" {{ old('icon', $category->icon ?? '') == 'bi-cart' ? 'selected' : '' }}>Belanja</option>
                <option value="bi-heart-pulse" {{ old('icon', $category->icon ?? '') == 'bi-heart-pulse' ? 'selected' : '' }}>Kesehatan</option>
                <option value="bi-book" {{ old('icon', $category->icon ?? '') == 'bi-book' ? 'selected' : '' }}>Pendidikan</option>
                <option value="bi-lightning-charge" {{ old('icon', $category->icon ?? '') == 'bi-lightning-charge' ? 'selected' : '' }}>Utilitas</option>
                <option value="bi-wallet2" {{ old('icon', $category->icon ?? '') == 'bi-wallet2' ? 'selected' : '' }}>Wallet 2</option>
              </optgroup>
            </select>
          </div>
          <small class="text-muted">Biarkan kosong untuk menggunakan icon otomatis berdasarkan nama kategori</small>
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

<script>
function updateIconPreview(name, type) {
    const iconPreview = document.getElementById('iconPreview');
    const iconSelect = document.getElementById('icon');
    
    if (!name || !type) return;
    
    const lowerName = name.toLowerCase();
    
    const defaultIcons = {
        income: {
            'gaji': 'bi-cash-stack',
            'investasi': 'bi-graph-up',
            'freelance': 'bi-laptop',
            'hibah': 'bi-gift',
            'bonus': 'bi-gift',
            'upah': 'bi-cash-stack',
        },
        expense: {
            'makan': 'bi-egg-fried',
            'makanan': 'bi-egg-fried',
            'restoran': 'bi-egg-fried',
            'transport': 'bi-car-front',
            'transportasi': 'bi-car-front',
            'bensin': 'bi-fuel-pump',
            'hiburan': 'bi-film',
            'nonton': 'bi-film',
            'belanja': 'bi-cart',
            'supermarket': 'bi-cart',
            'kesehatan': 'bi-heart-pulse',
            'obat': 'bi-heart-pulse',
            'dokter': 'bi-heart-pulse',
            'pendidikan': 'bi-book',
            'sekolah': 'bi-book',
            'kuliah': 'bi-book',
            'listrik': 'bi-lightning-charge',
            'air': 'bi-droplet',
            'internet': 'bi-wifi',
            'telepon': 'bi-phone',
        }
    };
    
    let matchedIcon = type === 'income' ? 'bi-cash-stack' : 'bi-wallet2';
    const typeIcons = defaultIcons[type] || {};
    
    for (const [key, icon] of Object.entries(typeIcons)) {
        if (lowerName.includes(key)) {
            matchedIcon = icon;
            break;
        }
    }
    
    iconPreview.className = 'bi ' + matchedIcon;
    
    if (!iconSelect.value) {
        iconSelect.value = matchedIcon;
    }
}

// Initialize icon preview on page load
document.addEventListener('DOMContentLoaded', function() {
    const name = document.getElementById('name')?.value;
    const type = document.getElementById('type')?.value;
    
    if (name && type) {
        updateIconPreview(name, type);
    }
});
</script>