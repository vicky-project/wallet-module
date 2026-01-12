@extends('wallet::layouts.app')

@section('title', 'Edit Budget - ' . $budget->name ?: $budget->category->name)

@use('Modules\Wallet\Enums\PeriodType')

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="row mb-4">
  <div class="col">
    <h2 class="page-title mb-2">
      <i class="bi bi-pencil-square me-2"></i>Edit Budget
    </h2>
  </div>
  <div class="col-auto">
    <a href="{{ route('apps.budgets.show', $budget) }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail
    </a>
  </div>
</div>

<!-- Current Budget Stats -->
<div class="current-stats">
  <div class="row align-items-center">
    <div class="col-md-6 mb-3">
      <div class="mb-3">
        <div class="stat-label">BUDGET SAAT INI</div>
        <div class="stat-value">@money($budget->amount->getMinorAmount()->toInt())</div>
      </div>
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
          <small>Terpakai: @money($budget->spent->getMinorAmount()->toInt())</small>
          <small>Sisa: @money($budget->remaining * 100)</small>
        </div>
        <div class="progress progress-edit">
          <div class="progress-bar 
            @if($budget->is_over_budget) bg-danger
            @elseif($budget->usage_percentage >= 80) bg-warning
            @else bg-success @endif" 
            style="width: {{ min($budget->usage_percentage, 100) }}%">
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-3 text-md-end">
      <div class="mb-3">
        <div class="stat-label">PERIODE</div>
        <div class="stat-value">{{ $budget->period_label }}</div>
      </div>
      <div class="d-flex justify-content-md-end gap-2">
        <span class="badge bg-light text-dark">
          <i class="bi bi-calendar me-1"></i>{{ $budget->days_left }} hari lagi
        </span>
        <span class="badge bg-light text-dark">
          <i class="bi bi-graph-up me-1"></i>{{ number_format($budget->usage_percentage, 1) }}%
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Main Form -->
<div class="row">
  <div class="col-lg-8 mb-4">
    <form action="{{ route('apps.budgets.update', $budget) }}" method="POST" id="editBudgetForm">
      @csrf
      @method('PUT')
                
      <!-- Basic Information -->
      <div class="form-section">
        <h6 class="form-section-title">Informasi Dasar</h6>
                    
        <div class="row g-3">
          <!-- Category (Readonly) -->
          <div class="col-md-8">
            <label class="form-label">
              <i class="bi bi-tags me-1"></i>Kategori
            </label>
            <div class="d-flex align-items-center p-2 text-bg-secondary rounded">
              <div class="account-icon-small me-3" style="background-color: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary);">
                <i class="bi {{ $budget->category->icon }}"></i>
              </div>
              <div>
                <div class="fw-semibold">{{ $budget->category->name }}</div>
                <small class="text-muted">Kategori tidak dapat diubah setelah budget dibuat</small>
              </div>
            </div>
            <input type="hidden" name="category_id" value="{{ $budget->category_id }}">
          </div>
                        
          <!-- Custom Name -->
          <div class="col-md-4">
            <label for="name" class="form-label">
              <i class="bi bi-pencil me-1"></i>Nama Budget
            </label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Budget Liburan" value="{{ old('name', $budget->name) }}">
            <div class="form-text">
              Kosongkan untuk menggunakan nama kategori.
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
                <div class="period-type-card {{ old('period_type', $budget->period_type->value) == $type->value ? 'selected' : '' }}" data-type="{{ $type->value}}">
                  <div class="period-icon">
                    <i class="bi {{ $type->icon() }}"></i>
                  </div>
                  <div class="fw-semibold">{{ ucfirst($type->label()) }}</div>
                </div>
              </div>
            @endforeach
          </div>
          <input type="hidden" name="period_type" id="period_type" value="{{ old('period_type', $budget->period_type->value) }}">
        </div>
                    
        <!-- Period Value and Year -->
        <div class="row g-3" id="periodConfig">
          <!-- Monthly -->
          <div class="col-md-6 period-config monthly {{ $budget->period_type != PeriodType::MONTHLY ? 'd-none' : '' }}">
            <label for="period_value_monthly" class="form-label">Bulan</label>
            <select class="form-select" id="period_value_monthly" name="period_value">
              @for($i = 1; $i <= 12; $i++)
                <option value="{{ $i }}" {{ old('period_value', $budget->period_value) == $i ? 'selected' : '' }}>
                  {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                </option>
              @endfor
            </select>
          </div>
                        
          <!-- Weekly -->
          <div class="col-md-6 period-config weekly {{ $budget->period_type != PeriodType::WEEKLY ? 'd-none' : '' }}">
            <label for="period_value_weekly" class="form-label">Minggu Ke-</label>
            <select class="form-select" id="period_value_weekly" name="period_value">
              @for($i = 1; $i <= 52; $i++)
                <option value="{{ $i }}" {{ old('period_value', $budget->period_value) == $i ? 'selected' : '' }}>
                  Minggu {{ $i }}
                </option>
              @endfor
            </select>
          </div>
                        
          <!-- Biweekly -->
          <div class="col-md-6 period-config biweekly {{ $budget->period_type != PeriodType::BIWEEKLY ? 'd-none' : '' }}">
            <label for="period_value_biweekly" class="form-label">Periode 2 Mingguan Ke-</label>
            <select class="form-select" id="period_value_biweekly" name="period_value">
              @for($i = 1; $i <= 26; $i++)
                <option value="{{ $i }}" {{ old('period_value', $budget->period_value) == $i ? 'selected' : '' }}>
                  Periode {{ $i }}
                </option>
              @endfor
            </select>
          </div>
                        
          <!-- Quarterly -->
          <div class="col-md-6 period-config quarterly {{ $budget->period_type != PeriodType::QUARTERLY ? 'd-none' : '' }}">
            <label for="period_value_quarterly" class="form-label">Kuartal</label>
            <select class="form-select" id="period_value_quarterly" name="period_value">
              @for($i = 1; $i <= 4; $i++)
                <option value="{{ $i }}" {{ old('period_value', $budget->period_value) == $i ? 'selected' : '' }}>
                  Q{{ $i }}
                </option>
              @endfor
            </select>
          </div>
                        
          <!-- Yearly -->
          <div class="col-md-6 period-config yearly {{ $budget->period_type != PeriodType::YEARLY ? 'd-none' : '' }}">
            <label for="period_value_yearly" class="form-label">Tahun</label>
            <input type="hidden" name="period_value" id="period_value_yearly" value="1">
            <input type="text" class="form-control" value="Tahunan" disabled>
          </div>
                        
          <!-- Custom -->
          <div class="col-md-6 period-config custom {{ $budget->period_type != PeriodType::CUSTOM ? 'd-none' : '' }}">
            <label for="period_value_custom" class="form-label">Periode Kustom</label>
            <input type="number" class="form-control" id="period_value_custom" name="period_value" min="1" value="{{ old('period_value', $budget->period_value) }}">
            <div class="form-text">Nomor periode kustom</div>
          </div>
                        
          <!-- Year -->
          <div class="col-md-6">
            <label for="year" class="form-label">Tahun</label>
            <select class="form-select" id="year" name="year" required>
              @for($i = date('Y') - 2; $i <= date('Y') + 2; $i++)
                <option value="{{ $i }}" {{ old('year', $budget->year) == $i ? 'selected' : '' }}>
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
                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date', $budget->start_date->format('Y-m-d')) }}" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date', $budget->end_date->format('Y-m-d')) }}" required>
              </div>
            </div>
          </div>
          <div class="text-center mt-2">
            <small class="text-muted" id="dateRangeLabel">
              Periode: {{ $budget->start_date->format('d M Y') }} - {{ $budget->end_date->format('d M Y') }}
            </small>
          </div>
        </div>
                    
        <div class="alert alert-info mt-3">
          <div class="d-flex">
            <div class="flex-shrink-0">
              <i class="bi bi-info-circle"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="alert-heading mb-1">Perubahan Periode</h6>
              <p class="mb-0">Mengubah periode budget akan mempengaruhi perhitungan transaksi yang terkait.</p>
            </div>
          </div>
        </div>
      </div>
                
      <!-- Amount Configuration -->
      <div class="form-section">
        <h6 class="form-section-title">Jumlah Budget</h6>
                    
        <div class="row g-3">
          <div class="col-md-8">
            <label for="amount" class="form-label">
              <i class="bi bi-currency-exchange me-1"></i>Jumlah Budget Baru
              <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" class="form-control" id="amount" name="amount" placeholder="1000000" min="1000" value="{{ old('amount', $budget->amount) }}" required>
            </div>
            <div class="form-text">
              Saat ini: @money($budget->amount->getMinorAmount()->toInt()) • Minimum: Rp 1.000
            </div>
            @error('amount')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
                            
            <!-- Suggested Amounts -->
            <div class="mt-3" id="suggestedAmountsContainer">
              <label class="form-label">Penyesuaian yang Disarankan:</label>
              <div class="suggested-amounts">
                @php
                  $currentAmount = $budget->amount;
                  $adjustments = [
                    round($currentAmount->multipliedBy(0.9)->getMinorAmount()->toInt()), // -10%
                    round($currentAmount->multipliedBy(1.1)->getMinorAmount()->toInt()), // +10%
                    round($currentAmount->multipliedBy(1.2)->getMinorAmount()->toInt()),
                    $budget->remaining > 0 ? $currentAmount->minus($budget->remaining, \Brick\Math\RoundingMode::DOWN)->getMinorAmount()->toInt() : $currentAmount->getMinorAmount()->t() // Remove remaining
                  ];
                @endphp
                <div class="suggested-amount" data-amount="{{ $adjustments[0] }}">
                  -10% (@money($adjustments[0]))
                </div>
                <div class="suggested-amount" data-amount="{{ $adjustments[1] }}">
                  +10% (@money($adjustments[1]))
                </div>
                <div class="suggested-amount" data-amount="{{ $adjustments[2] }}">
                  +20% (@money($adjustments[2]))
                </div>
              @if($budget->remaining > 0)
                <div class="suggested-amount" data-amount="{{ $adjustments[3] }}">
                Hapus Sisa (@money($adjustments[3]))
                </div>
              @endif
              </div>
            </div>
          </div>
                        
          <div class="col-md-4">
            <div class="amount-preview" id="amountPreview">
              @money(old('amount', $budget->amount->getMinorAmount()->toInt()))
            </div>
            <div class="text-center mt-2">
              <small class="text-muted d-block">Terpakai: @money($budget->spent->getMinorAmount()->toInt())</small>
              <small class="text-muted">Sisa: @money($budget->remaining * 100)</small>
            </div>
          </div>
        </div>
      </div>
                
      <!-- Account Selection -->
      <div class="form-section">
        <h6 class="form-section-title">Akun Terkait</h6>
        <p class="text-muted mb-3">Pilih akun yang akan dipantau dalam budget ini.</p>
                    
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
                @php
                  $isSelected = in_array($account->id, $selectedAccounts);
                @endphp
                <div class="account-select-item {{ $isSelected ? 'selected' : '' }}" data-id="{{ $account->id }}">
                  <div class="d-flex align-items-center">
                    <div class="account-icon-small me-3" style="background-color: {{ $account->color }}20; color: {{ $account->color }}">
                      <i class="bi bi-{{ $account->icon }}"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="fw-semibold">{{ $account->name }}</div>
                      <div class="text-muted small">
                        Saldo: @money($account->balance->getMinorAmount()->toInt()) • {{ $account->type->label() }}
                      </div>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input account-checkbox" type="checkbox" name="accounts[]" value="{{ $account->id }}" id="account_{{ $account->id }}"
                      {{ $isSelected ? 'checked' : '' }}>
                      <label class="form-check-label" for="account_{{ $account->id }}"></label>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
                            
            <div class="mt-3">
              <small class="text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Saat ini: {{ $budget->accounts->count() }} akun terkait
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
                  {{ old('rollover_unused', $budget->rollover_unused) ? 'checked' : '' }}>
                <label class="form-check-label" for="rollover_unused">
                  <i class="bi bi-arrow-repeat me-1"></i>Rollover Sisa Budget
                </label>
                <div class="form-text">
                  Saat ini: {{ $budget->rollover_unused ? 'AKTIF' : 'NONAKTIF' }}
                </div>
              </div>
            </div>
                            
            <div class="rollover-limit-container {{ !$budget->rollover_unused ? 'd-none' : '' }}" id="rolloverLimitContainer">
              <label for="rollover_limit" class="form-label">Limit Rollover</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control" id="rollover_limit" name="rollover_limit" placeholder="500000" min="0" value="{{ old('rollover_limit', $budget->rollover_limit) }}">
              </div>
              <div class="form-text">
                Saat ini: 
                @if($budget->rollover_limit)
                  @money($budget->rollover_limit)
                @else
                  Tidak ada limit
                @endif
              </div>
            </div>
          </div>
                        
          <!-- Status -->
          <div class="col-md-6">
            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                  {{ old('is_active', $budget->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                  <i class="bi bi-toggle-on me-1"></i>Budget Aktif
                </label>
                <div class="form-text">
                  Saat ini: {{ $budget->is_active ? 'AKTIF' : 'NONAKTIF' }}
                </div>
              </div>
            </div>
          </div>
                        
          <!-- Update Spent Amount -->
          <div class="col-12">
            <div class="alert alert-warning">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h6 class="alert-heading mb-1">Perbarui Jumlah Terpakai</h6>
                  <p class="mb-0">Jumlah terpakai saat ini: @money($budget->spent->getMinorAmount()->toInt())</p>
                  <div class="mt-2">
                    <a href="{{ route('apps.budgets.update-spent') }}" class="btn btn-sm btn-outline-warning" onclick="return confirm('Perbarui jumlah terpakai dari transaksi?')">
                      <i class="bi bi-arrow-clockwise me-1"></i>Perbarui dari Transaksi
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
                
      <!-- Danger Zone -->
      <div class="form-section danger-zone">
        <h6 class="form-section-title">
          <i class="bi bi-exclamation-triangle me-1"></i>Zona Berbahaya
        </h6>
                    
        <div class="alert alert-danger">
          <div class="d-flex">
            <div class="flex-shrink-0">
              <i class="bi bi-exclamation-octagon"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="alert-heading">Reset Jumlah Terpakai</h6>
              <p class="mb-2">
                Reset jumlah terpakai ke 0. Ini akan menghapus semua data penggunaan budget.
                <strong class="d-block mt-1">Aksi ini tidak dapat dibatalkan!</strong>
              </p>
              <div class="mt-3">
                <button type="button" class="btn btn-danger btn-sm" id="resetSpentBtn">
                  <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Jumlah Terpakai
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
                
      <!-- Form Actions -->
      <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
        <div>
          <a href="{{ route('apps.budgets.show', $budget) }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Batal
          </a>
        </div>
        <div>
          <button type="submit" class="btn btn-primary" id="submitBtn">
            <i class="bi bi-check-circle me-1"></i>Update Budget
          </button>
        </div>
      </div>
    </form>
  </div>
        
  <!-- Sidebar -->
  <div class="col-lg-4 mb-4">
    <div class="card sticky-top" style="top: 20px;">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-info-circle me-2"></i>Informasi Budget
        </h5>
      </div>
      <div class="card-body">
        <div class="mb-4">
          <h6 class="fw-semibold">Ringkasan Saat Ini</h6>
          <div class="list-group list-group-flush">
            <div class="list-group-item px-0">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Periode:</span>
                <span class="fw-semibold">{{ $budget->period_label }}</span>
              </div>
            </div>
            <div class="list-group-item px-0">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Budget:</span>
                <span class="fw-semibold">@money($budget->amount->getMinorAmount()->toInt())</span>
              </div>
            </div>
            <div class="list-group-item px-0">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Terpakai:</span>
                <span class="fw-semibold {{ $budget->is_over_budget ? 'text-danger' : 'text-success' }}">
                  @money($budget->spent->getMinorAmount()->toInt())
                </span>
              </div>
            </div>
            <div class="list-group-item px-0">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Sisa:</span>
                <span class="fw-semibold">@money($budget->remaining * 100)</span>
              </div>
            </div>
            <div class="list-group-item px-0">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Penggunaan:</span>
                <span class="fw-semibold">{{ number_format($budget->usage_percentage, 1) }}%</span>
              </div>
            </div>
          </div>
        </div>
                    
        <div class="mb-4">
          <h6 class="fw-semibold">
            <i class="bi bi-lightbulb text-warning me-2"></i>Tips Mengedit Budget
          </h6>
          <ul class="list-unstyled ps-3">
            <li class="mb-2">
              <i class="bi bi-check-circle text-success me-2"></i>
              <small>Perbarui budget sesuai kebutuhan aktual</small>
            </li>
            <li class="mb-2">
              <i class="bi bi-check-circle text-success me-2"></i>
              <small>Review transaksi sebelum mengubah periode</small>
            </li>
            <li class="mb-2">
              <i class="bi bi-check-circle text-success me-2"></i>
              <small>Update jumlah terpakai secara berkala</small>
            </li>
            <li class="mb-2">
              <i class="bi bi-check-circle text-success me-2"></i>
              <small>Nonaktifkan budget jika tidak digunakan</small>
            </li>
          </ul>
        </div>
                    
        <div>
          <h6 class="fw-semibold">
            <i class="bi bi-clock-history text-primary me-2"></i>Riwayat Perubahan
          </h6>
          <p class="small text-muted">
            Budget ini dibuat pada {{ $budget->created_at->format('d M Y') }}.
            @if($budget->updated_at != $budget->created_at)
              Terakhir diupdate {{ $budget->updated_at->diffForHumans() }}.
            @endif
          </p>
        </div>
      </div>
    </div>
            
    <!-- Quick Links -->
    <div class="card mt-4">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-link me-2"></i>Tautan Cepat
        </h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="{{ route('apps.budgets.show', $budget) }}" class="btn btn-outline-primary">
            <i class="bi bi-eye me-2"></i>Lihat Detail
          </a>
          <a href="{{ route('apps.transactions.create', ['budget_id' => $budget->id]) }}" class="btn btn-outline-success">
            <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi
          </a>
          <a href="{{ route('apps.budgets.duplicate', $budget) }}" class="btn btn-outline-info" onclick="return confirm('Duplikat budget ini?')">
            <i class="bi bi-files me-2"></i>Duplikat Budget
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Reset Spent Confirmation Modal -->
<div class="modal fade" id="resetSpentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-danger">
        <h5 class="modal-title text-danger">
          <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Reset
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <div class="icon-preview mx-auto bg-danger bg-opacity-10 text-danger" style="width: 80px; height: 80px; font-size: 2rem;">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
        </div>
                
        <h5 class="text-center mb-3">Reset Jumlah Terpakai?</h5>
                
        <div class="alert alert-danger">
          <div class="d-flex">
            <div class="flex-shrink-0">
              <i class="bi bi-exclamation-octagon"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <p class="mb-0">
                <strong>Perhatian:</strong> Aksi ini akan:
              </p>
              <ul class="mb-0 mt-2">
                <li>Reset jumlah terpakai dari @money($budget->spent->getMinorAmount()->toInt()) menjadi 0</li>
                <li>Tidak mempengaruhi transaksi yang sudah ada</li>
                <li>Hanya mempengaruhi perhitungan budget</li>
              </ul>
              <p class="mb-0 mt-2">
                <strong class="text-danger">Aksi ini tidak dapat dibatalkan!</strong>
              </p>
            </div>
          </div>
        </div>
                
        <div class="form-check mt-3">
          <input class="form-check-input" type="checkbox" id="confirmReset">
          <label class="form-check-label" for="confirmReset">
            Saya mengerti dan ingin melanjutkan reset
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <form method="POST" action="{{ route('apps.budgets.reset-spent', $budget) }}" id="resetSpentForm">
          @csrf
          @method('PUT')
          <button type="submit" class="btn btn-danger" id="confirmResetBtn" disabled>
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Jumlah Terpakai
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
    // Copy JavaScript from create.blade.php for period type selection, etc.
    // Then add specific edit functionality:
    
    // Initialize with current period type selected
    const currentPeriodType = '{{ $budget->period_type->value }}';
    const periodTypeCards = document.querySelectorAll('.period-type-card');
    
    periodTypeCards.forEach(card => {
        if (card.dataset.type === currentPeriodType) {
            card.classList.add('selected');
        }
    });
    
    // Reset spent amount button
    const resetSpentBtn = document.getElementById('resetSpentBtn');
    if (resetSpentBtn) {
        resetSpentBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('resetSpentModal'));
            modal.show();
        });
    }
    
    // Confirm reset checkbox
    const confirmCheckbox = document.getElementById('confirmReset');
    const confirmResetBtn = document.getElementById('confirmResetBtn');
    
    if (confirmCheckbox && confirmResetBtn) {
        confirmCheckbox.addEventListener('change', function() {
            confirmResetBtn.disabled = !this.checked;
        });
    }
    
    // Suggested amounts for edit
    const suggestedAmounts = document.querySelectorAll('.suggested-amount');
    const amountInput = document.getElementById('amount');
    const amountPreview = document.getElementById('amountPreview');
    
    suggestedAmounts.forEach(element => {
        element.addEventListener('click', function() {
            // Remove selected from all
            suggestedAmounts.forEach(el => el.classList.remove('selected'));
            
            // Add selected to clicked
            this.classList.add('selected');
            
            // Update amount input
            const amount = this.dataset.amount;
            console.log(amount)
            amountInput.value = amount;
            
            // Update preview
            if (amountPreview) {
                amountPreview.textContent = formatCurrency(amount);
            }
        });
    });
    
    // Format currency function
    function formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount || 0);
    }
    
    // Update amount preview on input
    if (amountInput && amountPreview) {
        amountInput.addEventListener('input', function() {
        console.log(this.value)
            amountPreview.textContent = formatCurrency(this.value);
            
            // Remove selected from suggested amounts
            suggestedAmounts.forEach(el => el.classList.remove('selected'));
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
    
    // Form submission
    const form = document.getElementById('editBudgetForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';
                submitBtn.disabled = true;
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
    /* Copy styles from create.blade.php */
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
    
    /* Current budget stats */
    .current-stats {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .current-stats .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    
    .current-stats .stat-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
    
    .progress-edit {
        height: 8px;
        border-radius: 4px;
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    /* Danger zone */
    .danger-zone {
        border: 2px solid #dc3545;
        background-color: rgba(220, 53, 69, 0.05);
    }
    
    .danger-zone .form-section-title {
        color: #dc3545;
        border-bottom-color: #dc3545;
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