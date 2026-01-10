@extends('wallet::layouts.app')

@section('title', $type === 'income' ? 'Tambah Kategori Pemasukan' : 'Tambah Kategori Pengeluaran')

@push('styles')
<style>
    .form-card {
        border-radius: 12px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .form-header {
        background: linear-gradient(135deg, {{ $type === 'income' ? 'var(--bs-success)' : 'var(--bs-danger)' }} 0%, {{ $type === 'income' ? '#0d6c3e' : '#a71d2a' }} 100%);
        color: white;
        padding: 2rem;
    }
    
    .form-header-icon {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        background-color: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    
    .icon-preview {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        transition: all 0.3s ease;
    }
    
    .icon-preview:hover {
        transform: scale(1.05);
    }
    
    .icon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 10px;
        max-height: 200px;
        overflow-y: auto;
        padding: 10px;
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
    }
    
    .icon-item {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 1.2rem;
    }
    
    .icon-item:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        transform: scale(1.1);
    }
    
    .icon-item.selected {
        background-color: var(--bs-primary);
        color: white;
        box-shadow: 0 4px 8px rgba(var(--bs-primary-rgb), 0.3);
    }
    
    .switch-card {
        border-left: 4px solid;
        transition: all 0.3s;
    }
    
    .switch-card.active {
        border-left-color: var(--bs-success);
        background-color: rgba(var(--bs-success-rgb), 0.05);
    }
    
    .switch-card.inactive {
        border-left-color: var(--bs-secondary);
        background-color: rgba(var(--bs-secondary-rgb), 0.05);
    }
    
    .type-card {
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    
    .type-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .type-card.selected {
        border-color: {{ $type === 'income' ? 'var(--bs-success)' : 'var(--bs-danger)' }};
        background-color: {{ $type === 'income' ? 'rgba(var(--bs-success-rgb), 0.05)' : 'rgba(var(--bs-danger-rgb), 0.05)' }};
    }
    
    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--bs-border-color);
    }
    
    .form-section-title {
        font-weight: 600;
        color: var(--bs-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    /* Dark mode adjustments */
    body[data-bs-theme="dark"] .form-card {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    body[data-bs-theme="dark"] .icon-grid {
        border-color: #495057;
        background-color: rgba(0, 0, 0, 0.2);
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card form-card">
      <div class="form-header">
                <div class="d-flex align-items-center">
                    <div class="form-header-icon">
                        <i class="bi bi-{{ $type === 'income' ? 'arrow-down-left' : 'arrow-up-right' }}"></i>
                    </div>
                    <div class="ms-3">
                        <h2 class="mb-1">
                            {{ $type === 'income' ? 'Tambah Kategori Pemasukan' : 'Tambah Kategori Pengeluaran' }}
                        </h2>
                        <p class="mb-0 opacity-75">
                            {{ $type === 'income' ? 'Kategori untuk mencatat sumber pendapatan dan pemasukan' : 'Kategori untuk mengelompokkan pengeluaran dan belanja' }}
                        </p>
                    </div>
                </div>
      </div>
            
      <div class="card-body p-4">
        <form method="POST" action="{{ route('wallet.categories.store') }}" id="categoryForm">
          @csrf
                    
          <input type="hidden" name="type" id="categoryType" value="{{ $type }}">
                    
          <!-- Type Selection (Quick Switch) -->
          <div class="form-section">
            <h5 class="form-section-title">
              <i class="bi bi-diagram-3"></i> Tipe Kategori
            </h5>
            <div class="row">
              <div class="col-md-6">
                <div class="card type-card {{ $type === 'expense' ? 'selected' : '' }}" onclick="selectType('expense')">
                  <div class="card-body text-center py-4">
                    <div class="mb-3">
                      <div class="icon-preview mx-auto" style="background-color: rgba(var(--bs-danger-rgb), 0.1); color: var(--bs-danger);">
                        <i class="bi bi-arrow-up-right"></i>
                      </div>
                    </div>
                    <h5 class="card-title mb-2">Pengeluaran</h5>
                    <p class="card-text text-muted small mb-0">
                      Untuk mencatat pengeluaran, belanja, dan biaya
                    </p>
                    <div class="mt-3">
                      <span class="badge bg-danger">Debit</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="card type-card {{ $type === 'income' ? 'selected' : '' }}" onclick="selectType('income')">
                    <div class="card-body text-center py-4">
                      <div class="mb-3">
                        <div class="icon-preview mx-auto" style="background-color: rgba(var(--bs-success-rgb), 0.1); color: var(--bs-success);">
                          <i class="bi bi-arrow-down-left"></i>
                        </div>
                      </div>
                      <h5 class="card-title mb-2">Pemasukan</h5>
                      <p class="card-text text-muted small mb-0">
                        Untuk mencatat pendapatan, penghasilan, dan pemasukan
                      </p>
                      <div class="mt-3">
                        <span class="badge bg-success">Kredit</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
                    
            <!-- Basic Information -->
            <div class="form-section">
              <h5 class="form-section-title">
                <i class="bi bi-info-circle"></i> Informasi Dasar
              </h5>
              <div class="row">
                <div class="col-md-8">
                  <div class="mb-3">
                    <label for="name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: {{ $type === 'income' ? 'Gaji Bulanan' : 'Belanja Bulanan' }}" required>
                    <div class="form-text">
                      Berikan nama yang jelas dan mudah diingat untuk kategori ini
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label">Ikon</label>
                                    <div class="d-flex align-items-center">
                                        <div class="icon-preview me-3" id="iconPreview">
                                            <i class="bi bi-tag"></i>
                                        </div>
                                        <input type="hidden" id="icon" name="icon" value="tag">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#iconModal">
                                            <i class="bi bi-palette"></i> Pilih Ikon
                                        </button>
                    </div>
                  </div>
                </div>
              </div>
                        
              <div class="mb-3">
                <label for="description" class="form-label">Deskripsi (Opsional)</label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Tambahkan deskripsi untuk kategori ini...">{{ old('description') }}</textarea>
                <div class="form-text">
                  Deskripsi membantu memahami tujuan kategori ini
                </div>
              </div>
            </div>
                    
            <!-- Additional Settings -->
            <div class="form-section">
              <h5 class="form-section-title">
                <i class="bi bi-gear"></i> Pengaturan Tambahan
              </h5>
                        
              <div class="row">
                <div class="col-md-6">
                  <div class="card switch-card {{ old('is_active', true) ? 'active' : 'inactive' }}" onclick="toggleActive()" style="cursor: pointer;">
                    <div class="card-body">
                      <div class="d-flex align-items-center">
                        <div class="me-3">
                          <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                          </div>
                        </div>
                        <div>
                          <h6 class="mb-1">Status Aktif</h6>
                          <p class="text-muted mb-0 small">
                            Kategori {{ old('is_active', true) ? 'aktif' : 'tidak aktif' }} untuk transaksi baru
                          </p>
                        </div>
                        <div class="ms-auto">
                          <span class="badge bg-{{ old('is_active', true) ? 'success' : 'secondary' }}">
                            {{ old('is_active', true) ? 'Aktif' : 'Nonaktif' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                            
                <div class="col-md-6">
                  <div class="card switch-card {{ old('is_budgetable', false) ? 'active' : 'inactive' }}" onclick="toggleBudgetable()" style="cursor: pointer;">
                    <div class="card-body">
                      <div class="d-flex align-items-center">
                        <div class="me-3">
                          <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="is_budgetable" name="is_budgetable" {{ old('is_budgetable', false) ? 'checked' : '' }}>
                          </div>
                        </div>
                        <div>
                          <h6 class="mb-1">Dapat Dibudgetkan</h6>
                          <p class="text-muted mb-0 small">{{ old('is_budgetable', false) ? 'Bisa' : 'Tidak bisa' }} menambahkan budget untuk kategori ini
                          </p>
                        </div>
                        <div class="ms-auto">
                          <span class="badge bg-{{ old('is_budgetable', false) ? 'primary' : 'secondary' }}">{{ old('is_budgetable', false) ? 'Ya' : 'Tidak' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
                        
              @if($type === 'expense')
                <div class="row mt-3">
                  <div class="col-12">
                    <div class="alert alert-info">
                      <div class="d-flex">
                        <div class="me-3">
                          <i class="bi bi-info-circle fs-4"></i>
                        </div>
                        <div>
                          <h6 class="alert-heading">Kategori Pengeluaran</h6>
                          <p class="mb-0 small">
                            Kategori pengeluaran dapat diberikan budget bulanan untuk membantu mengontrol pengeluaran. Aktifkan "Dapat Dibudgetkan" untuk menambahkan budget.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endif
            </div>
                    
            <!-- Form Actions -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
              <div>
                <a href="{{ route('wallet.categories.index') }}" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
              </div>
              <div>
                <button type="reset" class="btn btn-outline-danger me-2">
                  <i class="bi bi-arrow-clockwise me-1"></i> Reset
                </button>
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-save me-1"></i> Simpan Kategori
                </button>
              </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Icon Selection Modal -->
<div class="modal fade" id="iconModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-palette me-2"></i> Pilih Ikon Kategori
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="iconSearch" placeholder="Cari ikon...">
                </div>
                <div class="icon-grid" id="iconGrid">
                    @php
                        $categoryIcons = [
                            // Expense Icons
                            'cart', 'bag', 'cart-check', 'cart-plus', 'basket', 'basket2', 'basket3',
                            'cash', 'coin', 'credit-card', 'credit-card-2-front', 'currency-exchange',
                            'car-front', 'bus-front', 'train-front', 'airplane', 'fuel-pump',
                            'house', 'house-door', 'building', 'shop', 'hospital', 'bank',
                            'cup-hot', 'cup-straw', 'egg-fried', 'egg', 'utensils',
                            'phone', 'laptop', 'tv', 'headphones', 'camera',
                            'film', 'music-player', 'controller', 'dice-5',
                            'book', 'journal', 'newspaper', 'pencil', 'scissors',
                            'heart', 'heart-pulse', 'capsule', 'bandaid',
                            'tshirt', 'hat-cap', 'shoe-prints',
                            
                            // Income Icons
                            'cash-stack', 'piggy-bank', 'bank2', 'wallet', 'wallet2',
                            'graph-up', 'graph-up-arrow', 'arrow-up-right-circle',
                            'briefcase', 'briefcase-fill', 'person-workspace',
                            'building-check', 'building-up', 'house-check',
                            'gift', 'gift-fill', 'box-seam', 'box',
                            'arrow-repeat', 'arrow-left-right', 'arrow-down-up',
                            'hand-thumbs-up', 'hand-thumbs-up-fill',
                            'award', 'trophy', 'medal',
                            'lightbulb', 'lightbulb-fill', 'magic',
                            'code', 'code-slash', 'terminal',
                            'palette', 'palette2', 'brush',
                            'mic', 'mic-fill', 'mic-mute',
                            'camera-video', 'camera-video-fill'
                        ];
                    @endphp
                    
                    @foreach($categoryIcons as $icon)
                        <div class="icon-item" data-icon="{{ $icon }}" onclick="selectIcon('{{ $icon }}')">
                            <i class="bi bi-{{ $icon }}"></i>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Pilih Ikon</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set form title based on type
        const type = '{{ $type }}';
        updateFormHeader(type);
        
        // Type selection
        window.selectType = function(selectedType) {
            document.querySelectorAll('.type-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            document.querySelector(`.type-card.${selectedType === 'expense' ? 'selected' : ''}`).classList.add('selected');
            
            document.getElementById('categoryType').value = selectedType;
            updateFormHeader(selectedType);
            
            // Update placeholder based on type
            const nameInput = document.getElementById('name');
            nameInput.placeholder = selectedType === 'income' 
                ? 'Contoh: Gaji Bulanan, Bonus, Penjualan' 
                : 'Contoh: Belanja Bulanan, Transportasi, Makan';
        };
        
        function updateFormHeader(type) {
            const header = document.querySelector('.form-header');
            const icon = document.querySelector('.form-header-icon i');
            const title = document.querySelector('.form-header h2');
            const subtitle = document.querySelector('.form-header p');
            
            if (type === 'income') {
                header.style.background = 'linear-gradient(135deg, var(--bs-success) 0%, #0d6c3e 100%)';
                icon.className = 'bi bi-arrow-down-left';
                title.textContent = 'Tambah Kategori Pemasukan';
                subtitle.textContent = 'Kategori untuk mencatat sumber pendapatan dan pemasukan';
            } else {
                header.style.background = 'linear-gradient(135deg, var(--bs-danger) 0%, #a71d2a 100%)';
                icon.className = 'bi bi-arrow-up-right';
                title.textContent = 'Tambah Kategori Pengeluaran';
                subtitle.textContent = 'Kategori untuk mengelompokkan pengeluaran dan belanja';
            }
        }
        
        // Icon selection
        let selectedIcon = 'tag';
        
        window.selectIcon = function(icon) {
            selectedIcon = icon;
            
            // Update preview
            const preview = document.getElementById('iconPreview');
            preview.innerHTML = `<i class="bi bi-${icon}"></i>`;
            
            // Update hidden input
            document.getElementById('icon').value = icon;
            
            // Update selected state in grid
            document.querySelectorAll('.icon-item').forEach(item => {
                item.classList.remove('selected');
                if (item.dataset.icon === icon) {
                    item.classList.add('selected');
                }
            });
        };
        
        // Icon search functionality
        const iconSearch = document.getElementById('iconSearch');
        if (iconSearch) {
            iconSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const iconItems = document.querySelectorAll('.icon-item');
                
                iconItems.forEach(item => {
                    const iconName = item.dataset.icon.toLowerCase();
                    if (iconName.includes(searchTerm)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
        
        // Toggle active status
        window.toggleActive = function() {
            const checkbox = document.getElementById('is_active');
            const card = checkbox.closest('.switch-card');
            const badge = card.querySelector('.badge');
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                card.classList.remove('inactive');
                card.classList.add('active');
                badge.className = 'badge bg-success';
                badge.textContent = 'Aktif';
                card.querySelector('.text-muted').textContent = 'Kategori aktif untuk transaksi baru';
            } else {
                card.classList.remove('active');
                card.classList.add('inactive');
                badge.className = 'badge bg-secondary';
                badge.textContent = 'Nonaktif';
                card.querySelector('.text-muted').textContent = 'Kategori tidak aktif untuk transaksi baru';
            }
        };
        
        // Toggle budgetable
        window.toggleBudgetable = function() {
            const checkbox = document.getElementById('is_budgetable');
            const card = checkbox.closest('.switch-card');
            const badge = card.querySelector('.badge');
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                card.classList.remove('inactive');
                card.classList.add('active');
                badge.className = 'badge bg-primary';
                badge.textContent = 'Ya';
                card.querySelector('.text-muted').textContent = 'Bisa menambahkan budget untuk kategori ini';
            } else {
                card.classList.remove('active');
                card.classList.add('inactive');
                badge.className = 'badge bg-secondary';
                badge.textContent = 'Tidak';
                card.querySelector('.text-muted').textContent = 'Tidak bisa menambahkan budget untuk kategori ini';
            }
        };
        
        // Form validation
        const form = document.getElementById('categoryForm');
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            
            if (!name) {
                e.preventDefault();
                showToast('warning', 'Nama Kategori', 'Nama kategori wajib diisi');
                document.getElementById('name').focus();
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';
            submitBtn.disabled = true;
        });
        
        // Toast notification
        function showToast(type, title, message) {
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            const toastId = 'toast-' + Date.now();
            
            const toastHTML = `
                <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}:</strong> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            toastElement.addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
            return container;
        }
    });
</script>
@endpush