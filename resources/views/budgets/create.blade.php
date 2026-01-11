@extends('wallet::layouts.app')

@section('title', 'Buat Budget Baru')

@use('Modules\Wallet\Enums\PeriodType')

@push('styles')
<style>
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
    
    .period-type-card {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
        height: 100%;
    }
    
    .period-type-card:hover {
        border-color: var(--bs-primary);
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }
    
    .period-type-card.selected {
        border-color: var(--bs-primary);
        background-color: rgba(var(--bs-primary-rgb), 0.1);
    }
    
    .period-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: var(--bs-primary);
    }
    
    .account-select-item {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .account-select-item:hover {
        background-color: #f8f9fa;
        border-color: #adb5bd;
    }
    
    .account-select-item.selected {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        border-color: var(--bs-primary);
    }
    
    .account-icon-small {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-right: 10px;
    }
    
    .amount-preview {
        font-size: 2rem;
        font-weight: 700;
        text-align: center;
        padding: 1rem;
        border-radius: 10px;
        background-color: #f8f9fa;
        border: 2px dashed #dee2e6;
    }
    
    .suggested-amounts {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .suggested-amount {
        padding: 0.5rem 1rem;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .suggested-amount:hover {
        background-color: #f8f9fa;
        border-color: #adb5bd;
    }
    
    .suggested-amount.selected {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        border-color: var(--bs-primary);
        color: var(--bs-primary);
    }
    
    /* Date picker customization */
    .date-range-display {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
        border: 1px solid #dee2e6;
    }
    
    /* Dark mode adjustments */
    body[data-bs-theme="dark"] .form-section {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    body[data-bs-theme="dark"] .period-type-card {
        border-color: #4a5568;
        background-color: #374151;
    }
    
    body[data-bs-theme="dark"] .account-select-item {
        border-color: #4a5568;
        background-color: #374151;
    }
    
    body[data-bs-theme="dark"] .account-select-item:hover {
        background-color: #4b5563;
    }
    
    body[data-bs-theme="dark"] .amount-preview {
        background-color: #374151;
        border-color: #4b5563;
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="row mb-4">
  <div class="col">
    <a href="{{ route('apps.budgets.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
  </div>
  <div class="col-auto">
    <h2 class="page-title mb-2">
      <i class="bi bi-plus-circle me-2"></i>Buat Budget Baru
    </h2>
  </div>
</div>

<!-- Main Form -->
<div class="row">
  <div class="col-lg-8">
    <form action="{{ route('apps.budgets.store') }}" method="POST" id="createBudgetForm">
      @csrf
                
      <!-- Basic Information -->
      <div class="form-section">
        <h6 class="form-section-title">Informasi Dasar</h6>
                    
        <div class="row g-3">
          <!-- Category Selection -->
          <div class="col-md-8">
            <label for="category_id" class="form-label">
              <i class="bi bi-tags me-1"></i>Kategori
              <span class="text-danger">*</span>
            </label>
            <select class="form-select" id="category_id" name="category_id" required>
              <option value="">Pilih Kategori</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" 
                  {{ old('category_id', request('category_id')) == $category->id ? 'selected' : '' }} data-icon="{{ $category->icon }}" data-color="{{ $category->color ?? '#0d6efd' }}">
                  {{ $category->name }}
                </option>
              @endforeach
            </select>
            <div class="form-text">
              Hanya kategori pengeluaran yang tersedia untuk budget.
            </div>
            @error('category_id')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>
                        
          <!-- Custom Name -->
          <div class="col-md-4">
            <label for="name" class="form-label">
              <i class="bi bi-pencil me-1"></i>Nama Budget (Opsional)
            </label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Budget Liburan" value="{{ old('name') }}">
              <div class="form-text">
                Kosongkan untuk menggunakan nama default.
              </div>
            </div>
                        
          <!-- Category Preview -->
          <div class="col-12">
            <div class="category-preview d-none" id="categoryPreview">
              <div class="d-flex align-items-center p-3 bg-light rounded">
                <div class="category-icon me-3" id="previewCategoryIcon">
                  <i class="bi bi-tag"></i>
                </div>
                <div>
                  <h6 class="mb-1" id="previewCategoryName">Pilih Kategori</h6>
                  <small class="text-muted" id="previewCategoryDescription">
                    Deskripsi akan muncul di sini
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
                
      <!-- Period Configuration -->
      <div class="form-section">
        <h6 class="form-section-title">Konfigurasi Periode</h6>
                    
        <!-- Period Type Selection -->
        <div class="mb-4">
          <label class="form-label mb-3">Tipe Periode</label>
          <div class="row g-3">
            @foreach($periodTypes as $type)
              <div class="col-md-4">
                <div class="period-type-card" data-type="{{ $type }}">
                  <div class="period-icon">
                    @switch($type)
                      @case(PeriodType::MONTHLY)
                        <i class="bi bi-calendar-month"></i>
                        @break
                      @case(PeriodType::WEEKLY)
                        <i class="bi bi-calendar-week"></i>
                        @break
                      @case(PeriodType::BIWEEKLY)
                        <i class="bi bi-calendar2-week"></i>
                        @break
                      @case(PeriodType ::QUARTERLY)
                        <i class="bi bi-calendar3"></i>
                        @break
                      @case(PeriodType::YEARLY)
                        <i class="bi bi-calendar-range"></i>
                        @break
                      @case(PeriodType::CUSTOM)
                        <i class="bi bi-calendar-event"></i>
                        @break
                    @endswitch
                  </div>
                  <div class="fw-semibold">{{ ucfirst($type->value) }}</div>
                </div>
              </div>
            @endforeach
          </div>
          <input type="hidden" name="period_type" id="period_type" value="{{ old('period_type', $defaultPeriodType) }}">
        </div>
                    
        <!-- Period Value and Year -->
        <div class="row g-3" id="periodConfig">
                        <!-- Monthly -->
                        <div class="col-md-6 period-config monthly">
                            <label for="period_value_monthly" class="form-label">Bulan</label>
                            <select class="form-select" id="period_value_monthly" name="period_value">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ old('period_value', $defaultPeriodValue) == $i ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        
                        <!-- Weekly -->
                        <div class="col-md-6 period-config weekly d-none">
                            <label for="period_value_weekly" class="form-label">Minggu Ke-</label>
                            <select class="form-select" id="period_value_weekly" name="period_value">
                                @for($i = 1; $i <= 52; $i++)
                                    <option value="{{ $i }}" {{ old('period_value') == $i ? 'selected' : '' }}>
                                        Minggu {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        
                        <!-- Biweekly -->
                        <div class="col-md-6 period-config biweekly d-none">
                            <label for="period_value_biweekly" class="form-label">Periode 2 Mingguan Ke-</label>
                            <select class="form-select" id="period_value_biweekly" name="period_value">
                                @for($i = 1; $i <= 26; $i++)
                                    <option value="{{ $i }}" {{ old('period_value') == $i ? 'selected' : '' }}>
                                        Periode {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        
                        <!-- Quarterly -->
                        <div class="col-md-6 period-config quarterly d-none">
                            <label for="period_value_quarterly" class="form-label">Kuartal</label>
                            <select class="form-select" id="period_value_quarterly" name="period_value">
                                @for($i = 1; $i <= 4; $i++)
                                    <option value="{{ $i }}" {{ old('period_value') == $i ? 'selected' : '' }}>
                                        Q{{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        
                        <!-- Yearly -->
                        <div class="col-md-6 period-config yearly d-none">
                            <label for="period_value_yearly" class="form-label">Tahun</label>
                            <input type="hidden" name="period_value" id="period_value_yearly" value="1">
                            <input type="text" class="form-control" value="Tahunan" disabled>
                        </div>
                        
                        <!-- Custom -->
                        <div class="col-md-6 period-config custom d-none">
                            <label for="period_value_custom" class="form-label">Periode Kustom</label>
                            <input type="number" class="form-control" id="period_value_custom" name="period_value" 
                                   min="1" value="{{ old('period_value', 1) }}">
                            <div class="form-text">Nomor periode kustom</div>
                        </div>
                        
                        <!-- Year -->
                        <div class="col-md-6">
                            <label for="year" class="form-label">Tahun</label>
                            <select class="form-select" id="year" name="year" required>
                                @for($i = date('Y') - 2; $i <= date('Y') + 2; $i++)
                                    <option value="{{ $i }}" {{ old('year', $defaultYear) == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    
        <!-- Date Range Display -->
        <div class="date-range-display">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="{{ old('start_date') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="{{ old('end_date') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted" id="dateRangeLabel">
                                Periode: -
                            </small>
                        </div>
                    </div>
      </div>
                
      <!-- Amount Configuration -->
      <div class="form-section">
                    <h6 class="form-section-title">Jumlah Budget</h6>
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="amount" class="form-label">
                                <i class="bi bi-currency-exchange me-1"></i>Jumlah Budget
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       placeholder="1000000" min="1000" step="1000"
                                       value="{{ old('amount') }}" required>
                            </div>
                            <div class="form-text">
                                Minimum budget: Rp 1.000
                            </div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            
                            <!-- Suggested Amounts -->
                            <div class="mt-3" id="suggestedAmountsContainer">
                                <label class="form-label">Jumlah yang Disarankan:</label>
                                <div class="suggested-amounts">
                                    <div class="suggested-amount" data-amount="500000">500K</div>
                                    <div class="suggested-amount" data-amount="1000000">1 Jt</div>
                                    <div class="suggested-amount" data-amount="2000000">2 Jt</div>
                                    <div class="suggested-amount" data-amount="5000000">5 Jt</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="amount-preview" id="amountPreview">
                                Rp 0
                            </div>
                        </div>
                    </div>
                </div>
                
      <!-- Account Selection -->
      <div class="form-section">
                    <h6 class="form-section-title">Akun Terkait</h6>
                    <p class="text-muted mb-3">Pilih akun yang akan dipantau dalam budget ini. Kosongkan untuk memantau semua akun.</p>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAllAccounts">
                                    <label class="form-check-label" for="selectAllAccounts">
                                        Pilih Semua Akun
                                    </label>
                                </div>
                            </div>
                            
                            <div class="accounts-list" id="accountsList">
                                @foreach($accounts as $account)
                                    <div class="account-select-item" data-id="{{ $account->id }}">
                                        <div class="d-flex align-items-center">
                                            <div class="account-icon-small me-3" 
                                                 style="background-color: {{ $account->color }}20; color: {{ $account->color }}">
                                                <i class="bi bi-{{ $account->icon }}"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">{{ $account->name }}</div>
                                                <div class="text-muted small">
                                                    Saldo: {{ ($account->balance->getMinorAmount()->toInt()) }}
                                                </div>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input account-checkbox" 
                                                       type="checkbox" 
                                                       name="accounts[]" 
                                                       value="{{ $account->id }}"
                                                       id="account_{{ $account->id }}">
                                                <label class="form-check-label" for="account_{{ $account->id }}"></label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Jika tidak ada akun yang dipilih, budget akan memantau semua akun.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
      <!-- Advanced Settings -->
      <div class="form-section">
                    <h6 class="form-section-title">Pengaturan Lanjutan</h6>
                    
                    <div class="row g-3">
                        <!-- Rollover Settings -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="rollover_unused" name="rollover_unused" value="1"
                                        {{ old('rollover_unused') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="rollover_unused">
                                        <i class="bi bi-arrow-repeat me-1"></i>Rollover Sisa Budget
                                    </label>
                                    <div class="form-text">
                                        Sisa budget akan ditambahkan ke budget periode berikutnya.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="rollover-limit-container d-none" id="rolloverLimitContainer">
                                <label for="rollover_limit" class="form-label">Limit Rollover</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="rollover_limit" name="rollover_limit" 
                                           placeholder="500000" min="0" step="1000"
                                           value="{{ old('rollover_limit') }}">
                                </div>
                                <div class="form-text">
                                    Maksimal jumlah yang bisa di-rollover. Kosongkan untuk tanpa limit.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">
                                        <i class="bi bi-toggle-on me-1"></i>Budget Aktif
                                    </label>
                                    <div class="form-text">
                                        Nonaktifkan untuk menyembunyikan budget dari pemantauan.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
      <!-- Form Actions -->
      <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div>
                        <a href="{{ route('apps.budgets.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Batal
                        </a>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-primary me-2" id="previewBtn">
                            <i class="bi bi-eye me-1"></i>Pratinjau
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-check-circle me-1"></i>Simpan Budget
                        </button>
                    </div>
                </div>
    </form>
  </div>
        
  <!-- Sidebar -->
  <div class="col-lg-4">
    <div class="card sticky-top" style="top: 20px;">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-info-circle me-2"></i>Panduan Budget
        </h5>
      </div>
      <div class="card-body">
        <div class="mb-4">
                        <h6 class="fw-semibold">
                            <i class="bi bi-lightbulb text-warning me-2"></i>Tips Budgeting
                        </h6>
                        <ul class="list-unstyled ps-3">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <small>Mulai dari kategori penting terlebih dahulu</small>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <small>Gunakan data pengeluaran sebelumnya sebagai acuan</small>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <small>Review budget secara berkala</small>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <small>Aktifkan notifikasi untuk peringatan budget</small>
                            </li>
                        </ul>
                    </div>
                    
        <div class="mb-4">
                        <h6 class="fw-semibold">
                            <i class="bi bi-calculator text-primary me-2"></i>Kalkulator Budget Harian
                        </h6>
                        <div class="input-group mb-2">
                            <input type="number" class="form-control" id="dailyCalculator" placeholder="Jumlah budget">
                            <button class="btn btn-outline-primary" type="button" id="calculateDaily">
                                Hitung
                            </button>
                        </div>
                        <div id="dailyResult" class="d-none">
                            <small class="text-muted">Rata-rata per hari: <span id="dailyAmount">Rp 0</span></small>
                        </div>
                    </div>
                    
        <div>
                        <h6 class="fw-semibold">
                            <i class="bi bi-exclamation-triangle text-danger me-2"></i>Perhatian
                        </h6>
                        <p class="small text-muted">
                            Pastikan tanggal mulai dan selesai sudah benar. Budget tidak dapat diubah setelah periode berakhir.
                        </p>
                    </div>
      </div>
    </div>
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pratinjau Budget</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Preview content will be populated by JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="saveFromPreview">
          <i class="bi bi-check-circle me-1"></i>Simpan Budget
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Period type selection
    const periodTypeCards = document.querySelectorAll('.period-type-card');
    const periodTypeInput = document.getElementById('period_type');
    const periodConfigs = document.querySelectorAll('.period-config');
    
    // Initialize period type
    updatePeriodConfig();
    
    // Period type card click
    periodTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove selected from all cards
            periodTypeCards.forEach(c => c.classList.remove('selected'));
            
            // Add selected to clicked card
            this.classList.add('selected');
            
            // Update hidden input
            const periodType = this.dataset.type;
            periodTypeInput.value = periodType;
            
            // Update period configuration
            updatePeriodConfig();
            
            // Calculate dates
            calculateDates();
        });
    });
    
    // Update period configuration based on selected type
    function updatePeriodConfig() {
        const periodType = periodTypeInput.value;
        
        // Hide all configs
        periodConfigs.forEach(config => {
            config.classList.add('d-none');
        });
        
        // Show selected config
        const selectedConfig = document.querySelector(`.period-config.${periodType}`);
        if (selectedConfig) {
            selectedConfig.classList.remove('d-none');
        }
    }
    
    // Calculate dates based on period type, value, and year
    function calculateDates() {
        const periodType = periodTypeInput.value;
        const periodValue = getPeriodValue();
        const year = document.getElementById('year').value;
        
        if (!periodType || !periodValue || !year) return;
        
        // Make AJAX call to calculate dates
        fetch(`{{ route('api.apps.budgets.calculate-dates') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                period_type: periodType,
                period_value: periodValue,
                year: year
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('start_date').value = data.dates.start_date;
                document.getElementById('end_date').value = data.dates.end_date;
                updateDateRangeLabel(data.dates.start_date, data.dates.end_date);
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Get current period value
    function getPeriodValue() {
        const periodType = periodTypeInput.value;
        const elementId = `period_value_${periodType}`;
        const element = document.getElementById(elementId);
        
        if (element) {
            return element.value;
        }
        
        return document.querySelector('[name="period_value"]')?.value || 1;
    }
    
    // Update date range label
    function updateDateRangeLabel(startDate, endDate) {
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const options = { day: 'numeric', month: 'long', year: 'numeric' };
            const label = `Periode: ${start.toLocaleDateString('id-ID', options)} - ${end.toLocaleDateString('id-ID', options)}`;
            document.getElementById('dateRangeLabel').textContent = label;
        }
    }
    
    // Event listeners for period value and year changes
    document.getElementById('year').addEventListener('change', calculateDates);
    document.querySelectorAll('[name="period_value"]').forEach(element => {
        element.addEventListener('change', calculateDates);
    });
    
    // Amount formatting
    const amountInput = document.getElementById('amount');
    const amountPreview = document.getElementById('amountPreview');
    
    function formatAmount(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }
    
    if (amountInput && amountPreview) {
        amountInput.addEventListener('input', function() {
            const amount = parseInt(this.value) || 0;
            amountPreview.textContent = formatAmount(amount);
        });
        
        // Initialize preview
        amountPreview.textContent = formatAmount(parseInt(amountInput.value) || 0);
    }
    
    // Suggested amounts
    const suggestedAmounts = document.querySelectorAll('.suggested-amount');
    suggestedAmounts.forEach(element => {
        element.addEventListener('click', function() {
            // Remove selected from all
            suggestedAmounts.forEach(el => el.classList.remove('selected'));
            
            // Add selected to clicked
            this.classList.add('selected');
            
            // Update amount input
            const amount = this.dataset.amount;
            amountInput.value = amount;
            amountPreview.textContent = formatAmount(amount);
        });
    });
    
    // Category selection
    const categorySelect = document.getElementById('category_id');
    const categoryPreview = document.getElementById('categoryPreview');
    const previewCategoryIcon = document.getElementById('previewCategoryIcon');
    const previewCategoryName = document.getElementById('previewCategoryName');
    const previewCategoryDescription = document.getElementById('previewCategoryDescription');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const icon = selectedOption.dataset.icon;
            const color = selectedOption.dataset.color;
            const name = selectedOption.text;
            
            if (this.value) {
                categoryPreview.classList.remove('d-none');
                previewCategoryIcon.innerHTML = `<i class="bi bi-${icon}"></i>`;
                previewCategoryIcon.style.backgroundColor = `${color}20`;
                previewCategoryIcon.style.color = color;
                previewCategoryName.textContent = name;
                
                // Get suggested amount for category
                fetch(`{{ secure_url(config('app.url')) }}/api/apps/budgets/suggested-amount/${this.value}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.suggested_amount) {
                            amountInput.value = data.suggested_amount;
                            amountPreview.textContent = formatAmount(data.suggested_amount);
                        }
                    });
            } else {
                categoryPreview.classList.add('d-none');
            }
        });
    }
    
    // Account selection
    const selectAllAccounts = document.getElementById('selectAllAccounts');
    const accountCheckboxes = document.querySelectorAll('.account-checkbox');
    const accountSelectItems = document.querySelectorAll('.account-select-item');
    
    if (selectAllAccounts) {
        selectAllAccounts.addEventListener('change', function() {
            const isChecked = this.checked;
            accountCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                updateAccountItemSelection(checkbox);
            });
        });
    }
    
    // Account item click
    accountSelectItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (!e.target.closest('.form-check')) {
                const checkbox = this.querySelector('.account-checkbox');
                checkbox.checked = !checkbox.checked;
                updateAccountItemSelection(checkbox);
            }
        });
    });
    
    // Update account item visual selection
    function updateAccountItemSelection(checkbox) {
        const item = checkbox.closest('.account-select-item');
        if (checkbox.checked) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    }
    
    // Rollover toggle
    const rolloverCheckbox = document.getElementById('rollover_unused');
    const rolloverLimitContainer = document.getElementById('rolloverLimitContainer');
    
    if (rolloverCheckbox) {
        rolloverCheckbox.addEventListener('change', function() {
            if (this.checked) {
                rolloverLimitContainer.classList.remove('d-none');
            } else {
                rolloverLimitContainer.classList.add('d-none');
            }
        });
    }
    
    // Daily calculator
    const calculateDailyBtn = document.getElementById('calculateDaily');
    const dailyCalculatorInput = document.getElementById('dailyCalculator');
    const dailyResult = document.getElementById('dailyResult');
    const dailyAmount = document.getElementById('dailyAmount');
    
    if (calculateDailyBtn) {
        calculateDailyBtn.addEventListener('click', function() {
            const amount = parseInt(dailyCalculatorInput.value) || 0;
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (!amount || !startDate || !endDate) {
                alert('Isi jumlah budget dan tanggal terlebih dahulu');
                return;
            }
            
            const daysDiff = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
            const dailyAvg = Math.round(amount / daysDiff);
            
            dailyAmount.textContent = formatAmount(dailyAvg);
            dailyResult.classList.remove('d-none');
        });
    }
    
    // Preview button
    const previewBtn = document.getElementById('previewBtn');
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            // Collect form data and show preview
            const formData = new FormData(document.getElementById('createBudgetForm'));
            const data = Object.fromEntries(formData);
            
            // You can show a preview modal with the data
            // For simplicity, we'll just validate
            if (!validateForm()) {
                alert('Harap lengkapi semua field yang wajib diisi');
                return;
            }
            
            // Show preview modal
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        });
    }
    
    // Form validation
    function validateForm() {
        const categoryId = document.getElementById('category_id').value;
        const amount = document.getElementById('amount').value;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (!categoryId || !amount || !startDate || !endDate) {
            return false;
        }
        
        return true;
    }
    
    // Form submission
    const form = document.getElementById('createBudgetForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                alert('Harap lengkapi semua field yang wajib diisi');
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Initialize selected period type card
    const initialPeriodType = periodTypeInput.value;
    document.querySelector(`.period-type-card[data-type="${initialPeriodType}"]`)?.classList.add('selected');
    
    // Calculate initial dates
    calculateDates();
});
</script>
@endpush