@extends('wallet::layouts.app')

@section('title', isset($recurringTransaction) ? 'Edit Transaksi Rutin' : 'Tambah Transaksi Rutin')

@push('styles')
<style>
    .frequency-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .frequency-option {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: white;
    }
    
    .frequency-option:hover {
        border-color: #3b82f6;
        background-color: rgba(59, 130, 246, 0.05);
    }
    
    .frequency-option.active {
        border-color: #3b82f6;
        background-color: rgba(59, 130, 246, 0.1);
    }
    
    .frequency-option i {
        font-size: 1.5rem;
        margin-bottom: 8px;
        display: block;
    }
    
    .frequency-detail {
        display: none;
        padding: 15px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background-color: #f8f9fa;
        margin-top: 10px;
    }
    
    .frequency-detail.active {
        display: block;
    }
    
    body[data-bs-theme="dark"] .frequency-option {
        background-color: #1e1e1e;
        border-color: #495057;
    }
    
    body[data-bs-theme="dark"] .frequency-detail {
        background-color: #212529;
        border-color: #495057;
    }
    
    .occurrence-preview {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }
    
    body[data-bs-theme="dark"] .occurrence-preview {
        background-color: #212529;
    }
    
    .form-section {
        border-left: 3px solid #3b82f6;
        padding-left: 15px;
        margin-bottom: 25px;
    }
    
    .form-section-title {
        font-weight: 600;
        margin-bottom: 15px;
        color: #3b82f6;
    }
    
    .amount-input {
        position: relative;
    }
    
    .amount-input .input-group-text {
        background-color: #f8f9fa;
        font-weight: 500;
    }
    
    body[data-bs-theme="dark"] .amount-input .input-group-text {
        background-color: #2d3748;
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="page-title mb-1">
      {{ isset($recurringTransaction) ? 'Edit Transaksi Rutin' : 'Tambah Transaksi Rutin' }}
    </h1>
    <p class="text-muted mb-0">
      {{ isset($recurringTransaction) ? 'Perbarui transaksi rutin' : 'Buat transaksi rutin baru' }}
    </p>
  </div>
  <div>
    <a href="{{ route('apps.recurrings.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
  </div>
</div>

<form action="{{ isset($recurringTransaction) ? route('apps.recurring.update', $recurringTransaction->id) : route('apps.recurrings.store') }}" method="POST" id="recurringForm">
  @csrf
  @if(isset($recurringTransaction))
    @method('PUT')
  @endif

  <div class="row">
    <!-- Left Column: Basic Information -->
    <div class="col-lg-8">
      <!-- Basic Information Section -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="form-section">
            <h5 class="form-section-title">Informasi Dasar</h5>
          </div>
                        
          <div class="row g-3">
            <!-- Description -->
            <div class="col-12">
              <label for="description" class="form-label">Deskripsi *</label>
              <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description', $recurringTransaction->description ?? '') }}" placeholder="Contoh: Gaji bulanan, Tagihan listrik, dll." required>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Type Selection -->
            <div class="col-md-6">
              <label class="form-label">Tipe Transaksi *</label>
              <div class="row g-2">
                <div class="col-4">
                  <input type="radio" class="btn-check" name="type" id="type_income" value="income"
                    {{ old('type', $recurringTransaction->type ?? '') == 'income' ? 'checked' : '' }} required>
                  <label class="btn btn-outline-success w-100" for="type_income">
                    <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                  </label>
                </div>
                <div class="col-4">
                  <input type="radio" class="btn-check" name="type" id="type_expense" value="expense"
                    {{ old('type', $recurringTransaction->type ?? 'expense') == 'expense' ? 'checked' : '' }}>
                  <label class="btn btn-outline-danger w-100" for="type_expense">
                    <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                  </label>
                </div>
                <div class="col-4">
                  <input type="radio" class="btn-check" name="type" id="type_transfer" value="transfer"
                    {{ old('type', $recurringTransaction->type ?? '') == 'transfer' ? 'checked' : '' }}>
                  <label class="btn btn-outline-primary w-100" for="type_transfer">
                    <i class="bi bi-arrow-left-right me-1"></i> Transfer
                  </label>
                </div>
              </div>
              @error('type')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            <!-- Amount -->
            <div class="col-md-6">
              <label for="amount" class="form-label">Jumlah *</label>
              <div class="amount-input">
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $recurringTransaction->amount ?? '') }}" min="100" step="100" required>
                  @error('amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Account -->
            <div class="col-md-6">
              <label for="account_id" class="form-label">Akun *</label>
              <select class="form-select @error('account_id') is-invalid @enderror" id="account_id" name="account_id" required>
                <option value="">Pilih Akun</option>
                @foreach($accounts as $account)
                  <option value="{{ $account->id }}"
                    {{ old('account_id', $recurringTransaction->account_id ?? '') == $account->id ? 'selected' : '' }}>
                    {{ $account->name }} ({{ $account->type }})
                  </option>
                @endforeach
              </select>
              @error('account_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- To Account (for transfer) -->
            <div class="col-md-6" id="to_account_field" style="display: none;">
              <label for="to_account_id" class="form-label">Akun Tujuan *</label>
              <select class="form-select @error('to_account_id') is-invalid @enderror" id="to_account_id" name="to_account_id">
                <option value="">Pilih Akun Tujuan</option>
                @foreach($accounts as $account)
                  <option value="{{ $account->id }}"
                    {{ old('to_account_id', $recurringTransaction->to_account_id ?? '') == $account->id ? 'selected' : '' }}>
                    {{ $account->name }} ({{ $account->type }})
                  </option>
                @endforeach
              </select>
              @error('to_account_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Category -->
            <div class="col-md-6">
              <label for="category_id" class="form-label">Kategori *</label>
              <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                <option value="">Pilih Kategori</option>
                @foreach($categories as $id => $name)
                  <option value="{{ $id }}"
                    {{ old('category_id', $recurringTransaction->category_id ?? '') == $id ? 'selected' : '' }}>
                    {{ $name }}
                  </option>
                @endforeach
              </select>
              @error('category_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Is Active -->
            <div class="col-md-6">
              <div class="form-check mt-4 pt-2">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                  {{ old('is_active', $recurringTransaction->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                  Aktifkan transaksi ini
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recurrence Settings Section -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="form-section">
            <h5 class="form-section-title">Pengaturan Pengulangan</h5>
          </div>

          <!-- Frequency Selection -->
          <div class="mb-4">
            <label class="form-label">Frekuensi *</label>
            <div class="frequency-options">
              @foreach(['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'quarterly' => 'Triwulan', 'yearly' => 'Tahunan'] as $value => $label)
                <div class="frequency-option {{ old('frequency', $recurringTransaction->frequency ?? 'monthly') == $value ? 'active' : '' }}" data-frequency="{{ $value }}">
                  <i class="bi bi-{{ $value == 'daily' ? 'calendar-day' : ($value == 'weekly' ? 'calendar-week' : ($value == 'monthly' ? 'calendar-month' : ($value == 'quarterly' ? 'calendar-range' : 'calendar'))) }}"></i>
                  <div class="fw-medium">{{ $label }}</div>
                  <input type="radio" class="d-none" name="frequency" value="{{ $value }}"
                    {{ old('frequency', $recurringTransaction->frequency ?? 'monthly') == $value ? 'checked' : '' }} required>
                </div>
              @endforeach
            </div>
            @error('frequency')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <!-- Interval -->
          <div class="row mb-4">
            <div class="col-md-6">
              <label for="interval" class="form-label">Interval</label>
              <div class="input-group">
                <span class="input-group-text">Setiap</span>
                <input type="number" class="form-control @error('interval') is-invalid @enderror" id="interval" name="interval" value="{{ old('interval', $recurringTransaction->interval ?? 1) }}" min="1" max="12">
                <span class="input-group-text" id="interval_unit">bulan</span>
              </div>
              <small class="text-muted">Setiap berapa kali frekuensi terpilih</small>
              @error('interval')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Frequency Details -->
          <div class="frequency-detail {{ old('frequency', $recurringTransaction->frequency ?? '') == 'weekly' ? 'active' : '' }}" id="weekly_detail">
            <div class="row">
              <div class="col-12">
                <label class="form-label">Hari dalam Minggu *</label>
                <div class="row g-2">
                  @php
                                            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                  @endphp
                  @foreach($days as $index => $day)
                    <div class="col">
                      <input type="radio" class="btn-check" name="day_of_week" id="day_of_week_{{ $index }}" value="{{ $index }}" {{ old('day_of_week', $recurringTransaction->day_of_week ?? date('w')) == $index ? 'checked' : '' }}>
                      <label class="btn btn-outline-primary w-100" for="day_of_week_{{ $index }}">
                        {{ substr($day, 0, 3) }}
                      </label>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>

          <div class="frequency-detail {{ in_array(old('frequency', $recurringTransaction->frequency ?? ''), ['monthly', 'quarterly']) ? 'active' : '' }}" id="monthly_detail">
            <div class="row">
              <div class="col-md-6">
                <label for="day_of_month" class="form-label">Hari dalam Bulan *</label>
                <select class="form-select @error('day_of_month') is-invalid @enderror" id="day_of_month" name="day_of_month">
                  <option value="">Pilih Hari</option>
                  @for($i = 1; $i <= 31; $i++)
                    <option value="{{ $i }}"
                      {{ old('day_of_month', $recurringTransaction->day_of_month ?? date('d')) == $i ? 'selected' : '' }}>
                      Hari ke-{{ $i }}
                    </option>
                  @endfor
                  <option value="last" {{ old('day_of_month', $recurringTransaction->day_of_month ?? '') == 'last' ? 'selected' : '' }}>
                    Hari Terakhir
                  </option>
                </select>
                @error('day_of_month')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <!-- Date Range -->
          <div class="row g-3">
            <div class="col-md-6">
              <label for="start_date" class="form-label">Tanggal Mulai *</label>
              <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $recurringTransaction->start_date ?? date('Y-m-d')) }}" required>
              @error('start_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label for="end_date" class="form-label">Tanggal Berakhir (Opsional)</label>
              <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $recurringTransaction->end_date ?? '') }}">
              <div class="form-text">Kosongkan untuk berjalan selamanya</div>
              @error('end_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <!-- Remaining Occurrences -->
          <div class="row mt-3">
            <div class="col-md-6">
              <label for="remaining_occurrences" class="form-label">Jumlah Pengulangan (Opsional)</label>
              <input type="number" class="form-control @error('remaining_occurrences') is-invalid @enderror" id="remaining_occurrences" name="remaining_occurrences" value="{{ old('remaining_occurrences', $recurringTransaction->remaining_occurrences ?? '') }}" min="1">
              <div class="form-text">Jumlah pengulangan maksimum</div>
              @error('remaining_occurrences')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Preview & Actions -->
    <div class="col-lg-4">
      <!-- Preview Section -->
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3">Pratinjau Pengulangan</h5>
          <div id="occurrencePreview">
            <div class="text-center py-3">
              <i class="bi bi-calendar-event text-muted" style="font-size: 2rem;"></i>
              <p class="mt-2 mb-0">Pratinjau akan muncul di sini</p>
            </div>
          </div>
          <button type="button" class="btn btn-outline-primary w-100 mt-3" id="updatePreview">
            <i class="bi bi-arrow-clockwise me-1"></i> Perbarui Pratinjau
          </button>
        </div>
      </div>

      <!-- Actions Section -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">Aksi</h5>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save me-1"></i>
              {{ isset($recurringTransaction) ? 'Simpan Perubahan' : 'Simpan Transaksi Rutin' }}
            </button>
            <a href="{{ route('apps.recurrings.index') }}" class="btn btn-outline-secondary">
              <i class="bi bi-x-lg me-1"></i> Batal
            </a>
          </div>
                        
          @if(isset($recurringTransaction))
            <hr class="my-3">
            <div class="d-grid gap-2">
              <a href="{{ route('apps.recurrings.show', $recurringTransaction->id) }}" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i> Lihat Detail
              </a>
              @if($recurringTransaction->is_active)
                <button type="button" class="btn btn-outline-warning" id="toggleStatusBtn">
                  <i class="bi bi-power me-1"></i> Nonaktifkan
                </button>
              @else
                <button type="button" class="btn btn-outline-success" id="toggleStatusBtn">
                  <i class="bi bi-power me-1"></i> Aktifkan
                </button>
              @endif
              <button type="button" class="btn btn-outline-danger" id="deleteBtn">
                <i class="bi bi-trash me-1"></i> Hapus Transaksi
              </button>
            </div>
          @endif
        </div>
      </div>

      <!-- Quick Stats -->
      @if(isset($recurringTransaction))
        <div class="card mt-4">
          <div class="card-body">
            <h5 class="card-title mb-3">Statistik</h5>
            <div class="row g-2">
              <div class="col-6">
                <div class="bg-light p-2 rounded text-center">
                  <div class="fw-bold">{{ $recurringTransaction->transactions_count ?? 0 }}</div>
                  <small class="text-muted">Total Diproses</small>
                </div>
              </div>
              <div class="col-6">
                <div class="bg-light p-2 rounded text-center">
                  <div class="fw-bold">
                    @if($recurringTransaction->remaining_occurrences)
                      {{ $recurringTransaction->remaining_occurrences }}
                    @else
                      ∞
                    @endif
                  </div>
                  <small class="text-muted">Sisa Pengulangan</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endif
    </div>
  </div>
</form>

<!-- Delete Confirmation Modal -->
@if(isset($recurringTransaction))
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin menghapus transaksi rutin ini?</p>
        <p class="text-danger">
          <i class="bi bi-exclamation-triangle me-1"></i>
          Transaksi yang sudah diproses tidak akan terpengaruh, tetapi transaksi mendatang tidak akan dibuat.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <form action="{{ route('apps.recurrings.destroy', $recurringTransaction->id) }}" method="POST">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Hapus</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format currency for amount input
    const amountInput = document.getElementById('amount');
    if (amountInput) {
        amountInput.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = Math.round(value / 100) * 100; // Round to nearest 100
            }
        });
    }

    // Show/hide to account field based on transaction type
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const toAccountField = document.getElementById('to_account_field');
    
    function updateToAccountField() {
        const selectedType = document.querySelector('input[name="type"]:checked')?.value;
        if (selectedType === 'transfer') {
            toAccountField.style.display = 'block';
            document.getElementById('to_account_id').required = true;
        } else {
            toAccountField.style.display = 'none';
            document.getElementById('to_account_id').required = false;
        }
    }
    
    typeRadios.forEach(radio => {
        radio.addEventListener('change', updateToAccountField);
    });
    updateToAccountField(); // Initial check

    // Frequency selection
    const frequencyOptions = document.querySelectorAll('.frequency-option');
    const intervalUnit = document.getElementById('interval_unit');
    
    frequencyOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove active class from all options
            frequencyOptions.forEach(opt => {
                opt.classList.remove('active');
                opt.querySelector('input[type="radio"]').checked = false;
            });
            
            // Add active class to clicked option
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update interval unit text
            const frequency = this.dataset.frequency;
            updateIntervalUnit(frequency);
            
            // Show/hide frequency details
            updateFrequencyDetails(frequency);
            
            // Update preview
            updatePreview();
        });
    });

    // Update interval unit text
    function updateIntervalUnit(frequency) {
        const units = {
            daily: 'hari',
            weekly: 'minggu',
            monthly: 'bulan',
            quarterly: 'triwulan',
            yearly: 'tahun'
        };
        if (intervalUnit && units[frequency]) {
            intervalUnit.textContent = units[frequency];
        }
    }

    // Show/hide frequency details
    function updateFrequencyDetails(frequency) {
        // Hide all detail sections
        document.querySelectorAll('.frequency-detail').forEach(detail => {
            detail.classList.remove('active');
        });
        
        // Show relevant detail section
        if (frequency === 'weekly') {
            document.getElementById('weekly_detail').classList.add('active');
        } else if (frequency === 'monthly' || frequency === 'quarterly') {
            document.getElementById('monthly_detail').classList.add('active');
        }
    }

    // Initialize frequency settings
    const initialFrequency = document.querySelector('input[name="frequency"]:checked')?.value || 'monthly';
    updateIntervalUnit(initialFrequency);
    updateFrequencyDetails(initialFrequency);

    // Form validation
    const form = document.getElementById('recurringForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (endDate && new Date(endDate) <= new Date(startDate)) {
                e.preventDefault();
                alert('Tanggal berakhir harus setelah tanggal mulai.');
                return false;
            }
            
            const amount = document.getElementById('amount').value;
            if (amount < 100) {
                e.preventDefault();
                alert('Jumlah minimal adalah Rp 100.');
                return false;
            }
            
            return true;
        });
    }

    // Update preview
    const updatePreviewBtn = document.getElementById('updatePreview');
    if (updatePreviewBtn) {
        updatePreviewBtn.addEventListener('click', updatePreview);
    }
    
    // Auto-update preview when form changes
    const previewInputs = ['frequency', 'interval', 'start_date', 'end_date', 'remaining_occurrences', 'day_of_week', 'day_of_month'];
    previewInputs.forEach(inputName => {
        const inputs = document.querySelectorAll(`[name="${inputName}"]`);
        inputs.forEach(input => {
            input.addEventListener('change', updatePreview);
        });
    });

    function updatePreview() {
        const previewContainer = document.getElementById('occurrencePreview');
        const formData = new FormData(document.getElementById('recurringForm'));
        
        // Show loading state
        previewContainer.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Memuat pratinjau...</p>
            </div>
        `;
        
        // Simulate calculation (in real app, this would be an AJAX call)
        setTimeout(() => {
            const frequency = document.querySelector('input[name="frequency"]:checked')?.value;
            const interval = parseInt(document.getElementById('interval').value) || 1;
            const startDate = document.getElementById('start_date').value;
            
            if (!frequency || !startDate) {
                previewContainer.innerHTML = `
                    <div class="text-center py-3">
                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">Lengkapi pengaturan frekuensi</p>
                    </div>
                `;
                return;
            }
            
            // Generate preview text
            let previewText = '';
            const frequencyText = {
                daily: 'hari',
                weekly: 'minggu',
                monthly: 'bulan',
                quarterly: 'triwulan',
                yearly: 'tahun'
            }[frequency];
            
            const startDateObj = new Date(startDate);
            const startFormatted = startDateObj.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            previewText += `<p><strong>Dimulai:</strong> ${startFormatted}</p>`;
            previewText += `<p><strong>Frekuensi:</strong> Setiap ${interval} ${frequencyText}</p>`;
            
            // Generate next occurrences
            previewText += '<p class="mb-2"><strong>5 Transaksi Berikutnya:</strong></p>';
            previewText += '<div class="list-group list-group-flush">';
            
            const nextDates = calculateNextOccurrences(startDateObj, frequency, interval, 5);
            nextDates.forEach((date, index) => {
                const formattedDate = date.toLocaleDateString('id-ID', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
                
                previewText += `
                    <div class="list-group-item border-0 px-0 py-1">
                        <div class="d-flex justify-content-between">
                            <span>${index + 1}. ${formattedDate}</span>
                            <span class="badge ${index === 0 ? 'bg-primary' : 'bg-light text-dark'}">
                                ${index === 0 ? 'Berikutnya' : ''}
                            </span>
                        </div>
                    </div>
                `;
            });
            
            previewText += '</div>';
            
            previewContainer.innerHTML = previewText;
        }, 500);
    }

    // Helper function to calculate next occurrences
    function calculateNextOccurrences(startDate, frequency, interval, count) {
        const dates = [];
        let currentDate = new Date(startDate);
        const now = new Date();
        
        // If start date is in the past, find the next occurrence
        if (currentDate < now) {
            switch (frequency) {
                case 'daily':
                    while (currentDate < now) {
                        currentDate.setDate(currentDate.getDate() + interval);
                    }
                    break;
                case 'weekly':
                    while (currentDate < now) {
                        currentDate.setDate(currentDate.getDate() + (interval * 7));
                    }
                    break;
                case 'monthly':
                    while (currentDate < now) {
                        currentDate.setMonth(currentDate.getMonth() + interval);
                    }
                    break;
                case 'quarterly':
                    while (currentDate < now) {
                        currentDate.setMonth(currentDate.getMonth() + (interval * 3));
                    }
                    break;
                case 'yearly':
                    while (currentDate < now) {
                        currentDate.setFullYear(currentDate.getFullYear() + interval);
                    }
                    break;
            }
        }
        
        // Generate next dates
        for (let i = 0; i < count; i++) {
            const nextDate = new Date(currentDate);
            
            switch (frequency) {
                case 'daily':
                    nextDate.setDate(currentDate.getDate() + (interval * i));
                    break;
                case 'weekly':
                    nextDate.setDate(currentDate.getDate() + (interval * 7 * i));
                    break;
                case 'monthly':
                    nextDate.setMonth(currentDate.getMonth() + (interval * i));
                    break;
                case 'quarterly':
                    nextDate.setMonth(currentDate.getMonth() + (interval * 3 * i));
                    break;
                case 'yearly':
                    nextDate.setFullYear(currentDate.getFullYear() + (interval * i));
                    break;
            }
            
            dates.push(nextDate);
        }
        
        return dates;
    }

    // Initial preview
    updatePreview();

    // Delete confirmation
    const deleteBtn = document.getElementById('deleteBtn');
    const deleteModal = document.getElementById('deleteModal');
    
    if (deleteBtn && deleteModal) {
        const modal = new bootstrap.Modal(deleteModal);
        deleteBtn.addEventListener('click', function() {
            modal.show();
        });
    }

    // Toggle status
    const toggleStatusBtn = document.getElementById('toggleStatusBtn');
    if (toggleStatusBtn) {
        toggleStatusBtn.addEventListener('click', function() {
            const id = '{{ $recurringTransaction->id ?? 0 }}';
            const action = '{{ isset($recurringTransaction) && $recurringTransaction->is_active ? "deactivate" : "activate" }}';
            const message = action === 'deactivate' 
                ? 'Apakah Anda yakin ingin menonaktifkan transaksi ini?' 
                : 'Apakah Anda yakin ingin mengaktifkan transaksi ini?';
            
            if (confirm(message)) {
                fetch('{{ secure_url(config("app.url") . "/apps/recurrings/toggle-status") }}/' + id, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal mengubah status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengubah status');
                });
            }
        });
    }
});
</script>
@endpush