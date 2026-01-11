@extends('wallet::layouts.app')

@section('title', 'Detail Budget - ' . $budget->name ?: $budget->category->name)

@use('Modules\Wallet\Helpers\Helper')

@section('content')
@include('wallet::partials.fab')
<!-- Budget Header -->
<div class="budget-header">
    <div class="row align-items-center">
        <div class="col-md-8 position-relative z-1">
            <div class="d-flex align-items-center mb-3">
                <div class="me-3">
                    <i class="bi bi-cash-coin display-4 opacity-75"></i>
                </div>
                <div>
                    <h1 class="h2 mb-1">{{ $budget->name ?: $budget->category->name }}</h1>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="period-badge me-2 mb-1">
                            <i class="bi bi-{{ $budget->category->icon }} me-1"></i>{{ $budget->category->name }}
                        </span>
                        <span class="period-badge me-2 mb-1">
                            <i class="bi bi-calendar me-1"></i>{{ ucfirst($budget->period_type->value) }}
                        </span>
                        <span class="period-badge me-2 mb-1">
                            <i class="bi bi-clock me-1"></i>{{ $budget->days_left }} hari lagi
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="mt-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-white-75">Progress Penggunaan</span>
                    <span class="text-white fw-semibold">{{ number_format($budget->usage_percentage, 1) }}%</span>
                </div>
                <div class="progress progress-budget-lg mb-2">
                    <div class="progress-bar 
                        @if($budget->is_over_budget) bg-danger
                        @elseif($budget->usage_percentage >= 80) bg-warning
                        @else bg-success @endif" 
                        style="width: {{ min($budget->usage_percentage, 100) }}%">
                    </div>
                </div>
                <div class="d-flex justify-content-between text-white-75">
                    <small>{{ Helper::formatMoney($budget->spent->getAmount()->toInt()) }} terpakai</small>
                    <small>{{ Helper::formatMoney($budget->amount->getAmount()->toInt()) }} total budget</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 text-center text-md-end position-relative z-1">
            <div class="mb-3">
                <div class="display-5 fw-bold text-white">{{ Helper::formatMoney($budget->remaining) }}</div>
                <div class="text-white-75">SISA BUDGET</div>
            </div>
            <div class="d-flex justify-content-center justify-content-md-end">
                <div class="text-start">
                    <div class="text-white-75 small">Rata-rata per hari</div>
                    <div class="text-white fw-semibold">{{ Helper::formatMoney($budget->daily_budget) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="{{ route('apps.budgets.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
            <div class="btn-group">
                <a href="{{ route('apps.budgets.edit', $budget) }}" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <button type="button" class="btn btn-info dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('apps.transactions.create', ['budget_id' => $budget->id]) }}">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('apps.budgets.index', $budget) }}" 
                           onclick="return confirm('Buat budget untuk periode berikutnya?')">
                            <i class="bi bi-calendar-plus me-2"></i>Buat Periode Berikutnya
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#duplicateModal">
                            <i class="bi bi-files me-2"></i>Duplikat Budget
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('apps.budgets.toggle-status', $budget) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-toggle-{{ $budget->is_active ? 'off' : 'on' }} me-2"></i>
                                {{ $budget->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('apps.budgets.destroy', $budget) }}" 
                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus budget ini?')" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-trash me-2"></i>Hapus
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-value">{{ format_currency($budget->amount) }}</div>
            <div class="stat-label">TOTAL BUDGET</div>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="bi bi-calendar-range me-1"></i>
                    {{ $budget->start_date->format('d M') }} - {{ $budget->end_date->format('d M Y') }}
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-value {{ $budget->is_over_budget ? 'text-danger' : 'text-success' }}">
                {{ format_currency($budget->spent) }}
            </div>
            <div class="stat-label">TOTAL TERPAKAI</div>
            <div class="mt-3">
                <div class="d-flex align-items-center">
                    <span class="usage-indicator 
                        @if($budget->is_over_budget) usage-high
                        @elseif($budget->usage_percentage >= 80) usage-medium
                        @else usage-low @endif">
                    </span>
                    <small class="text-muted">{{ number_format($budget->usage_percentage, 1) }}% dari budget</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-value text-info">{{ format_currency($budget->remaining) }}</div>
            <div class="stat-label">SISA BUDGET</div>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="bi bi-calendar-day me-1"></i>
                    {{ $budget->days_left }} hari tersisa
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-value text-warning">{{ format_currency($budget->daily_budget) }}</div>
            <div class="stat-label">RATA-RATA HARIAN</div>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="bi bi-graph-up me-1"></i>
                    Rekomendasi harian
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Details & Accounts -->
    <div class="col-lg-4">
        <!-- Details Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>Detail Budget
                </h5>
                <span class="badge bg-{{ $budget->is_active ? 'success' : 'secondary' }}">
                    {{ $budget->is_active ? 'AKTIF' : 'NONAKTIF' }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th width="120">Kategori</th>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="account-icon-small me-2" 
                                     style="background-color: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary);">
                                    <i class="bi bi-{{ $budget->category->icon }}"></i>
                                </div>
                                {{ $budget->category->name }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Nama Budget</th>
                        <td>{{ $budget->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Tipe Periode</th>
                        <td>
                            <span class="badge bg-primary">{{ ucfirst($budget->period_type) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th>Periode</th>
                        <td>
                            {{ $budget->period_label }}
                            <br>
                            <small class="text-muted">
                                {{ $budget->start_date->format('d M Y') }} - {{ $budget->end_date->format('d M Y') }}
                            </small>
                        </td>
                    </tr>
                    <tr>
                        <th>Status Rollover</th>
                        <td>
                            @if($budget->rollover_unused)
                                <span class="badge bg-info">AKTIF</span>
                                @if($budget->rollover_limit)
                                    <small class="text-muted d-block">
                                        Limit: {{ format_currency($budget->rollover_limit) }}
                                    </small>
                                @endif
                            @else
                                <span class="badge bg-secondary">NONAKTIF</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Dibuat</th>
                        <td>{{ $budget->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Diupdate</th>
                        <td>{{ $budget->updated_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Accounts Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-wallet me-2"></i>Akun Terkait
                </h5>
            </div>
            <div class="card-body">
                @if($budget->accounts->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($budget->accounts as $account)
                            <div class="list-group-item px-0">
                                <div class="d-flex align-items-center">
                                    <div class="account-icon-small me-3" 
                                         style="background-color: {{ $account->color }}20; color: {{ $account->color }}">
                                        <i class="bi bi-{{ $account->icon }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $account->name }}</div>
                                        <div class="text-muted small">
                                            Saldo: {{ format_currency($account->balance->getMinorAmount()->toInt()) }}
                                        </div>
                                    </div>
                                    <span class="badge bg-light text-dark">
                                        {{ $account->type->label() }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-wallet display-6 opacity-50"></i>
                        <p class="mt-2 mb-0">Semua Akun</p>
                        <small>Budget memantau semua akun</small>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-lightning me-2"></i>Aksi Cepat
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('wallet.transactions.create', ['budget_id' => $budget->id]) }}" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi
                    </a>
                    
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#duplicateModal">
                        <i class="bi bi-files me-2"></i>Duplikat Budget
                    </button>
                    
                    <a href="{{ route('apps.budgets.next-period', $budget) }}" 
                       class="btn btn-outline-info" 
                       onclick="return confirm('Buat budget untuk periode berikutnya?')">
                        <i class="bi bi-calendar-plus me-2"></i>Buat Periode Berikutnya
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Transactions & Statistics -->
    <div class="col-lg-8">
        <!-- Recent Transactions -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-receipt me-2"></i>Transaksi Terbaru
                    <span class="badge bg-secondary ms-2">{{ $transactions->total() }}</span>
                </h5>
                <a href="{{ route('apps.transactions.index', ['budget_id' => $budget->id]) }}" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                @if($transactions->count() > 0)
                    <div class="transaction-timeline">
                        @foreach($transactions as $transaction)
                            <div class="transaction-item {{ $transaction->type === 'income' ? 'income' : 'expense' }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold">{{ $transaction->description ?: 'Tanpa deskripsi' }}</div>
                                        <div class="text-muted small">
                                            <i class="bi bi-calendar me-1"></i>
                                            {{ $transaction->transaction_date->format('d M Y') }}
                                            •
                                            <i class="bi bi-wallet me-1"></i>
                                            {{ $transaction->account->name }}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold {{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->type === 'income' ? '+' : '-' }}{{ format_currency($transaction->amount) }}
                                        </div>
                                        <div class="text-muted small">
                                            via {{ $transaction->payment_method }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Pagination -->
                    @if($transactions->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-receipt display-1 text-muted"></i>
                        </div>
                        <h5 class="mb-3">Belum ada transaksi</h5>
                        <p class="text-muted mb-4">Mulai dengan menambahkan transaksi untuk budget ini.</p>
                        <a href="{{ route('apps.transactions.create', ['budget_id' => $budget->id]) }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi Pertama
                        </a>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Statistics Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>Statistik Budget
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        <div class="display-6 fw-bold text-primary">{{ $stats['total_transactions'] }}</div>
                        <div class="text-muted">Total Transaksi</div>
                        <small class="text-muted">{{ $stats['transactions_today'] }} hari ini</small>
                    </div>
                    
                    <div class="col-md-4 text-center mb-4">
                        <div class="display-6 fw-bold text-success">{{ format_currency($stats['average_transaction']) }}</div>
                        <div class="text-muted">Rata-rata Transaksi</div>
                        <small class="text-muted">per transaksi</small>
                    </div>
                    
                    <div class="col-md-4 text-center mb-4">
                        <div class="display-6 fw-bold text-danger">{{ format_currency($stats['largest_transaction']) }}</div>
                        <div class="text-muted">Transaksi Terbesar</div>
                        <small class="text-muted">single transaction</small>
                    </div>
                </div>
                
                <!-- Daily Usage Chart -->
                <div class="mt-4">
                    <h6 class="fw-semibold mb-3">Penggunaan Harian</h6>
                    <div class="chart-container">
                        <canvas id="dailyUsageChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Modal -->
<div class="modal fade" id="duplicateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('apps.budgets.duplicate', $budget) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Duplikat Budget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="duplicate_name" class="form-label">Nama Budget Baru</label>
                        <input type="text" class="form-control" id="duplicate_name" name="name" 
                               value="{{ $budget->name ? $budget->name . ' (Salinan)' : $budget->category->name . ' (Salinan)' }}" required>
                        <div class="form-text">
                            Berikan nama untuk budget duplikat.
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="duplicate_settings" name="duplicate_settings" checked>
                            <label class="form-check-label" for="duplicate_settings">
                                Salin semua pengaturan
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="duplicate_next_period" name="duplicate_next_period">
                            <label class="form-check-label" for="duplicate_next_period">
                                Buat untuk periode berikutnya
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-files me-1"></i>Duplikat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize daily usage chart
    const chartCtx = document.getElementById('dailyUsageChart');
    if (chartCtx) {
        // Sample data - in production, fetch this from API
        const dailyData = {
            labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            datasets: [{
                label: 'Pengeluaran Harian',
                data: [150000, 230000, 180000, 320000, 280000, 450000, 120000],
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        };
        
        new Chart(chartCtx, {
            type: 'line',
            data: dailyData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Update spent amount button
    const updateSpentBtn = document.getElementById('updateSpentBtn');
    if (updateSpentBtn) {
        updateSpentBtn.addEventListener('click', function() {
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memperbarui...';
            this.disabled = true;
            
            fetch('{{ route("apps.budgets.update-spent") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Berhasil', data.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast('danger', 'Gagal', data.message);
                    this.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Perbarui Penggunaan';
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', 'Error', 'Terjadi kesalahan');
                this.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Perbarui Penggunaan';
                this.disabled = false;
            });
        });
    }
    
    // Toast notification function
    function showToast(type, title, message) {
        const toastContainer = document.createElement('div');
        toastContainer.innerHTML = `
            <div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}:</strong> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        document.body.appendChild(toastContainer);
        const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
        toast.show();
        
        toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', function() {
            toastContainer.remove();
        });
    }
});
</script>
@endpush

@push('styles')
<style>
    .budget-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .budget-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }
    
    .budget-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(-30%, 30%);
    }
    
    .progress-budget-lg {
        height: 15px;
        border-radius: 10px;
        background-color: rgba(255, 255, 255, 0.2);
        overflow: hidden;
    }
    
    .progress-budget-lg .progress-bar {
        border-radius: 10px;
    }
    
    .stat-card {
        background-color: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e9ecef;
        transition: transform 0.2s;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: var(--bs-primary);
    }
    
    .stat-card .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .transaction-timeline {
        position: relative;
        padding-left: 2rem;
    }
    
    .transaction-timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .transaction-item {
        position: relative;
        padding: 0.75rem;
        margin-bottom: 1rem;
        border-radius: 8px;
        background-color: white;
        border: 1px solid #e9ecef;
    }
    
    .transaction-item::before {
        content: '';
        position: absolute;
        left: -2.25rem;
        top: 50%;
        transform: translateY(-50%);
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #0d6efd;
        border: 3px solid white;
        box-shadow: 0 0 0 2px #0d6efd;
    }
    
    .transaction-item.expense::before {
        background-color: #dc3545;
        box-shadow: 0 0 0 2px #dc3545;
    }
    
    .transaction-item.income::before {
        background-color: #198754;
        box-shadow: 0 0 0 2px #198754;
    }
    
    .period-badge {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .account-badge {
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 15px;
        background-color: #e9ecef;
        color: #495057;
    }
    
    .usage-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    
    .usage-low { background-color: #28a745; }
    .usage-medium { background-color: #ffc107; }
    .usage-high { background-color: #dc3545; }
    
    .chart-container {
        position: relative;
        height: 300px;
        margin: 20px 0;
    }
    
    /* Dark mode adjustments */
    body[data-bs-theme="dark"] .stat-card {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    body[data-bs-theme="dark"] .transaction-item {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    body[data-bs-theme="dark"] .account-badge {
        background-color: #4a5568;
        color: #e2e8f0;
    }
</style>
@endpush