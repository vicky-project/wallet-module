@php
  $isEdit = isset($account);
  $action = $isEdit 
    ? route('apps.accounts.update', $account)
        : route('apps.accounts.store');
  $method = $isEdit ? 'PUT' : 'POST';
@endphp

<form action="{{ $action }}" method="POST" id="accountForm">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="row">
    <div class="col-md-6">
      <div class="mb-3">
        <label for="name" class="form-label">Nama Akun <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $account->name ?? '') }}" placeholder="Contoh: BCA Tabungan, Dana Cash, OVO" required>
        @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label for="type" class="form-label">Jenis Akun <span class="text-danger">*</span></label>
        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required onchange="updateAccountPreview(this.value)">
          <option value="">Pilih jenis akun...</option>
          @foreach(\Modules\Wallet\Enums\AccountType::cases() as $accountType)
            <option value="{{ $accountType }}" 
              {{ old('type', $account->type->value ?? '') == $accountType->value ? 'selected' : '' }}>
              {{ $accountType->name ?? $accountType->value }}
            </option>
          @endforeach
        </select>
        @error('type')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="mb-3">
        <label for="bank_name" class="form-label">Nama Bank/Institusi</label>
        <input type="text" class="form-control @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name" value="{{ old('bank_name', $account->bank_name ?? '') }}" placeholder="Contoh: BCA, Mandiri, Dana, OVO">
        @error('bank_name')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label for="account_number" class="form-label">Nomor Akun/Rekening</label>
        <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number', $account->account_number ?? '') }}" placeholder="Contoh: 1234567890">
        @error('account_number')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="mb-3">
        <label for="initial_balance" class="form-label">Saldo Awal</label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="number" class="form-control @error('initial_balance') is-invalid @enderror" id="initial_balance" name="initial_balance" value="{{ old('initial_balance', isset($account) ? $account->initial_balance_float : 0) }}" placeholder="0" min="0">
        </div>
        @error('initial_balance')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">Saldo saat pertama kali menambahkan akun</small>
      </div>
    </div>
    <div class="col-md-6">
      <div class="mb-3">
        <label for="current_balance" class="form-label">Saldo Saat Ini</label>
        <div class="input-group">
          <span class="input-group-text">Rp</span>
          <input type="number" class="form-control @error('current_balance') is-invalid @enderror" id="current_balance" name="current_balance" value="{{ old('current_balance', isset($account) ? $account->current_balance_float : 0) }}" placeholder="0" min="0" @if($isEdit) readonly @endif>
        </div>
        @error('current_balance')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">
          @if($isEdit)
          Saldo saat ini (otomatis terupdate dari transaksi)
          @else
          Akan sama dengan saldo awal
          @endif
        </small>
      </div>
    </div>
  </div>

  <!-- Account Preview -->
  <div class="card mb-4">
    <div class="card-body">
      <h6 class="mb-3">Preview Akun</h6>
      <div class="d-flex align-items-center">
        <div id="accountIconPreview" class="me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 12px; background: {{ $account->color ?? '#3b82f6' }}; color: white;">
          <i id="accountIcon" class="bi {{ $account->icon_class ?? 'bi-bank' }} fs-3"></i>
        </div>
        <div>
          <div class="d-flex align-items-center mb-1">
            <h5 id="accountNamePreview" class="mb-0 me-2">{{ $account->name ?? 'Nama Akun' }}</h5>
            <span id="accountTypeBadge" class="badge" style="background: {{ $account->color ?? '#3b82f6' }};">
                {{ $account->type_label ?? 'Bank' }}
            </span>
          </div>
          <p id="accountBankPreview" class="text-muted mb-0">
            {{ $account->bank_name ?? 'Nama Bank' }}
            @if(isset($account) && $account->account_number) • {{ $account->account_number }}
            @endif
          </p>
        </div>
      </div>
    </div>
  </div>

  <div class="form-check form-switch mb-4">
    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1"
      {{ old('is_default', $account->is_default ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_default">
      <strong>Jadikan sebagai akun default</strong>
      <br>
      <small class="text-muted">Akun default akan digunakan untuk transaksi otomatis</small>
    </label>
  </div>

  @if($isEdit)
  <div class="alert alert-info">
    <i class="bi bi-info-circle"></i> 
    <strong>Informasi:</strong> Perubahan saldo awal tidak akan mempengaruhi saldo saat ini. 
    Untuk menyesuaikan saldo, gunakan transaksi deposit atau withdraw.
  </div>
  @endif
</form>

@push('scripts')
<script>
// Map untuk icon dan warna berdasarkan tipe akun
const accountTypeMap = {
    'cash': { 
        icon: 'bi-cash-stack', 
        color: '#10b981', 
        label: 'Uang Tunai',
        bankPlaceholder: 'Tidak ada (uang tunai)'
    },
    'bank': { 
        icon: 'bi-bank', 
        color: '#3b82f6', 
        label: 'Bank',
        bankPlaceholder: 'Contoh: BCA, Mandiri, BNI'
    },
    'ewallet': { 
        icon: 'bi-phone', 
        color: '#8b5cf6', 
        label: 'E-Wallet',
        bankPlaceholder: 'Contoh: Dana, OVO, GoPay'
    },
    'credit_card': { 
        icon: 'bi-credit-card', 
        color: '#ef4444', 
        label: 'Kartu Kredit',
        bankPlaceholder: 'Contoh: BCA Credit, Mandiri Credit'
    },
    'investment': { 
        icon: 'bi-graph-up', 
        color: '#f59e0b', 
        label: 'Investasi',
        bankPlaceholder: 'Contoh: Reksadana, Saham, Crypto'
    }
};

function updateAccountPreview(type) {
    const preview = accountTypeMap[type] || { 
        icon: 'bi-wallet', 
        color: '#6366f1', 
        label: 'Lainnya',
        bankPlaceholder: 'Nama institusi'
    };
    
    // Update icon dan warna
    const iconPreview = document.getElementById('accountIconPreview');
    const iconElement = document.getElementById('accountIcon');
    if (iconPreview && iconElement) {
        iconPreview.style.backgroundColor = preview.color;
        iconElement.className = `bi ${preview.icon} fs-3`;
    }
    
    // Update badge
    const typeBadge = document.getElementById('accountTypeBadge');
    if (typeBadge) {
        typeBadge.textContent = preview.label;
        typeBadge.style.backgroundColor = preview.color;
    }
    
    // Update placeholder bank name
    const bankInput = document.getElementById('bank_name');
    if (bankInput && !bankInput.value) {
        bankInput.placeholder = preview.bankPlaceholder;
    }
    
    // Update placeholder account number
    const accountNumberInput = document.getElementById('account_number');
    if (accountNumberInput) {
        if (type === 'cash') {
            accountNumberInput.placeholder = 'Tidak diperlukan untuk uang tunai';
            accountNumberInput.disabled = true;
            accountNumberInput.value = '';
        } else {
            accountNumberInput.placeholder = 'Contoh: 1234567890';
            accountNumberInput.disabled = false;
        }
    }
}

// Auto-set current balance equal to initial balance for new accounts
if (!@json($isEdit ?? false)) {
    document.getElementById('initial_balance')?.addEventListener('input', function() {
        const currentBalanceInput = document.getElementById('current_balance');
        if (currentBalanceInput) {
            currentBalanceInput.value = this.value;
        }
    });
}

// Update preview saat input nama berubah
document.getElementById('name')?.addEventListener('input', function() {
    const previewElement = document.getElementById('accountNamePreview');
    if (previewElement && this.value) {
        previewElement.textContent = this.value;
    }
});

// Update preview saat input bank berubah
document.getElementById('bank_name')?.addEventListener('input', function() {
    const previewElement = document.getElementById('accountBankPreview');
    if (previewElement) {
        let text = this.value || 'Nama Bank';
        const accountNumber = document.getElementById('account_number')?.value;
        if (accountNumber) {
            text += ` • ${accountNumber}`;
        }
        previewElement.textContent = text;
    }
});

// Update preview saat input account number berubah
document.getElementById('account_number')?.addEventListener('input', function() {
    const previewElement = document.getElementById('accountBankPreview');
    if (previewElement) {
        const bankName = document.getElementById('bank_name')?.value || 'Nama Bank';
        let text = bankName;
        if (this.value) {
            text += ` • ${this.value}`;
        }
        previewElement.textContent = text;
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    if (typeSelect && typeSelect.value) {
        updateAccountPreview(typeSelect.value);
    } else if (typeSelect) {
        // Set default to bank
        typeSelect.value = 'bank';
        updateAccountPreview('bank');
    }
});
</script>
@endpush