@extends('wallet::layouts.app')

@section('title', 'Create Transaction')

@use('Modules\Wallet\Helpers\Helper')
@use('Modules\Wallet\Enums\TransactionType')
@use('Modules\Wallet\Enums\CategoryType')
@use('Modules\Wallet\Enums\PaymentMethod')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <a href="{{ route('apps.transactions.index') }}" class="btn btn-outline-secondary" role="button">
      <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
  </div>
  <h1 class="page-title">Buat Transaksi</h1>
</div>

<!-- Transaction Form -->
<form method="POST" action="{{ route('apps.transactions.store') }}" id="transactionForm">
  @csrf
  <div class="row">
    <!-- Left Column - Basic Information -->
    <div class="col-lg-8">
      <!-- Transaction Type Section -->
      <div class="form-section">
        <h5>Type</h5>
        <div class="type-toggle mb-4">
          @foreach(TransactionType::cases() as $type)
            <input type="radio" id="type_{{ $type->value }}" name="type" value="{{ $type->value }}" @checked(old('type', $preset['type']) === $type->value)>
            <label for="type_{{ $type->value }}" class="text-{{ $type->value }}">
              @switch($type->value)
                @case('income')
                <i class="bi bi-arrow-up-circle me-2"></i>{{ $type->name}}
                @break
                @case('expense')
                <i class="bi bi-arrow-down-circle me-2"></i>{{ $type->name }}
                @break
              @endswitch
            </label>
          @endforeach
        </div>
        
        <!-- Amount Input -->
        <div class="mb-4">
          <label for="amount" class="form-label">Amount<span class="text-danger">*</span></label>
          <div class="input-group amount-input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control amount-input @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $preset['amount']) }}" placeholder="0" required>
          </div>
          @error('amount')
          <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
        </div>
        
        <!-- Title and Description -->
        <div class="row">
          <div class="col-md-4 mb-3">
            <label for="transaction_date" class="form-label">Date</label>
            <input type="datetime-local" class="form-control @error('transaction_date') is-invalid @enderror" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}">
            @error('transaction_date')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-8 mb-3">
            <label for="title" class="form-label">Title<span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $preset['title']) }}" placeholder="Ex: Gaji Bulanan, Belanja Bulanan" required>
            @error('title')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
        </div>
        
        <!-- Description -->
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control @error('description') @enderror" id="description" name="description" rows="3" placeholder="Tambahkan catatan untuk transaksi ini...">{{ old('description') }}</textarea>
          @error('description')
          <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
      
      <!-- Category Selection -->
      <div class="form-section">
        <h5>Pilih Kategori</h5>
        
        <!-- Income Category hidden by default -->
        <div id="incomeCategories" class="category-options" style="{{ old('type', $preset['type']) == CategoryType::INCOME->value ? '' : 'display: none;' }}">
          @forelse($incomeCategories as $category)
          <label class="category-option">
            <input type="radio" name="category_id" value="{{ $category->id}}" @checked(old('category_id', $preset['category_id']) == $category->id)>
            <div class="d-flex align-items-center w-100">
              <div class="category-icon">
                <i class="{{ $category->icon }}"></i>
              </div>
              <div class="flex-grow-1">
                <div class="fw-medium">{{ $category->name }}</div>
                @if($category->budget_limit)
                <small class="text-muted">Anggaran: {{ $category->formatted_budget_limit }}</small>
                @endif
              </div>
            </div>
          </label>
          @empty
          <div class="text-center py-4">
            <i class="bi bi-tag display-4 text-muted"></i>
            <p class="text-muted mt-2">Belum ada kategori {{ CategoryType::INCOME->value}}</p>
            <a href="{{ route('apps.categories.create', ['type' => CategoryType::INCOME->value]) }}" class="btn btn-sm btn-primary">Create Category</a>
          </div>
          @endforelse
        </div>
        
        <!-- Expense Category shown by default -->
        <div id="expenseCategories" class="category-options" style="{{ old('type', $preset['type']) == CategoryType::EXPENSE->value ? '' : 'display: none;' }}">
          @forelse($expenseCategories as $category)
          <label class="category-option">
            <input type="radio" name="category_id" value="{{ $category->id}}" @checked(old('category_id', $preset['category_id']) == $category->id)>
            <div class="d-flex align-items-center w-100">
              <div class="category-icon">
                <i class="{{ $category->icon }}"></i>
              </div>
              <div class="flex-grow-1">
                <div class="fw-medium">{{ $category->name }}</div>
                @if($category->budget_limit)
                <small class="text-muted">Anggaran: {{ $category->formatted_budget_limit }}</small>
                @endif
              </div>
            </div>
          </label>
          @empty
          <div class="text-center py-4">
            <i class="bi bi-tag display-4 text-muted"></i>
            <p class="text-muted mt-2">Belum ada kategori {{ CategoryType::EXPENSE->value }}</p>
            <a href="{{ route('apps.categories.create', ['type' => CategoryType::EXPENSE->value]) }}" class="btn btn-sm btn-primary">Create Category</a>
          </div>
          @endforelse
        </div>
        
        @error('category_id')
        <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror
      </div>
    </div>
    
    <!-- Right Column - additional Information -->
    <div class="col-lg-4">
      <!-- Account Selection -->
      
      <div class="form-section">
        <h5>Akun</h5>
        <div class="mb-3">
          <label for="account_id" class="form-label">Pilih Akun<span class="text-danger">*</span></label>
          <select name="account_id" id="account_id" class="form-select @error('account_id') is-invalid @enderror" required>
            @forelse($accounts as $account)
            <option value="{{ $account->id }}" @selected(old('account_id', $preset['account_id']) == $account->id) data-balance="{{ $account->formatted_balance }}">
              {{ $account->name }} ({{ $account->formatted_balance }})
            </option>
            @empty
            <option value="">No Account</option>
            @endforelse
          </select>
          @if($accounts->count() == 0)
          <div class="small text-danger mt-1">
            <a href="{{ route('apps.accounts.create') }}">create account</a>
          </div>
          @endif
          @error('account_id')
          <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
          
          <div class="mt-2" id="accountBalance">
            <!-- Account balance will be shown here -->
          </div>
        </div>
      </div>
      
      <!-- Payment Method -->
      <div class="form-section">
        <h5>Metode Pembayaran</h5>
        <div class="mb-3">
          <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
            @foreach(PaymentMethod::cases() as $payment)
            <option value="{{ $payment->value}}" @selected(old('payment_method') == $payment->value)>{{ $payment->name }}</option>
            @endforeach
          </select>
          @error('payment_method')
          <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
      
      <!-- Recurring Transaction -->
      <div class="form-section">
        <h5>Transaksi Rutin</h5>
        <div class="mb-3">
          <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" role="switch" id="is_recurring" name="is_recurring" value="1" @checked(old('is_recurring'))>
            <label for="is_recurring" class="form-check-label">Jadikan Transaksi Rutin</label>
          </div>
          <div id="recurringOptions" style="display: none;margin-top: 1rem;">
            <div class="mb-3">
              <label for="recurring_period" class="form-label">Period</label>
              <select class="form-select" name="recurring_period" id="recurring_period">
                <option value="">Pilih Period</option>
                <option value="daily" @selected(old('recurring_period') == 'daily')>Harian</option>
                <option value="weekly" @selected(old('recurring_period') == 'weekly')>Mingguan</option>
                <option value="monthly" @selected(old('recurring_period') == 'monthly')>Bulanan</option>
                <option value="yearly" @selected(old('recurring_period') == 'yearly')>Tahunan</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label for="recurring_end_date" class="form-label">Tanggal Berakhir (Optional)</label>
              <input type="date" class="form-control" name="recurring_end_date" id="recurring_end_date" value="{{ old('recurring_end_date') }}">
              <small class="text-muted">Biarkan kosong jika tidak ada tanggal berakhir</small>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Submit Button -->
      <div class="form-section">
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-circle me-2"></i>Simpan Transaksi
          </button>
          <button type="button" class="btn btn-outline-secondary" onclick="previewTransaction();">
            <i class="bi bi-eye me-2"></i>Pratinjau
          </button>
        </div>
      </div>
    </div>
  </div>
</form>

<div class="modal fade" id="previewModal" tabindex="1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pratinjau Transaksi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="previewContent">
          <!-- Preview Content will be inserted here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" onclick="submitForm();">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Submit form from preview
  function submitForm() {
    document.getElementById('transactionForm').submit();
  }
    
  function getPaymentMethodLabel(method) {
    const labels = {
      'cash': 'Tunai',
      'bank': 'Bank',
      'credit_card': 'Kartu Kredit',
      'ewallet': 'E-Wallet',
      'other': 'Lainnya'
    };
      
    return labels[method] || method;
  }
    
  // Preview Transaction
  function previewTransaction() {
    const form = document.getElementById('transactionForm');
    const formData = new FormData(form);
      
    // Get Value
    const type = document.querySelector('input[name="type"]:checked')?.value || '';
    const amount = document.getElementById('amount').value;
    const title = document.getElementById('title').value;
    const date = document.getElementById('transaction_date').value;
    const categoryId = document.querySelector('input[name="category_id"]:checked')?.value;
    const accountId = document.getElementById('account_id').value;
    const paymentMethod = document.getElementById('payment_method').value;
    const description = document.getElementById('description').value;
      
    // Find Category Name
    let categoryName = '';
    if(categoryId){
      const categoryOption = d.querySelector(`input[name="category_id"][value="${categoryId}"]`);
        
      if(categoryOption) {
        const categoryDiv = categoryOption.nextElementSibling;
        categoryName = categoryDiv.querySelector('.fw-medium')?.textContent || '';
      }
    }
      
    // Find Account Name
    let accountName = '';
    if(accountId) {
      const accountOption = document.getElementById('account_id').options[accountId];
      accountName = accountOption ? accountOption.textContent.split('(')[0].trim() : '';
    }
      
    // Format date
    const dateObj = new Date(date);
    const formattedDate = dateObj.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
      
    // Build preview HTML
    const previewHTML = `
    <div class="text-center mb-4">
      <div class="transaction-icon ${type === 'income' ? 'bg-income' : 'bg-expense'}" style="width: 60px;height: 60px;margin: 0 auto 1rem;">
        <i class="bi ${type === 'income' ? 'bi-arrow-up-circle text-income' : 'bi-arrow-down-circle text-expense'}" style="font-size: 1.5rem;"></i>
      </div>
      <h4 class="${type === 'income' ? 'text-income' : 'text-expense'}">${type === 'income' ? 'Income' : 'Expense'}</h4>
    </div>
    <div class="row">
      <div class="col-6">
        <small class="text-muted d-block">Jumlah</small>
        <h5 class="fw-bold ${type === 'income' ? 'text-income' : 'text-expense'}">${type === 'income' ? '+' : '-'}Rp ${amount || 0}</h5>
      </div>
      <div class="col-6 text-end">
        <small class="text-muted d-block">Tanggal</small>
        <div class="fw-medium">${formattedDate}</div>
      </div>
    </div>
    <hr>
    <div class="mb-3">
      <small class="text-muted d-block">Title</small>
      <div class="fw-medium">${title || '-'}</div>
    </div>
    ${description ? `<div class="mb-3">
      <small class="d-block text-muted">Deskripsi</small>
      <div>${description}</div>
    </div>` : ''}
    <div class="row">
      <div class="col-6">
        <small class="text-muted d-block">Kategori</small>
        <div class="fw-medium">${categoryName || '-'}</div>
      </div>
      <div class="col-6">
        <small class="text-muted d-block">Akun</small>
        <div class="fw-medium">${accountName}</div>
      </div>
    </div>
    <div class="mt-3">
      <small class="text-muted d-block">Metode Pembayaran</small>
      <div class="fw-medium">${getPaymentMethodLabel(paymentMethod) || '-'}</div>
    </div>
    `;
      
    document.getElementById('previewContent').innerHTML = previewHTML;
      
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
      modal.show();
  }
    
  document.addEventListener("DOMContentLoaded", function () {
    const amountInput = document.getElementById('amount');
    
    amountInput.addEventListener('input', function (e) {
      let value = e.target.value;
      if(value) {
        value = parseInt(value).toLocaleString('id-ID', {
          style: 'currency',
          curency: 'id-ID',
          useGrouping: 'auto'
        });
      }
      
      e.target.value = value;
    });
    
    // Toggle Category section based on type
    const typeIncome = document.getElementById('type_{{ CategoryType::INCOME }}');
    const typeExpense = document.getElementById('type_{{ CategoryType::EXPENSE }}');
    const incomeCategories = document.getElementById('incomeCategories');
    const expenseCategories = document.getElementById('expenseCategories');
    
    function toggleCategories() {
      if(typeIncome.checked) {
        incomeCategories.style.display = 'block';
        expenseCategories.style.display = 'none';
        
        document.querySelectorAll('#expenseCategories input[type="radio"]').forEach(radio => {
          radio.checked = false;
        });
      } else {
        incomeCategories.style.display = 'none';
        expenseCategories.style.display = 'block';
        document.querySelectorAll('#incomeCategories input[type="radio"]').forEach(radio => {
          radio.checked = false;
        });
      }
    }
    
    typeIncome.addEventListener('change', toggleCategories);
    typeExpense.addEventListener('change', toggleCategories);
    
    // Initialize
    toggleCategories();
    
    // Toggle recurring options
    const isRecurring = document.getElementById('is_recurring');
    const recurringOptions = document.getElementById('recurringOptions');
    
    isRecurring.addEventListener('change', function () {
      if(this.checked) {
        recurringOptions.style.display = 'block';
      } else {
        recurringOptions.style.display = 'none';
      }
    });
    
    // Show Account Balance
    const accountSelect = document.getElementById('account_id');
    const accountBalance = document.getElementById('accountBalance');
    
    accountSelect.addEventListener('change', function () {
      const selectedOption = this.options[this.selectedIndex];
      const balance = selectedOption.getAttribute('data-balance');
      
      if(balance) {
        accountBalance.innerHTML = `
        <div class="alert alert-info p-2">
          <small>
            <i class="bi bi-wallet me-1"></i>
            Saldo saat ini: <strong>${balance}</strong>
          </small>
        </div>
        `;
      } else {
        accountBalance.innerHTML = "";
      }
    });
    
    if(accountSelect.value) {
      accountSelect.dispatchEvent(new Event('change'));
    }
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('transaction_date').value = today;
  });
</script>
@endpush

@push('styles')
<style>
  .form-section {
    background-color: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }
  
  .form-section h5 {
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dee2e6;
  }
  
  .amount-input-group {
    position: relative;
  }
  
  .amount-input-group .input-group-text {
    background-color: #f8f9fa;
    border-right: none;
  }
  
  .amount-input-group input {
    border-left: none;
    padding-left: 0;
  }
  
  .type-toggle {
    display: flex;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
  }
  
  .type-toggle label {
    flex: 1;
    padding: 0.75rem;
    text-align: center;
    cursor: pointer;
    margin: 0;
    transition: all 0.3s;
  }
  
  .type-toggle input[type="radio"]{
    display: none;
  }
  
  .type-toggle input[type="radio"]:checked + label {
    background-color: #4361ee;
    color: white;
  }
  
  .type-toggle label[for="{{ CategoryType::INCOME->value }}"] {
    border-right: 1px solid #dee2e6;
  }
  
  .category-options {
    max-height: 300px;
    overflow-y: auto;
  }
  
  .category-option {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .category-option:hover {
    background-color: #f8f9fa;
    border-color: #4361ee;
  }
  
  .category-option input[type="radio"] {
    display: none;
  }
  
  .category-option input[type="radio"]:checked + div {
    background-color: #4361ee;
    border-color: #4361ee;
  }
  
  .category-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.2rem;
  }
  
  body[data-bs-theme="dark"] .form-section {
    background-color: #1e1e1e;
  }
  
  body[data-bs-theme="dark"] .type-toggle {
    border-color: #495057;
  }
  
  body[data-bs-theme="dark"] .category-option:hover {
    background-color: #2d3748;
  }
</style>
@endpush