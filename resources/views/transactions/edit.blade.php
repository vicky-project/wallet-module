@extends('wallet::layouts.app')

@section('title', 'Edit Transaksi')

@push('styles')
<!-- Reuse the same styles from create.blade.php -->
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
    
    .type-toggle input[type="radio"] {
        display: none;
    }
    
    .type-toggle input[type="radio"]:checked + label {
        background-color: #4361ee;
        color: white;
    }
    
    .type-toggle label[for="type_income"] {
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
        background-color: rgba(67, 97, 238, 0.1);
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

@section('content')
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Edit Transaksi</h1>
        <div>
            <a href="{{ route('apps.transactions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </a>
        </div>
    </div>
    
    <!-- Transaction Form -->
    <form action="{{ route('apps.transactions.update', $transaction->id) }}" method="POST" id="transactionForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Left Column - Basic Information -->
            <div class="col-lg-8">
                <!-- Transaction Type Section -->
                <div class="form-section">
                    <h5>Tipe Transaksi</h5>
                    <div class="type-toggle mb-4">
                        <input type="radio" id="type_income" name="type" value="income" 
                               {{ old('type', $transaction->type) == 'income' ? 'checked' : '' }}>
                        <label for="type_income" class="text-income">
                            <i class="bi bi-arrow-up-circle me-2"></i>Pemasukan
                        </label>
                        
                        <input type="radio" id="type_expense" name="type" value="expense"
                               {{ old('type', $transaction->type) == 'expense' ? 'checked' : '' }}>
                        <label for="type_expense" class="text-expense">
                            <i class="bi bi-arrow-down-circle me-2"></i>Pengeluaran
                        </label>
                    </div>
                    
                    <!-- Amount Input -->
                    <div class="mb-4">
                        <label for="amount" class="form-label">Jumlah</label>
                        <div class="amount-input-group input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control amount-input" 
                                   id="amount" name="amount" 
                                   value="{{ old('amount', number_format($transaction->amount / 100, 0, ',', '.')) }}"
                                   placeholder="0" required>
                        </div>
                        @error('amount')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Title and Description -->
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="title" class="form-label">Judul Transaksi</label>
                            <input type="text" class="form-control" id="title" name="title"
                                   value="{{ old('title', $transaction->title) }}"
                                   placeholder="Contoh: Gaji Bulanan, Belanja Bulanan" required>
                            @error('title')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="transaction_date" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="transaction_date" name="transaction_date"
                                   value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required>
                            @error('transaction_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" placeholder="Tambahkan catatan untuk transaksi ini...">{{ old('description', $transaction->description) }}</textarea>
                        @error('description')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Category Selection -->
                <div class="form-section">
                    <h5>Pilih Kategori</h5>
                    
                    <!-- Income Categories -->
                    <div id="incomeCategories" class="category-options" 
                         style="{{ old('type', $transaction->type) == 'income' ? '' : 'display: none;' }}">
                        @forelse($incomeCategories as $category)
                        <label class="category-option">
                            <input type="radio" name="category_id" value="{{ $category->id }}"
                                   {{ old('category_id', $transaction->category_id) == $category->id ? 'checked' : '' }}>
                            <div class="d-flex align-items-center w-100">
                                <div class="category-icon" style="background-color: {{ $category->color }}20; color: {{ $category->color }}">
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
                            <p class="text-muted mt-2">Belum ada kategori pemasukan</p>
                        </div>
                        @endforelse
                    </div>
                    
                    <!-- Expense Categories -->
                    <div id="expenseCategories" class="category-options"
                         style="{{ old('type', $transaction->type) == 'expense' ? '' : 'display: none;' }}">
                        @forelse($expenseCategories as $category)
                        <label class="category-option">
                            <input type="radio" name="category_id" value="{{ $category->id }}"
                                   {{ old('category_id', $transaction->category_id) == $category->id ? 'checked' : '' }}>
                            <div class="d-flex align-items-center w-100">
                                <div class="category-icon" style="background-color: {{ $category->color }}20; color: {{ $category->color }}">
                                    <i class="{{ $category->icon }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-medium">{{ $category->name }}</div>
                                    @if($category->budget_limit)
                                    <small class="text-muted">Anggaran: {{ $category->formatted_budget_limit }}</small>
                                    @endif
                                </div>
                                <div class="text-end">
                                    @if($category->budget_usage_percentage > 0)
                                    <div class="progress" style="width: 80px; height: 6px;">
                                        <div class="progress-bar {{ $category->has_budget_exceeded ? 'bg-danger' : 'bg-success' }}" 
                                             style="width: {{ min($category->budget_usage_percentage, 100) }}%">
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ round($category->budget_usage_percentage, 1) }}%</small>
                                    @endif
                                </div>
                            </div>
                        </label>
                        @empty
                        <div class="text-center py-4">
                            <i class="bi bi-tag display-4 text-muted"></i>
                            <p class="text-muted mt-2">Belum ada kategori pengeluaran</p>
                        </div>
                        @endforelse
                    </div>
                    
                    @error('category_id')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <!-- Right Column - Additional Information -->
            <div class="col-lg-4">
                <!-- Account Selection -->
                <div class="form-section">
                    <h5>Akun</h5>
                    <div class="mb-3">
                        <label for="account_id" class="form-label">Pilih Akun</label>
                        <select class="form-select" id="account_id" name="account_id" required>
                            <option value="">Pilih Akun...</option>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}"
                                    {{ old('account_id', $transaction->account_id) == $account->id ? 'selected' : '' }}
                                    data-balance="{{ $account->formatted_current_balance }}">
                                <div class="d-flex align-items-center">
                                    <i class="{{ $account->icon }} me-2" style="color: {{ $account->color }}"></i>
                                    {{ $account->name }} ({{ $account->formatted_current_balance }})
                                </div>
                            </option>
                            @endforeach
                        </select>
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
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Pilih Metode...</option>
                            <option value="cash" {{ old('payment_method', $transaction->payment_method) == 'cash' ? 'selected' : '' }}>Tunai</option>
                            <option value="bank_transfer" {{ old('payment_method', $transaction->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Transfer Bank</option>
                            <option value="credit_card" {{ old('payment_method', $transaction->payment_method) == 'credit_card' ? 'selected' : '' }}>Kartu Kredit</option>
                            <option value="e_wallet" {{ old('payment_method', $transaction->payment_method) == 'e_wallet' ? 'selected' : '' }}>E-Wallet</option>
                            <option value="other" {{ old('payment_method', $transaction->payment_method) == 'other' ? 'selected' : '' }}>Lainnya</option>
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
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   id="is_recurring" name="is_recurring" value="1"
                                   {{ old('is_recurring', $transaction->is_recurring) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_recurring">
                                Jadikan transaksi rutin
                            </label>
                        </div>
                        
                        <div id="recurringOptions" style="{{ old('is_recurring', $transaction->is_recurring) ? '' : 'display: none;' }} margin-top: 1rem;">
                            <div class="mb-3">
                                <label for="recurring_period" class="form-label">Periode</label>
                                <select class="form-select" id="recurring_period" name="recurring_period">
                                    <option value="">Pilih Periode...</option>
                                    <option value="daily" {{ old('recurring_period', $transaction->recurring_period) == 'daily' ? 'selected' : '' }}>Harian</option>
                                    <option value="weekly" {{ old('recurring_period', $transaction->recurring_period) == 'weekly' ? 'selected' : '' }}>Mingguan</option>
                                    <option value="monthly" {{ old('recurring_period', $transaction->recurring_period) == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                    <option value="yearly" {{ old('recurring_period', $transaction->recurring_period) == 'yearly' ? 'selected' : '' }}>Tahunan</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="recurring_end_date" class="form-label">Tanggal Berakhir (Opsional)</label>
                                <input type="date" class="form-control" id="recurring_end_date" name="recurring_end_date"
                                       value="{{ old('recurring_end_date', optional($transaction->recurring_end_date)->format('Y-m-d')) }}">
                                <small class="text-muted">Biarkan kosong jika tidak ada tanggal berakhir</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="form-section">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-2"></i>Perbarui Transaksi
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="previewTransaction()">
                            <i class="bi bi-eye me-2"></i>Pratinjau
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pratinjau Perubahan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <!-- Preview content will be inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Reuse JavaScript functions from create.blade.php
    // with slight modifications for edit functionality
    
    document.addEventListener('DOMContentLoaded', function() {
        const amountInput = document.getElementById('amount');
        
        amountInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
            }
            e.target.value = value;
        });
        
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            const amountField = document.getElementById('amount');
            if (amountField.value) {
                const cleanValue = amountField.value.replace(/[^0-9]/g, '');
                amountField.value = cleanValue;
            }
        });
        
        const typeIncome = document.getElementById('type_income');
        const typeExpense = document.getElementById('type_expense');
        const incomeCategories = document.getElementById('incomeCategories');
        const expenseCategories = document.getElementById('expenseCategories');
        
        function toggleCategories() {
            if (typeIncome.checked) {
                incomeCategories.style.display = 'block';
                expenseCategories.style.display = 'none';
            } else {
                incomeCategories.style.display = 'none';
                expenseCategories.style.display = 'block';
            }
        }
        
        typeIncome.addEventListener('change', toggleCategories);
        typeExpense.addEventListener('change', toggleCategories);
        toggleCategories();
        
        const isRecurring = document.getElementById('is_recurring');
        const recurringOptions = document.getElementById('recurringOptions');
        
        isRecurring.addEventListener('change', function() {
            if (this.checked) {
                recurringOptions.style.display = 'block';
            } else {
                recurringOptions.style.display = 'none';
            }
        });
        
        const accountSelect = document.getElementById('account_id');
        const accountBalance = document.getElementById('accountBalance');
        
        accountSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const balance = selectedOption.getAttribute('data-balance');
            
            if (balance) {
                accountBalance.innerHTML = `
                    <div class="alert alert-info p-2">
                        <small>
                            <i class="bi bi-wallet me-1"></i>
                            Saldo saat ini: <strong>${balance}</strong>
                        </small>
                    </div>
                `;
            } else {
                accountBalance.innerHTML = '';
            }
        });
        
        if (accountSelect.value) {
            accountSelect.dispatchEvent(new Event('change'));
        }
    });
    
    function previewTransaction() {
        // Similar to create version but for edit
        const type = document.querySelector('input[name="type"]:checked')?.value || '';
        const amount = document.getElementById('amount').value;
        const title = document.getElementById('title').value;
        const date = document.getElementById('transaction_date').value;
        const categoryId = document.querySelector('input[name="category_id"]:checked')?.value;
        const accountId = document.getElementById('account_id').value;
        const paymentMethod = document.getElementById('payment_method').value;
        const description = document.getElementById('description').value;
        
        let categoryName = '';
        if (categoryId) {
            const categoryOption = document.querySelector(`input[name="category_id"][value="${categoryId}"]`);
            if (categoryOption) {
                const categoryDiv = categoryOption.nextElementSibling;
                categoryName = categoryDiv.querySelector('.fw-medium')?.textContent || '';
            }
        }
        
        let accountName = '';
        if (accountId) {
            const accountOption = document.getElementById('account_id').options[accountId];
            accountName = accountOption ? accountOption.textContent.split('(')[0].trim() : '';
        }
        
        const dateObj = new Date(date);
        const formattedDate = dateObj.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        const previewHTML = `
            <div class="text-center mb-4">
                <div class="transaction-icon ${type === 'income' ? 'bg-income' : 'bg-expense'}" 
                     style="width: 60px; height: 60px; margin: 0 auto 1rem;">
                    <i class="bi ${type === 'income' ? 'bi-arrow-up-circle text-income' : 'bi-arrow-down-circle text-expense'}" 
                       style="font-size: 1.5rem;"></i>
                </div>
                <h4 class="${type === 'income' ? 'text-income' : 'text-expense'}">
                    ${type === 'income' ? 'Pemasukan' : 'Pengeluaran'}
                </h4>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <small class="text-muted d-block">Jumlah</small>
                    <h5 class="fw-bold ${type === 'income' ? 'text-income' : 'text-expense'}">
                        ${type === 'income' ? '+' : '-'}Rp ${amount || '0'}
                    </h5>
                </div>
                <div class="col-6 text-end">
                    <small class="text-muted d-block">Tanggal</small>
                    <div class="fw-medium">${formattedDate}</div>
                </div>
            </div>
            
            <hr>
            
            <div class="mb-3">
                <small class="text-muted d-block">Judul</small>
                <div class="fw-medium">${title || '-'}</div>
            </div>
            
            ${description ? `
            <div class="mb-3">
                <small class="text-muted d-block">Deskripsi</small>
                <div>${description}</div>
            </div>
            ` : ''}
            
            <div class="row">
                <div class="col-6">
                    <small class="text-muted d-block">Kategori</small>
                    <div class="fw-medium">${categoryName || '-'}</div>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Akun</small>
                    <div class="fw-medium">${accountName || '-'}</div>
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
    
    function getPaymentMethodLabel(method) {
        const labels = {
            'cash': 'Tunai',
            'bank_transfer': 'Transfer Bank',
            'credit_card': 'Kartu Kredit',
            'e_wallet': 'E-Wallet',
            'other': 'Lainnya'
        };
        return labels[method] || method;
    }
    
    function submitForm() {
        document.getElementById('transactionForm').submit();
    }
</script>
@endpush