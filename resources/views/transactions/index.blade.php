@extends('wallet::layouts.app')

@section('title', 'Transaksi - Aplikasi Keuangan')

@push('styles')
<style>
    .transaction-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 1.2rem;
    }
    
    .transaction-income .transaction-icon {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }
    
    .transaction-expense .transaction-icon {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .transaction-transfer .transaction-icon {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    
    .transaction-amount {
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .transaction-income .transaction-amount {
        color: #198754;
    }
    
    .transaction-expense .transaction-amount {
        color: #dc3545;
    }
    
    .transaction-transfer .transaction-amount {
        color: #0d6efd;
    }
    
    .select2-container .select2-selection--single {
        height: 38px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title">Transaksi</h2>
                <p class="text-muted mb-0">Kelola semua transaksi keuangan Anda</p>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-plus-circle me-2"></i> Transaksi Baru
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('apps.transactions.create', ['type' => 'income']) }}">
                        <i class="bi bi-arrow-down-left text-success me-2"></i> Pemasukan
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('apps.transactions.create', ['type' => 'expense']) }}">
                        <i class="bi bi-arrow-up-right text-danger me-2"></i> Pengeluaran
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('apps.transactions.create', ['type' => 'transfer']) }}">
                        <i class="bi bi-arrow-left-right text-primary me-2"></i> Transfer
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('apps.transactions.index') }}" id="filterForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Jenis</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">Semua Jenis</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                        <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="account_id" class="form-label">Akun</label>
                    <select name="account_id" id="account_id" class="form-select select2">
                        <option value="">Semua Akun</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select name="category_id" id="category_id" class="form-select select2">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="payment_method" class="form-label">Metode Pembayaran</label>
                    <select name="payment_method" id="payment_method" class="form-select">
                        <option value="">Semua Metode</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Tunai</option>
                        <option value="debit_card" {{ request('payment_method') == 'debit_card' ? 'selected' : '' }}>Kartu Debit</option>
                        <option value="credit_card" {{ request('payment_method') == 'credit_card' ? 'selected' : '' }}>Kartu Kredit</option>
                        <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="e-wallet" {{ request('payment_method') == 'e-wallet' ? 'selected' : '' }}>E-Wallet</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="description" class="form-label">Keterangan / Catatan</label>
                    <input type="text" name="description" id="description" class="form-control" 
                           placeholder="Cari dalam keterangan atau catatan..."
                           value="{{ request('description') }}">
                </div>
                
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="{{ request('start_date') }}">
                </div>
                
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="{{ request('end_date') }}">
                </div>
                
                <div class="col-md-12">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-2"></i> Terapkan Filter
                        </button>
                        <a href="{{ route('wallet.transactions.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i> Reset
                        </a>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="bi bi-download me-2"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Pemasukan</h6>
                        <h4 class="text-success mb-0">
                            Rp {{ number_format($totals['income'], 0, ',', '.') }}
                        </h4>
                    </div>
                    <div class="bg-success bg-opacity-10 p-2 rounded">
                        <i class="bi bi-arrow-down-left text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Pengeluaran</h6>
                        <h4 class="text-danger mb-0">
                            Rp {{ number_format($totals['expense'], 0, ',', '.') }}
                        </h4>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-2 rounded">
                        <i class="bi bi-arrow-up-right text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Transfer</h6>
                        <h4 class="text-primary mb-0">
                            Rp {{ number_format($totals['transfer'], 0, ',', '.') }}
                        </h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-2 rounded">
                        <i class="bi bi-arrow-left-right text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transactions List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Transaksi</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">Pilih Semua</label>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item bulk-action" href="#" data-action="delete">
                            <i class="bi bi-trash text-danger me-2"></i> Hapus Terpilih
                        </a></li>
                        <li><a class="dropdown-item bulk-action" href="#" data-action="export">
                            <i class="bi bi-download me-2"></i> Export Terpilih
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        @if($transactions->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50"></th>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th>Kategori</th>
                            <th>Akun</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr class="transaction-{{ $transaction->type }}">
                                <td>
                                    <input type="checkbox" class="form-check-input transaction-check" 
                                           value="{{ $transaction->id }}">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="transaction-icon">
                                            <i class="bi bi-{{ $transaction->typeIcon }}"></i>
                                        </div>
                                        <div>
                                            {{ $transaction->transaction_date->format('d/m/Y') }}
                                            <br>
                                            <small class="text-muted">
                                                {{ $transaction->transaction_date->format('H:i') }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $transaction->description }}</strong>
                                    @if($transaction->notes)
                                        <br>
                                        <small class="text-muted">{{ Str::limit($transaction->notes, 50) }}</small>
                                    @endif
                                    @if($transaction->reference_number)
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-hash"></i> {{ $transaction->reference_number }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-{{ $transaction->category->icon }} me-1"></i>
                                        {{ $transaction->category->name }}
                                    </span>
                                </td>
                                <td>
                                    @if($transaction->type == 'transfer')
                                        <div>
                                            <small class="text-muted">Dari:</small>
                                            <br>{{ $transaction->account->name }}
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted">Ke:</small>
                                            <br>{{ $transaction->toAccount->name ?? '-' }}
                                        </div>
                                    @else
                                        {{ $transaction->account->name }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="transaction-amount">
                                        {{ $transaction->formattedAmount }}
                                    </span>
                                    <br>
                                    <small class="text-muted">{{ $transaction->typeLabel }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('wallet.transactions.edit', $transaction->uuid) }}" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                data-bs-toggle="modal" data-bs-target="#deleteModal{{ $transaction->id }}"
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#" 
                                                       onclick="duplicateTransaction('{{ $transaction->uuid }}')">
                                                        <i class="bi bi-copy me-2"></i> Duplikat
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="#" 
                                                       data-bs-toggle="modal" 
                                                       data-bs-target="#detailModal{{ $transaction->id }}">
                                                        <i class="bi bi-eye me-2"></i> Detail
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal{{ $transaction->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Apakah Anda yakin ingin menghapus transaksi ini?</p>
                                                    <div class="alert alert-warning">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        <strong>Perhatian:</strong> Saldo akun akan disesuaikan otomatis.
                                                    </div>
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <strong>{{ $transaction->description }}</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                {{ $transaction->transaction_date->format('d/m/Y H:i') }}
                                                            </small>
                                                            <div class="mt-2">
                                                                <span class="badge bg-{{ $transaction->typeColor }}">
                                                                    {{ $transaction->typeLabel }}: {{ $transaction->formattedAmount }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" 
                                                            data-bs-dismiss="modal">Batal</button>
                                                    <form action="{{ route('apps.transactions.destroy', $transaction->uuid) }}" 
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="bi bi-trash me-2"></i> Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Detail Modal -->
                                    <div class="modal fade" id="detailModal{{ $transaction->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detail Transaksi</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted">ID Transaksi</label>
                                                                <p class="mb-0">{{ $transaction->uuid }}</p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted">Tanggal Transaksi</label>
                                                                <p class="mb-0">{{ $transaction->transaction_date->format('d F Y H:i') }}</p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted">Jenis</label>
                                                                <p class="mb-0">
                                                                    <span class="badge bg-{{ $transaction->typeColor }}">
                                                                        {{ $transaction->typeLabel }}
                                                                    </span>
                                                                </p>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted">Kategori</label>
                                                                <p class="mb-0">
                                                                    <i class="bi bi-{{ $transaction->category->icon }} me-1"></i>
                                                                    {{ $transaction->category->name }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted">Akun</label>
                                                                <p class="mb-0">{{ $transaction->account->name }}</p>
                                                            </div>
                                                            @if($transaction->type == 'transfer' && $transaction->toAccount)
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted">Akun Tujuan</label>
                                                                    <p class="mb-0">{{ $transaction->toAccount->name }}</p>
                                                                </div>
                                                            @endif
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted">Jumlah</label>
                                                                <h4 class="{{ $transaction->typeColor }}">
                                                                    {{ $transaction->formattedAmount }}
                                                                </h4>
                                                            </div>
                                                            @if($transaction->payment_method)
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted">Metode Pembayaran</label>
                                                                    <p class="mb-0">{{ $transaction->payment_method }}</p>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="mb-3">
                                                                <label class="form-label text-muted">Keterangan</label>
                                                                <p class="mb-0">{{ $transaction->description }}</p>
                                                            </div>
                                                            @if($transaction->notes)
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted">Catatan</label>
                                                                    <p class="mb-0">{{ $transaction->notes }}</p>
                                                                </div>
                                                            @endif
                                                            @if($transaction->reference_number)
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted">Nomor Referensi</label>
                                                                    <p class="mb-0">{{ $transaction->reference_number }}</p>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="card bg-light">
                                                                <div class="card-body">
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-clock-history me-1"></i>
                                                                        Dibuat: {{ $transaction->created_at->format('d/m/Y H:i') }}
                                                                        @if($transaction->created_at != $transaction->updated_at)
                                                                            <br>
                                                                            <i class="bi bi-arrow-clockwise me-1"></i>
                                                                            Terakhir diubah: {{ $transaction->updated_at->format('d/m/Y H:i') }}
                                                                        @endif
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <a href="{{ route('apps.transactions.edit', $transaction->uuid) }}" 
                                                       class="btn btn-primary">
                                                        <i class="bi bi-pencil me-2"></i> Edit
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($transactions->hasPages())
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            Menampilkan {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }}
                            dari {{ $transactions->total() }} transaksi
                        </div>
                        <div>
                            {{ $transactions->links() }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-receipt display-1 text-muted"></i>
                </div>
                <h5 class="text-muted">Belum ada transaksi</h5>
                <p class="text-muted">Mulai dengan menambahkan transaksi pertama Anda</p>
                <div class="mt-4">
                    <a href="{{ route('apps.transactions.create', ['type' => 'income']) }}" class="btn btn-success me-2">
                    <a href="{{ route('apps.transactions.create', ['type' => 'income']) }}" class="btn btn-success me-2">
                        <i class="bi bi-plus-circle me-2"></i> Tambah Pemasukan
                    </a>
                    <a href="{{ route('apps.transactions.create', ['type' => 'expense']) }}" class="btn btn-danger">
                        <i class="bi bi-plus-circle me-2"></i> Tambah Pengeluaran
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Data Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('apps.transactions.export') }}" method="GET" id="exportForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="export_format" class="form-label">Format Export</label>
                        <select name="format" id="export_format" class="form-select" required>
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                            <option value="json">JSON (.json)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="export_start_date" class="form-label">Dari Tanggal</label>
                            <input type="date" name="start_date" id="export_start_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="export_end_date" class="form-label">Sampai Tanggal</label>
                            <input type="date" name="end_date" id="export_end_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-text">
                        Kosongkan tanggal untuk mengekspor semua data transaksi.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download me-2"></i> Export Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Select2
        $('.select2').select2({
            placeholder: "Pilih...",
            allowClear: true,
            width: '100%'
        });
        
        // Select All functionality
        const selectAll = document.getElementById('selectAll');
        const transactionChecks = document.querySelectorAll('.transaction-check');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                transactionChecks.forEach(check => {
                    check.checked = isChecked;
                });
            });
        }
        
        // Bulk Actions
        const bulkActions = document.querySelectorAll('.bulk-action');
        bulkActions.forEach(action => {
            action.addEventListener('click', function(e) {
                e.preventDefault();
                
                const selectedTransactions = Array.from(transactionChecks)
                    .filter(check => check.checked)
                    .map(check => check.value);
                
                if (selectedTransactions.length === 0) {
                    alert('Pilih setidaknya satu transaksi terlebih dahulu.');
                    return;
                }
                
                const actionType = this.dataset.action;
                
                switch(actionType) {
                    case 'delete':
                        if (confirm(`Apakah Anda yakin ingin menghapus ${selectedTransactions.length} transaksi terpilih?`)) {
                            bulkDelete(selectedTransactions);
                        }
                        break;
                        
                    case 'export':
                        bulkExport(selectedTransactions);
                        break;
                }
            });
        });
        
        // Bulk Delete
        function bulkDelete(ids) {
            fetch("{{ route('apps.transactions.bulk-delete') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Berhasil menghapus ${data.deleted} transaksi.`);
                    location.reload();
                } else {
                    alert('Gagal menghapus transaksi: ' + data.message);
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan: ' + error.message);
            });
        }
        
        // Bulk Export
        function bulkExport(ids) {
            const params = new URLSearchParams();
            ids.forEach(id => params.append('ids[]', id));
            
            window.location.href = "{{ route('apps.transactions.export') }}?" + params.toString();
        }
        
        // Duplicate Transaction
        window.duplicateTransaction = function(uuid) {
            if (confirm('Apakah Anda ingin menduplikasi transaksi ini?')) {
                fetch("{{ route('apps.transactions.duplicate', '') }}/" + uuid, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Transaksi berhasil diduplikasi!');
                        location.reload();
                    } else {
                        alert('Gagal menduplikasi: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan: ' + error.message);
                });
            }
        };
        
        // Auto-submit filter on date change
        const dateInputs = ['start_date', 'end_date'];
        dateInputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('change', function() {
                    if (this.value) {
                        document.getElementById('filterForm').submit();
                    }
                });
            }
        });
    });
</script>
@endpush