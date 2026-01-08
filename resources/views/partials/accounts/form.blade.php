@use('Modules\Wallet\Helpers\Helper')
@use('Modules\Wallet\Enums\AccountType')

<form action="{{ $action }}" method="POST" id="accountForm">
  @if(isset($account) && $account->id)
    @method('PUT')
  @endif
  @csrf

  <div class="row">
    <!-- Basic Information -->
    <div class="col-md-6 mb-3">
      <label for="name" class="form-label">Nama Akun <span class="text-danger">*</span></label>
      <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $account->name ?? '') }}" required placeholder="Contoh: BCA Tabungan, OVO, Dompet Tunai">
      @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>
    
    <div class="col-md-6 mb-3">
      <label for="type" class="form-label">Tipe Akun <span class="text-danger">*</span></label>
      <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
            <option value="">Pilih Tipe Akun</option>
            @foreach(\Modules\Wallet\Enums\AccountType::cases() as $type)
              <option value="{{ $type->value }}" @selected(old('type', isset($account) ? $account->type->value : '') == $type->value) data-type="{{ $type->value }}">
                {{ $type->label() }}
              </option>
            @endforeach
          </select>
      @error('type')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>
  </div>

  <div class="row">
    <!-- Balance Information -->
    <div class="col-md-6 mb-3">
      <label for="initial_balance" class="form-label">Saldo Awal</label>
      <div class="input-group">
        <span class="input-group-text" id="currency-symbol">Rp</span>
        <input type="number" class="form-control @error('initial_balance') is-invalid @enderror" id="initial_balance" name="initial_balance" value="{{ old('initial_balance', isset($account) ? $account->initial_balance->getAmount()->toInt() : 0) }}" min="0" placeholder="0">
        <span class="input-group-text">,00</span>
      </div>
      <small class="form-text text-muted">Saldo saat pertama kali menambahkan akun</small>
      @error('initial_balance')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="col-md-6 mb-3">
      <label for="currency" class="form-label">Mata Uang <span class="text-danger">*</span></label>
      <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency" required>
        @foreach(\Modules\Wallet\Helpers\Helper::listCurrencies() as $name => $currency)
          <option value="{{ $name }}" @selected(old('currency', $account->currency ?? 'IDR') == $name)>
            {{ $currency}}
          </option>
        @endforeach
      </select>
      @error('currency')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>
  </div>

  <!-- Bank Information (Conditional) -->
  <div class="row" id="bankInfoSection" style="display: none;">
    <div class="col-md-6 mb-3">
      <label for="account_number" class="form-label">Nomor Akun / Rekening</label>
      <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number', $account->account_number ?? '') }}" placeholder="Contoh: 1234567890">
      @error('account_number')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="col-md-6 mb-3">
      <label for="bank_name" class="form-label">Nama Bank / Penyedia</label>
      <input type="text" class="form-control @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name" value="{{ old('bank_name', $account->bank_name ?? '') }}" placeholder="Contoh: Bank Central Asia, OVO, GoPay">
      @error('bank_name')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>
  </div>

  <div class="row">
    <!-- Visual Customization -->
    <div class="col-md-6 mb-3">
      <label for="color" class="form-label">Warna Akun</label>
      <div class="input-group">
        <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', $account->color ?? '#3490dc') }}" title="Pilih warna untuk akun">
        <button type="button" class="btn btn-outline-secondary" id="resetColor">Reset</button>
      </div>
      <small class="form-text text-muted">Warna akan digunakan untuk tampilan visual akun</small>
      @error('color')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Icon</label>
      <div class="input-group">
        <input type="text" class="form-control" name="icon" id="icon" value="bi-wallet" readonly disabled>
        <input type="hidden" name="color" value="#3490dc" id="color-account">
        <div class="account-icon-preview" style="background-color: {{ old('color', $account->color ?? '#3490dc') }}20; color: {{ old('color', $account->color ?? '#3490dc') }}">
              <i id="iconPreview" class="bi {{ old('icon', $account->icon ?? 'bi-wallet') }}"></i>
            </div>
      </div>
    </div>
  </div>

  <!-- Status Settings -->
  <div class="row">
    <div class="col-md-6 mb-3">
      <div class="card border-0 bg-light">
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" @checked(old('is_active', $account->is_active ?? true))>
            <label class="form-check-label fw-semibold" for="is_active">
              <i class="bi bi-check-circle text-success me-2"></i>Aktifkan Akun
            </label>
            <p class="text-muted small mb-0 mt-1">Akun tidak aktif tidak akan muncul dalam perhitungan total</p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 mb-3">
      <div class="card border-0 bg-light">
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" @checked(old('is_default', $account->is_defaultb?? false))>
            <label class="form-check-label fw-semibold" for="is_default">
              <i class="bi bi-star text-warning me-2"></i>Jadikan Akun Default
            </label>
            <p class="text-muted small mb-0 mt-1">Akun default akan dipilih otomatis saat membuat transaksi baru</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notes -->
  <div class="mb-4">
    <label for="notes" class="form-label">Catatan (Opsional)</label>
    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" placeholder="Tambahkan catatan atau keterangan tentang akun ini...">{{ old('notes', $account->notes ?? '') }}</textarea>
    <small class="form-text text-muted">Contoh: Rekening untuk kebutuhan sehari-hari, tabungan darurat, dll.</small>
    @error('notes')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <!-- Form Actions -->
  <div class="d-flex justify-content-between align-items-center border-top pt-4">
    <div>
      @if(isset($account) && $account->id)
        <small class="text-muted">
          <i class="bi bi-clock-history me-1"></i>
          Terakhir diperbarui: {{ $account->updated_at->translatedFormat('d F Y H:i') }}
        </small>
      @endif
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
        <i class="bi bi-arrow-left me-1"></i>Kembali
      </button>
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-2"></i>
        {{ isset($account) && $account->id ? 'Perbarui Akun' : 'Simpan Akun' }}
      </button>
    </div>
  </div>
</form>

@push('scripts')
<script>
  const accountTypeMap = @json(Helper::accountTypeMap());
  const typeSelect = document.getElementById('type');
  const bankInfoSection = document.getElementById('bankInfoSection');
  // Icon preview update
  const iconInput = document.getElementById('icon');
  const iconPreview = document.getElementById('iconPreview');
  const colorInput = document.getElementById('color');
  const colorAccount = document.getElementById('color-account');
  
  
  // Toggle bank info section based on account type
  function toggleBankInfo() {
    const selectedType = typeSelect?.value;
    const showBankInfo = ['bank', 'credit_card', 'ewallet'].includes(selectedType);

    if (showBankInfo) {
      bankInfoSection.style.display = 'block';
    } else {
      bankInfoSection.style.display = 'none';
    }
  }
    
  function updateIconPreviewColor() {
    const preview = document.querySelector('.account-icon-preview');
    preview.style.backgroundColor = colorInput?.value + '20';
    preview.style.color = colorInput?.value;
    colorAccount.value = colorInput?.value;
  }
    
  document.addEventListener('DOMContentLoaded', function() {
    // Set initial state
    toggleBankInfo();
        
    // Listen for changes
    typeSelect.addEventListener('change', function() {
      toggleBankInfo();
      
      if(typeSelect.value) {
        const item = accountTypeMap[typeSelect.value];
        iconInput.value = item.icon;
        iconPreview.className = item.icon;
        colorAccount.value = item.color;
        colorInput.value = item.color;
        
        updateIconPreviewColor();
      }
    });
        
    // Color reset button
    const resetColorBtn = document.getElementById('resetColor');
    resetColorBtn.addEventListener('click', function() {
      colorInput.value = accountTypeMap[typeSelect.value].color ?? '#3490dc';
      updateIconPreviewColor();
    });
        
    // Update icon preview color when color changes
    colorInput.addEventListener('input', updateIconPreviewColor);
        
        
    // Initialize icon preview
    updateIconPreviewColor();
        
    // Form validation
    const form = document.getElementById('accountForm');
    form.addEventListener('submit', function(e) {
      // Convert initial balance to minor units
      const initialBalanceInput = document.getElementById('initial_balance');
      if (initialBalanceInput.value) {
        // Multiply by 100 to convert to minor units
        const value = parseFloat(initialBalanceInput.value);
        if (!isNaN(value)) {
          // The MoneyCast will handle conversion, but we need to ensure it's a number
          initialBalanceInput.value = value;
        }
      }
            
      // Show loading state
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';
      submitBtn.disabled = true;
            
      // The form will submit normally
    });
        
    // Format currency on blur
    const initialBalanceInput = document.getElementById('initial_balance');
    initialBalanceInput.addEventListener('blur', function() {
      const value = parseFloat(this.value);
      if (!isNaN(value)) {
        this.value = value.toLocaleString('id-ID', {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        });
      }
    });
        
    initialBalanceInput.addEventListener('focus', function() {
      const value = parseFloat(this.value.replace(/\./g, ''));
      if (!isNaN(value)) {
        this.value = value;
      }
    });
  });
</script>

<style>
    .account-icon-preview {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: all 0.3s;
    }
    
    .form-control-color {
        height: 45px;
    }
</style>
@endpush