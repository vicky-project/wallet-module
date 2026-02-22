@extends('core::layouts.app')

@section('title', isset($account) ? 'Edit Akun' : 'Tambah Akun')

@section('content')
<div class="row justify-content-center">
  <div class="col-12">
    <div class="card border-0 shadow-sm" style="background-color: var(--tg-theme-section-bg-color);">
      <div class="card-header py-3" style="background-color: transparent; border-bottom: 1px solid var(--tg-theme-section-separator-color);">
        <h5 class="card-title mb-0 fw-bold" style="color: var(--tg-theme-section-header-text-color);">
          <i class="bi bi-wallet2 me-2" style="color: var(--tg-theme-accent-text-color);"></i>
          {{ isset($account) ? 'Edit Akun' : 'Tambah Akun Baru' }}
        </h5>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="{{ isset($account) ? route('financial.account.update', $account->id) : route('financial.account.store') }}">
          @csrf
          @if(isset($account))
            @method('PUT')
          @endif
          
          <!-- Preview Ikon -->
          <div class="text-center mb-4">
            <div class="d-inline-block p-3 rounded-circle" style="background-color: {{ old('color', $account->color ?? '#40a7e3') }}20; border: 2px solid {{ old('color', $account->color ?? '#40a7e3') }};">
              <i class="bi {{ old('icon', $account->icon ?? 'bi-wallet') }} fs-1" id="iconPreview" style="color: {{ old('color', $account->color ?? '#40a7e3') }};"></i>
            </div>
          </div>
          <input type="hidden" name="icon" id="iconInput" value="{{ old('icon', $account->icon ?? 'bi-wallet') }}">

          <div class="row g-4">
            <!-- Nama Akun -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-tag me-1" style="color: var(--tg-theme-accent-text-color);"></i>Nama Akun
              </label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $account->name ?? '') }}" placeholder="Contoh: Kas Harian" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Tipe Akun -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-grid me-1" style="color: var(--tg-theme-accent-text-color);"></i>Tipe Akun
              </label>
              <select id="accountType" name="type" class="form-select @error('type') is-invalid @enderror" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                <option value="">Pilih Tipe</option>
                @foreach(\Modules\Wallet\Enums\AccountType::cases() as $type)
                  <option value="{{ $type->value ?? $type }}" data-icon="{{ $type->icon() }}" data-color="{{ $type->color() ?? '' }}" data-label="{{ $type->label() }}" data-bank="{{ in_array($type->value, ['bank', 'credit_card', 'ewallet']) 'yes' : 'no'}}" @selected(old('type', isset($account) ? $account->type->value : '') == $type->value)>
                    {{ ucfirst($type->label() ?? $type) }}
                  </option>
                @endforeach
              </select>
              @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Saldo Awal (hanya untuk create) -->
            @if(!isset($account))
              <div class="col-md-6">
                <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                  <i class="bi bi-cash me-1" style="color: var(--tg-theme-accent-text-color);"></i>Saldo Awal
                </label>
                <input type="number" class="form-control @error('initial_balance') is-invalid @enderror" name="initial_balance" value="{{ old('initial_balance', 0) }}" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                @error('initial_balance')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            @endif

            <!-- Mata Uang -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-currency-exchange me-1" style="color: var(--tg-theme-accent-text-color);"></i>Mata Uang
              </label>
              <select name="currency" class="form-select" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                @foreach(\Modules\Wallet\Helpers\Helper::listCurrencies() as $name => $currency)
                  <option value="{{ $name }}" @selected(old('currency', $account->currency ?? 'IDR') == $name)>
                    {{ $currency}}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="row g-4" id="bankFields" style="{{ in_array(old('type', $account->type ?? ''), ['bank', 'credit_card', 'ewallet']) ? '' : 'display: none;' }}">
              <!-- Nomor Rekening -->
              <div class="col-md-6">
                <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                  <i class="bi bi-credit-card me-1" style="color: var(--tg-theme-accent-text-color);"></i>Nomor Rekening (opsional)
                </label>
                <input type="text" class="form-control" name="account_number" value="{{ old('account_number', $account->account_number ?? '') }}" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
              </div>
            
              <!-- Nama Bank -->
              <div class="col-md-6">
                <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                  <i class="bi bi-bank me-1" style="color: var(--tg-theme-accent-text-color);"></i>Nama Bank (opsional)
                </label>
                <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $account->bank_name ?? '') }}" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
              </div>
            </div>

            <!-- Warna -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-palette me-1" style="color: var(--tg-theme-accent-text-color);"></i>Warna
              </label>
              <input type="color" id="colorPicker" class="form-control form-control-color" name="color" value="{{ old('color', $account->color ?? '#40a7e3') }}" style="width: 100%; height: 45px;">
            </div>

            <!-- Status Aktif dan Default (dalam satu baris) -->
            <div class="col-12">
              <div class="d-flex gap-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" @checked(old('is_active', $account->is_active ?? true))>
                  <label class="form-check-label" for="isActive" style="color: var(--tg-theme-text-color);">
                    Akun Aktif
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefault" @checked(old('is_default', $account->is_default ?? false))>
                  <label class="form-check-label" for="isDefault" style="color: var(--tg-theme-text-color);">
                    Akun Utama
                  </label>
                </div>
              </div>
            </div>

            <!-- Catatan -->
            <div class="col-12">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-pencil me-1" style="color: var(--tg-theme-accent-text-color);"></i>Catatan (opsional)
              </label>
              <textarea class="form-control" name="notes" rows="3" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">{{ old('notes', $account->notes ?? '') }}</textarea>
            </div>
          </div>

          <!-- Tombol Aksi -->
          <div class="row mt-5">
            <div class="col-12 d-flex justify-content-between">
              <a href="{{ route('financial.index') }}" class="btn px-4 py-2" style="background-color: transparent; color: var(--tg-theme-button-color); border: 1px solid var(--tg-theme-button-color);">
                <i class="bi bi-arrow-left me-2"></i>Batal
              </a>
              <button type="submit" class="btn px-4 py-2" style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none;">
                <i class="bi bi-check-circle me-2"></i>{{ isset($account) ? 'Perbarui' : 'Simpan' }}
              </button>
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
  (function() {
    const typeSelect = document.getElementById('accountType');
    const iconPreview = document.getElementById('iconPreview');
    const iconInput = document.getElementById('iconInput');
    const colorPicker = document.getElementById('colorPicker');
    const bankFields = document.getElementById('bankFields');
    
    // Fungsi untuk update ikon dan warna
    function updatePreview() {
      const selectedOption = typeSelect.options[typeSelect.selectedIndex];
      if(selectedOption && selectedOption.value) {
        const icon = selectedOption.getAttribute('data-icon') || 'bi-wallet';
        const color = colorPicker.value;
        iconPreview.className = `bi ${icon} fs-1`;
        iconPreview.style.color = color;
        iconInput.value = icon;
      } else {
        iconPreview.className = 'bi bi-wallet fs-1';
        iconPreview.style.color = colorPicker.value;
        iconInput.value = 'bi-wallet';
      }
    }
    
    function toggleBankFields() {
      const selectedOption = typeSelect.options[typeSelect.selectedIndex];
      if(selectedOption && selectedOption.value) {
        const needBank = selectedOption.getAttribute('data-bank') === 'yes';
        bankFields.style.display = needBank ? '' : 'none';
      } else {
        bankFields.style.display = 'none';
      }
    }
    
    typeSelect.addEventListener('change', function() {
      updatePreview();
      toggleBankFields();
    });
    
    colorPicker.addEventListener('input', function() {
      iconPreview.style.color = this.value;
    });
    
    if(typeSelect.value) {
      updatePreview();
      toggleBankFields();
    } else {
      bankFields.style.display = 'none';
    }
  })();
</script>
@endpush

@push('styles')
<style>
    /* Styling tambahan untuk form */
    .form-control, .form-select {
        border-width: 1px;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--tg-theme-button-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--tg-theme-button-color-rgb, 64, 167, 227), 0.25);
    }
    .form-check-input:checked {
        background-color: var(--tg-theme-button-color);
        border-color: var(--tg-theme-button-color);
    }
    .form-check-input:focus {
        border-color: var(--tg-theme-button-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--tg-theme-button-color-rgb, 64, 167, 227), 0.25);
    }
</style>
@endpush