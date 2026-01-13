@extends('wallet::layouts.app')

@section('title', $transaction ? 'Edit Transaksi' : 'Tambah Transaksi Baru')

@use('Modules\Wallet\Enums\TransactionType')
@use('Modules\Wallet\Enums\PaymentMethod')

@push('styles')
<style>
    .account-option {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        border-radius: 6px;
        margin-bottom: 5px;
        transition: background-color 0.2s;
    }
    
    .account-option:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }
    
    .account-option.selected {
        background-color: rgba(var(--primary-color-rgb), 0.1);
        border-left: 3px solid var(--primary-color);
    }
    
    .account-balance {
        font-size: 0.85rem;
        color: #666;
    }
    
    .form-section {
        background-color: rgba(0, 0, 0, 0.02);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 15px;
        color: var(--primary-color);
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 5px;
    }
    
    .transaction-type-badge {
        font-size: 0.9rem;
        padding: 6px 12px;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    
    .transaction-type-badge.active {
        border-color: currentColor;
        transform: scale(1.05);
    }
    
    .category-icon-preview {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        font-size: 1.2rem;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h2 class="page-title">{{ $transaction ? 'Edit Transaksi' : 'Tambah Transaksi Baru' }}</h2>
            <p class="text-muted mb-0">Isi form berikut untuk {{ $transaction ? 'mengedit' : 'menambahkan' }} transaksi</p>
          </div>
          <div>
            <a href="{{ route('apps.transactions.index') }}" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left me-2"></i> Kembali
            </a>
          </div>
        </div>
                
        @if($transaction)
          <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center">
              <i class="bi bi-info-circle me-3 fs-4"></i>
              <div>
                <strong>ID Transaksi:</strong> {{ $transaction->uuid }}<br>
                <strong>Dibuat:</strong> {{ $transaction->created_at->format('d/m/Y H:i') }}
                @if($transaction->updated_at != $transaction->created_at)
                  <br><strong>Terakhir diubah:</strong> {{ $transaction->updated_at->format('d/m/Y H:i') }}
                @endif
              </div>
            </div>
          </div>
        @endif
                
        <form method="POST" action="{{ $transaction ? route('apps.transactions.update', $transaction->uuid) : route('apps.transactions.store') }}" id="transactionForm">
          @csrf
          @if($transaction) @method('PUT') @endif
                    
          <!-- Transaction Type -->
          <div class="form-section">
            <h5 class="form-section-title">Jenis Transaksi</h5>
            <div class="row">
              <div class="col-md-12">
                <div class="d-flex flex-wrap gap-3">
                  @foreach(TransactionType::cases() as $type)
                    <div class="transaction-type-badge bg-{{ $type->color() }} bg-opacity-10 text-{{ $type->color() }} {{ (!$transaction && request('type') == $type->value) || ($transaction && $transaction->type == $type) ? 'active' : '' }}" data-type="{{ $type->value }}">
                      <i class="bi {{ $type->icon() }} me-2"></i>
                      {{ $type->name }}
                    </div>
                  @endforeach
                </div>
                <input type="hidden" name="type" id="type" value="{{ $transaction ? $transaction->type : (request('type') ?? TransactionType::EXPENSE->value) }}">
                @error('type')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
                    
          <!-- Basic Information -->
          <div class="form-section">
            <h5 class="form-section-title">Informasi Dasar</h5>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="description" class="form-label">Keterangan *</label>
                <input type="text" name="description" id="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $transaction->description ?? '') }}" placeholder="Deskripsi transaksi..." required>
                @error('description')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
                            
              <div class="col-md-6 mb-3">
                <label for="transaction_date" class="form-label">Tanggal & Waktu *</label>
                <input type="datetime-local" name="transaction_date" id="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ old('transaction_date', $transaction ? $transaction->transaction_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required>
                @error('transaction_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
                            
              <div class="col-md-6 mb-3">
                <label for="amount" class="form-label">Jumlah (Rp) *</label>
                <div class="input-group">
                  <span class="input-group-text" id="account-currency">Rp</span>
                  <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $transaction ? $transaction->amount->getAmount()->toInt() : '') }}" min="1" required>
                </div>
                <small class="text-muted" id="amountHelp"></small>
                @error('amount')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
                            
              <div class="col-md-6 mb-3">
                <label for="payment_method" class="form-label">Metode Pembayaran</label>
                <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                  <option value="">Pilih Metode</option>
                  @foreach(PaymentMethod::cases() as $method)
                    <option value="{{ $method->value }}" @selected(old('payment_method', $transaction->payment_method ?? '') == $method->value)>
                      {{ $method->name }}
                    </option>
                  @endforeach
                </select>
                @error('payment_method')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
                    
          <!-- Accounts -->
          <div class="form-section">
            <h5 class="form-section-title">Akun</h5>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="account_id" class="form-label" id="accountLabel">
                  {{ ($transaction && $transaction->type == TransactionType::TRANSFER) || request('type') == TransactionType::TRANSFER->value ? 'Dari Akun *' : 'Akun *' }}
                </label>
                <select name="account_id" id="account_id" class="form-select @error('account_id') is-invalid @enderror" required>
                  <option value="">Pilih Akun</option>
                  @foreach($accounts as $account)
                    <option value="{{ $account->id }}" data-balance="{{ $account->balance->getAmount()->toInt() }}" @selected(old('account_id', $transaction->account_id ?? '') == $account->id)>
                      {{ $account->name }} 
                      (Rp {{ number_format($account->balance->getAmount()->toInt(), 0, ',', '.') }})
                    </option>
                  @endforeach
                </select>
                <small class="text-muted" id="accountBalance"></small>
                @error('account_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
                            
              <div class="col-md-6 mb-3" id="toAccountField" style="{{ ($transaction && $transaction->type == TransactionType::TRANSFER) || request('type') == TransactionType::TRANSFER->value ? '' : 'display: none;' }}">
                <label for="to_account_id" class="form-label">Ke Akun *</label>
                <select name="to_account_id" id="to_account_id" class="form-select @error('to_account_id') is-invalid @enderror">
                  <option value="">Pilih Akun Tujuan</option>
                  @foreach($accounts as $account)
                    <option value="{{ $account->id }}" data-balance="{{ $account->balance->getAmount()->toInt() }}" @selected(old('to_account_id', $transaction->to_account_id ?? '') == $account->id)>
                      {{ $account->name }} 
                      (Rp {{ number_format($account->balance->getAmount()->toInt(), 0, ',', '.') }})
                    </option>
                  @endforeach
                </select>
                <small class="text-muted" id="toAccountBalance"></small>
                @error('to_account_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
                    
          <!-- Category -->
          <div class="form-section">
            <h5 class="form-section-title">Kategori</h5>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="category_id" class="form-label">Kategori *</label>
                <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                  <option value="">Pilih Kategori</option>
                  @foreach($categories as $category)
                    <option value="{{ $category->id }}" data-type="{{ $category->type }}" data-icon="{{ $category->icon }}" @selected(old('category_id', $transaction->category_id ?? '') == $category->id)>
                      <i class="bi {{ $category->icon }} me-2"></i>
                      {{ $category->name }}
                    </option>
                  @endforeach
                </select>
                @error('category_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
                            
              <div class="col-md-6 mb-3" id="budgetInfo" style="display: none;">
                <label class="form-label">Informasi Anggaran</label>
                <div class="card text-bg-light">
                  <div class="card-body py-2">
                    <div id="budgetMessage"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
                    
          <!-- Additional Information -->
          <div class="form-section">
            <h5 class="form-section-title">Informasi Tambahan</h5>
            <div class="row">
              <div class="col-md-12 mb-3">
                <label for="notes" class="form-label">Catatan</label>
                <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="Catatan tambahan...">{{ old('notes', $transaction->notes ?? '') }}</textarea>
                @error('notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
                            
              <div class="col-md-6 mb-3">
                <label for="reference_number" class="form-label">Nomor Referensi</label>
                <input type="text" name="reference_number" id="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number', $transaction->reference_number ?? '') }}" placeholder="No. invoice, kode transaksi, dll.">
                @error('reference_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
                            
              <div class="col-md-6 mb-3">
                <label class="form-label">Transaksi Berulang</label>
                <div class="form-check">
                  <input type="checkbox" name="is_recurring" id="is_recurring" class="form-check-input" value="1" @checked(old('is_recurring', $transaction->is_recurring ?? false))>
                  <label class="form-check-label" for="is_recurring">
                    Jadikan transaksi berulang
                  </label>
                </div>
                <small class="text-muted">Centang jika transaksi ini terjadi secara berkala</small>
              </div>
            </div>
          </div>
                    
          <!-- Submit Buttons -->
          <div class="row mt-4">
            <div class="col-md-12">
              <div class="d-flex justify-content-between">
                <div>
                  <button type="reset" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-2"></i> Reset
                  </button>
                </div>
                <div>
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ $transaction ? 'Simpan Perubahan' : 'Simpan Transaksi' }}
                  </button>
                  @if($transaction)
                    <a href="{{ route('apps.transactions.index') }}" class="btn btn-outline-danger ms-2">
                      <i class="bi bi-x-circle me-2"></i> Batal
                    </a>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
        
  <!-- Quick Stats -->
  @if(!$transaction)
    <div class="row mt-4">
      <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center py-3">
            <h6 class="text-muted mb-1">Total Akun Aktif</h6>
            <h4 class="mb-0">{{ $accounts->count() }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center py-3">
            <h6 class="text-muted mb-1">Total Kategori</h6>
            <h4 class="mb-0">{{ $categories->count() }}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center py-3">
            <h6 class="text-muted mb-1">Transaksi Hari Ini</h6>
            <h4 class="mb-0">{{ $todayTransactions }}</h4>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const typeBadges = document.querySelectorAll('.transaction-type-badge');
        const typeInput = document.getElementById('type');
        const toAccountField = document.getElementById('toAccountField');
        const accountLabel = document.getElementById('accountLabel');
        const accountSelect = document.getElementById('account_id');
        const toAccountSelect = document.getElementById('to_account_id');
        const categorySelect = document.getElementById('category_id');
        const amountInput = document.getElementById('amount');
        const amountHelp = document.getElementById('amountHelp');
        const budgetInfo = document.getElementById('budgetInfo');
        const budgetMessage = document.getElementById('budgetMessage');
        
        // Transaction type selection
        typeBadges.forEach(badge => {
            badge.addEventListener('click', function() {
                const type = this.dataset.type;
                
                // Update active badge
                typeBadges.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update hidden input
                typeInput.value = type;
                
                // Update form based on type
                updateFormForType(type);
            });
        });
        
        // Update form based on transaction type
        function updateFormForType(type) {
            // Show/hide to account field for transfers
            if (type === 'transfer') {
                toAccountField.style.display = 'block';
                accountLabel.textContent = 'Dari Akun *';
                toAccountSelect.required = true;
            } else {
                toAccountField.style.display = 'none';
                accountLabel.textContent = 'Akun *';
                toAccountSelect.required = false;
            }
            
            // Filter categories based on type
            filterCategories(type);
            
            // Update account balance display
            updateAccountBalance();
        }
        
        // Filter categories based on transaction type
        function filterCategories(type) {
            const categoryOptions = categorySelect.options;
            
            for (let i = 0; i < categoryOptions.length; i++) {
                const option = categoryOptions[i];
                if (option.value === '') continue;
                
                const categoryType = option.dataset.type;
                
                if (type === 'transfer') {
                    // Show all categories for transfer
                    option.style.display = '';
                } else {
                    // Show only matching categories
                    option.style.display = (categoryType === type) ? '' : 'none';
                }
            }
            
            // Reset selection if current selection doesn't match
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            if (selectedOption && selectedOption.style.display === 'none') {
                categorySelect.value = '';
                categorySelect.dispatchEvent(new Event('change'));
            }
        }
        
        // Update account balance display
        function updateAccountBalance() {
            const accountOption = accountSelect.options[accountSelect.selectedIndex];
            if (accountOption && accountOption.value) {
                const balance = parseInt(accountOption.dataset.balance) || 0;
                document.getElementById('accountBalance').textContent = 
                    `Saldo saat ini: Rp ${balance.toLocaleString('id-ID')}`;
            } else {
                document.getElementById('accountBalance').textContent = '';
            }
            
            const toAccountOption = toAccountSelect.options[toAccountSelect.selectedIndex];
            if (toAccountOption && toAccountOption.value) {
                const toBalance = parseInt(toAccountOption.dataset.balance) || 0;
                document.getElementById('toAccountBalance').textContent = 
                    `Saldo saat ini: Rp ${toBalance.toLocaleString('id-ID')}`;
            } else {
                document.getElementById('toAccountBalance').textContent = '';
            }
        }
        
        // Check budget when category is selected
        function checkBudget(categoryId, amount, date) {
            if (!categoryId || !amount) return;
            
            fetch("{{ secure_url(config('app.url')) }}/apps/transactions/check-budget", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    category_id: categoryId,
                    amount: amount,
                    date: date
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.has_budget) {
                    budgetInfo.style.display = 'block';
                    
                    let message = '';
                    const usage = Math.round((data.current_spent + amount) / data.budget_amount * 100);
                    
                    if (data.current_spent + amount > data.budget_amount) {
                        message = `
                            <div class="text-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Peringatan:</strong> Anggaran akan terlampaui!
                                <br>
                                <small>Penggunaan: ${usage}% (${data.formatted_spent} + Rp ${amount.toLocaleString('id-ID')} / ${data.formatted_budget_amount})</small>
                            </div>
                        `;
                    } else if (usage >= 80) {
                        message = `
                            <div class="text-warning">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <strong>Perhatian:</strong> Anggaran hampir habis.
                                <br>
                                <small>Penggunaan: ${usage}% (${data.formatted_spent} + Rp ${amount.toLocaleString('id-ID')} / ${data.formatted_budget_amount})</small>
                            </div>
                        `;
                    } else {
                        message = `
                            <div class="text-success">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Anggaran tersedia:</strong> ${data.formatted_budget_amount}
                                <br>
                                <small>Penggunaan: ${usage}% (${data.formatted_spent} + Rp ${amount.toLocaleString('id-ID')} / ${data.formatted_budget_amount})</small>
                            </div>
                        `;
                    }
                    
                    budgetMessage.innerHTML = message;
                } else {
                    budgetInfo.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error checking budget:', error);
            });
        }
        
        // Event Listeners
        accountSelect.addEventListener('change', updateAccountBalance);
        toAccountSelect.addEventListener('change', updateAccountBalance);
        
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            const amount = parseInt(amountInput.value) || 0;
            const date = document.getElementById('transaction_date').value;
            
            if (categoryId && amount > 0) {
                checkBudget(categoryId, amount, date);
            }
        });
        
        amountInput.addEventListener('input', function() {
            const amount = parseInt(this.value) || 0;
            const categoryId = categorySelect.value;
            const date = document.getElementById('transaction_date').value;
            
            if (categoryId && amount > 0) {
                checkBudget(categoryId, amount, date);
            }
            
            // Format amount helper
            amountHelpText(amount);
        });
        
        
        function amountHelpText(amount) {
            if (amount > 0) {
                amountHelp.textContent = `Rp ${amount.toLocaleString('id-ID')}`;
            } else {
                amountHelp.textContent = '';
            }
        }
        // Form validation
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            const type = typeInput.value;
            const amount = parseInt(amountInput.value) || 0;
            const accountId = accountSelect.value;
            const toAccountId = toAccountSelect.value;
            
            // Get account balance
            const accountOption = accountSelect.options[accountSelect.selectedIndex];
            const accountBalance = parseInt(accountOption?.dataset.balance) || 0;
            
            // Validate minimum amount
            if (amount < 1) {
                e.preventDefault();
                alert('Jumlah transaksi harus lebih dari 0.');
                return;
            }
            
            // Validate account selection
            if (!accountId) {
                e.preventDefault();
                alert('Harap pilih akun.');
                return;
            }
            
            // Check balance for expense or transfer
            if ((type === 'expense' || type === 'transfer') && amount > accountBalance) {
                e.preventDefault();
                alert('Saldo akun tidak mencukupi untuk transaksi ini.');
                return;
            }
            
            // Validate to account for transfer
            if (type === 'transfer') {
                if (!toAccountId) {
                    e.preventDefault();
                    alert('Harap pilih akun tujuan untuk transfer.');
                    return;
                }
                
                if (accountId === toAccountId) {
                    e.preventDefault();
                    alert('Tidak dapat transfer ke akun yang sama.');
                    return;
                }
            }
            
            // Validate category
            if (!categorySelect.value) {
                e.preventDefault();
                alert('Harap pilih kategori.');
                return;
            }
        });
        
        // Initialize form
        const initialType = typeInput.value;
        updateFormForType(initialType);
        
        // Trigger initial budget check if editing
        @if($transaction && $transaction->type == 'expense')
            const initialCategoryId = '{{ $transaction->category_id }}';
            const initialAmount = {{ $transaction->amount }};
            const initialDate = '{{ $transaction->transaction_date->format("Y-m-d\TH:i") }}';
            
            if (initialCategoryId && initialAmount > 0) {
                checkBudget(initialCategoryId, initialAmount, initialDate);
            }
        @endif
        
        @if($transaction)
          amountHelpText(parseInt(amountInput.value))
        @endif
    });
</script>
@endpush