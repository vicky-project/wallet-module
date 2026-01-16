@extends('wallet::layouts.app')

@section('title', 'Dashboard - Aplikasi Keuangan Digital')

@push('styles')
<style>
    .stat-card {
        border-left: 4px solid;
    }
    
    .stat-card-income {
        border-left-color: #10b981;
    }
    
    .stat-card-expense {
        border-left-color: #ef4444;
    }
    
    .stat-card-balance {
        border-left-color: #4361ee;
    }
    
    .stat-card-accounts {
        border-left-color: #8b5cf6;
    }
    
    .stat-card-budget {
        border-left-color: #f59e0b;
    }
    
    .stat-card-savings {
        border-left-color: #3b82f6;
    }
    
    .stat-card-recurring {
        border-left-color: #8b5cf6;
    }
    
    .progress-budget {
        height: 8px;
        border-radius: 4px;
    }
    
    .account-card {
        transition: all 0.3s;
    }
    
    .account-card:hover {
        transform: translateY(-3px);
    }
    
    .quick-actions .btn {
        border-radius: 10px;
        padding: 10px 15px;
    }
    
    .transaction-tag {
        font-size: 0.75rem;
        padding: 2px 8px;
    }
    
    .alert-budget {
        border-left: 4px solid;
    }
    
    .alert-budget.warning {
        border-left-color: #f59e0b;
        background-color: rgba(245, 158, 11, 0.1);
    }
    
    .alert-budget.danger {
        border-left-color: #ef4444;
        background-color: rgba(239, 68, 68, 0.1);
    }
    
    .mini-chart {
        height: 40px;
        width: 100%;
    }
    
    .trend-up {
        color: #10b981;
    }
    
    .trend-down {
        color: #ef4444;
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<!-- Statistik Utama -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-balance">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 text-muted">Total Saldo</h6>
                        <h3 class="card-title mb-0 currency">{{ number_format($dashboardData['total_balance'] ?? 0) }}</h3>
                        <p class="text-muted small mb-0">Semua akun aktif</p>
                    </div>
                    <div class="card-icon bg-primary">
                        <i class="bi bi-wallet text-white"></i>
                    </div>
                </div>
                <div class="mt-3">
                    @if(($dashboardData['balance_trend'] ?? 0) > 0)
                    <span class="badge bg-success">
                        <i class="bi bi-arrow-up"></i> 
                        {{ number_format($dashboardData['balance_trend'] ?? 0, 1) }}%
                    </span>
                    @elseif(($dashboardData['balance_trend'] ?? 0) < 0)
                    <span class="badge bg-danger">
                        <i class="bi bi-arrow-down"></i> 
                        {{ number_format(abs($dashboardData['balance_trend'] ?? 0), 1) }}%
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-income">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 text-muted">Pemasukan Bulan Ini</h6>
                        <h3 class="card-title mb-0 currency">{{ number_format($dashboardData['monthly_income'] ?? 0) }}</h3>
                        <p class="text-muted small mb-0">{{ date('F Y') }}</p>
                    </div>
                    <div class="card-icon bg-success">
                        <i class="bi bi-arrow-down-left text-white"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-info">
                        <i class="bi bi-cash-stack"></i> 
                        {{ $dashboardData['income_count'] ?? 0 }} transaksi
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-expense">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 text-muted">Pengeluaran Bulan Ini</h6>
                        <h3 class="card-title mb-0 currency">{{ number_format($dashboardData['monthly_expense'] ?? 0) }}</h3>
                        <p class="text-muted small mb-0">{{ date('F Y') }}</p>
                    </div>
                    <div class="card-icon bg-danger">
                        <i class="bi bi-arrow-up-right text-white"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-warning">
                        <i class="bi bi-receipt"></i> 
                        {{ $dashboardData['expense_count'] ?? 0 }} transaksi
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card stat-card-budget">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 text-muted">Penggunaan Budget</h6>
                        <h3 class="card-title mb-0">{{ $dashboardData['budget_usage_percentage'] ?? 0 }}%</h3>
                        <p class="text-muted small mb-0">Rata-rata kategori</p>
                    </div>
                    <div class="card-icon" style="background-color: #f59e0b;">
                        <i class="bi bi-pie-chart text-white"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress progress-budget">
                        <div class="progress-bar {{ ($dashboardData['budget_usage_percentage'] ?? 0) > 90 ? 'bg-danger' : (($dashboardData['budget_usage_percentage'] ?? 0) > 70 ? 'bg-warning' : 'bg-success') }}" 
                             role="progressbar" 
                             style="width: {{ min(100, $dashboardData['budget_usage_percentage'] ?? 0) }}%"
                             aria-valuenow="{{ $dashboardData['budget_usage_percentage'] ?? 0 }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ringkasan Keuangan -->
<div class="row mb-4">
    <!-- Grafik Transaksi -->
    <div class="col-xl-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Grafik Transaksi (6 Bulan Terakhir)</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary active" data-period="month">Bulanan</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-period="year">Tahunan</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="transactionChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Ringkasan Budget & Peringatan -->
    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Ringkasan Budget</h5>
                <span class="badge bg-primary">{{ $dashboardData['budget_stats']['total_budgets'] ?? 0 }} Budget</span>
            </div>
            <div class="card-body">
                @if(isset($dashboardData['budget_stats']) && $dashboardData['budget_stats']['total'] > 0)
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Total Budget</small>
                            <strong class="currency">{{ $dashboardData['budget_stats']['total_amount'] ?? 0 }}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Terkeluarkan</small>
                            <strong class="currency">{{ $dashboardData['budget_stats']['total_spent'] ?? 0 }}</strong>
                        </div>
                    </div>
                    
                    @foreach($dashboardData['budget_summary'] ?? [] as $budget)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">{{ $budget['category_name'] ?? 'Kategori' }}</span>
                            <span class="small">
                                <span class="currency">{{ $budget['spent'] ?? 0 }}</span> / 
                                <span class="currency">{{ $budget['amount'] ?? 0 }}</span>
                            </span>
                        </div>
                        <div class="progress progress-budget">
                            @php
                                $percentage = isset($budget['amount']) && $budget['amount'] > 0 ? 
                                    min(100, ($budget['spent'] / $budget['amount']) * 100) : 0;
                                $bgClass = $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="progress-bar {{ $bgClass }}" 
                                 role="progressbar" 
                                 style="width: {{ $percentage }}%"
                                 aria-valuenow="{{ $percentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="small text-muted">{{ number_format($percentage, 1) }}%</span>
                            <span class="small text-muted currency">{{ ($budget['amount'] ?? 0) - ($budget['spent'] ?? 0) }}</span>
                        </div>
                    </div>
                    @endforeach
                    
                    <!-- Peringatan Budget -->
                    @if(isset($dashboardData['budget_warnings']) && count($dashboardData['budget_warnings']) > 0)
                    <div class="mt-4">
                        <h6 class="mb-3"><i class="bi bi-exclamation-triangle text-warning"></i> Peringatan Budget</h6>
                        @foreach($dashboardData['budget_warnings'] as $warning)
                        <div class="alert alert-budget {{ $warning['percentage'] > 90 ? 'danger' : 'warning' }} mb-2 p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="fw-bold">{{ $warning['category_name'] }}</small>
                                <small class="fw-bold">{{ number_format($warning['percentage'], 1) }}%</small>
                            </div>
                            <small class="d-block">{{ $warning['message'] }}</small>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-piggy-bank display-4 text-muted"></i>
                        <p class="text-muted mt-3">Belum ada budget yang diatur</p>
                        <a href="{{ route('apps.budgets.create') }}" class="btn btn-sm btn-outline-primary">Buat Budget</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Akun dan Analisis -->
<div class="row mb-4">
    <!-- Daftar Akun -->
    <div class="col-lg-5 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Akun Saya</h5>
                <div>
                    <span class="badge bg-info me-2">{{ $dashboardData['account_stats']['active'] ?? 0 }} Aktif</span>
                    <a href="{{ route('apps.accounts.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Tambah
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @if(isset($dashboardData['accounts']) && count($dashboardData['accounts']) > 0)
                        @foreach($dashboardData['accounts'] as $account)
                        <div class="list-group-item p-3 account-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="transaction-icon" style="background-color: {{ $account['color'] ?? '#3490dc' }};">
                                        <i class="bi {{ $account['icon'] ?? 'bi-wallet' }} text-white"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">{{ $account['name'] }}</h6>
                                        <span class="currency fw-bold">{{ $account['balance'] ?? 0 }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <small class="text-muted">
                                            {{ ucfirst($account['type']->name ?? 'cash') }}
                                            @if($account['is_default'] ?? false)
                                                <span class="badge bg-info ms-2">Utama</span>
                                            @endif
                                        </small>
                                        <small class="text-muted">
                                            @if(($account['net_flow']->getAmount()->toInt() ?? 0) > 0)
                                                <span class="trend-up">
                                                    <i class="bi bi-arrow-up"></i> 
                                                    <span class="currency">{{ $account['net_flow'] }}</span>
                                                </span>
                                            @elseif(($account['net_flow']->getAmount()->toInt() ?? 0) < 0)
                                                <span class="trend-down">
                                                    <i class="bi bi-arrow-down"></i> 
                                                    <span class="currency">{{ abs($account['net_flow']) }}</span>
                                                </span>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-wallet display-4 text-muted"></i>
                            <p class="text-muted mt-3">Belum ada akun</p>
                        </div>
                    @endif
                </div>
                @if(isset($dashboardData['account_stats']) && $dashboardData['account_stats']['total'] > 0)
                <div class="card-footer bg-transparent">
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted d-block">Total</small>
                            <strong>{{ $dashboardData['account_stats']['total'] ?? 0 }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Aktif</small>
                            <strong class="text-success">{{ $dashboardData['account_stats']['active'] ?? 0 }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Saldo</small>
                            <strong class="currency">{{ $dashboardData['account_stats']['total_balance'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Analisis Kategori -->
    <div class="col-lg-7 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Analisis Kategori</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-secondary active" data-type="expense">Pengeluaran</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-type="income">Pemasukan</button>
                </div>
            </div>
            <div class="card-body">
                @if(isset($dashboardData['category_analysis']) && count($dashboardData['category_analysis']) > 0)
                    <canvas id="categoryChart" height="200"></canvas>
                    <div class="row mt-3">
                        @foreach($dashboardData['category_analysis'] as $index => $category)
                            @if($index < 6)
                            <div class="col-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge me-2" style="background-color: {{ $category['color'] ?? '#3490dc' }};">&nbsp;</span>
                                    <small class="text-truncate">{{ $category['name'] }}</small>
                                    <small class="ms-auto fw-bold currency">{{ $category['amount'] }}</small>
                                </div>
                                <div class="progress progress-budget mt-1">
                                    <div class="progress-bar" 
                                         style="width: {{ $category['percentage'] ?? 0 }}%; background-color: {{ $category['color'] ?? '#3490dc' }};">
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-tags display-4 text-muted"></i>
                        <p class="text-muted mt-3">Belum ada data kategori</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Transaksi dan Aktivitas -->
<div class="row">
    <!-- Transaksi Terbaru -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Transaksi Terbaru</h5>
                <div>
                    <span class="badge bg-secondary me-2">{{ $dashboardData['transaction_stats']['total_this_month'] ?? 0 }} Bulan Ini</span>
                    <a href="{{ route('apps.transactions.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Transaksi Baru
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if(isset($dashboardData['recent_transactions']) && count($dashboardData['recent_transactions']) > 0)
                    @foreach($dashboardData['recent_transactions'] as $transaction)
                    <div class="transaction-item">
                        <div class="transaction-icon {{ $transaction['type'] == 'income' ? 'bg-income' : 'bg-expense' }}">
                            <i class="bi {{ $transaction['icon'] ?? 'bi-cash' }} {{ $transaction['type'] == 'income' ? 'text-income' : 'text-expense' }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">{{ $transaction['description'] }}</h6>
                                    <div class="d-flex align-items-center mt-1">
                                        <small class="text-muted me-2">{{ $transaction['category_name'] ?? 'Tidak Berkategori' }}</small>
                                        @if($transaction['is_recurring'] ?? false)
                                        <span class="badge bg-info transaction-tag">
                                            <i class="bi bi-arrow-repeat"></i> Rutin
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="currency fw-bold {{ $transaction['type'] == 'income' ? 'text-income' : 'text-expense' }}">
                                        {{ $transaction['type'] == 'income' ? '+' : '-' }}{{ $transaction['amount'] }}
                                    </span>
                                    <div class="small text-muted mt-1">
                                        {{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-receipt display-4 text-muted"></i>
                        <p class="text-muted mt-3">Belum ada transaksi</p>
                    </div>
                @endif
            </div>
            <div class="card-footer bg-transparent">
                <div class="row text-center">
                    <div class="col-4">
                        <small class="text-muted d-block">Hari Ini</small>
                        <strong class="currency">{{ $dashboardData['transaction_stats']['today'] ?? 0 }}</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">7 Hari</small>
                        <strong class="currency">{{ $dashboardData['transaction_stats']['last_7_days'] ?? 0 }}</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">30 Hari</small>
                        <strong class="currency">{{ $dashboardData['transaction_stats']['last_30_days'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aktivitas dan Notifikasi -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Aktivitas & Peringatan</h5>
            </div>
            <div class="card-body p-0">
                <!-- Transaksi Rutin Mendatang -->
                @if(isset($dashboardData['upcoming_recurring']) && count($dashboardData['upcoming_recurring']) > 0)
                <div class="p-3 border-bottom">
                    <h6 class="mb-3"><i class="bi bi-arrow-repeat text-primary"></i> Transaksi Rutin Mendatang</h6>
                    @foreach($dashboardData['upcoming_recurring'] as $recurring)
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-shrink-0">
                            <i class="bi {{ $recurring['type'] == 'income' ? 'bi-arrow-down-left text-success' : 'bi-arrow-up-right text-danger' }}"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <small class="d-block">{{ $recurring['description'] }}</small>
                            <small class="text-muted">
                                <span class="currency">{{ $recurring['amount'] }}</span> • 
                                {{ \Carbon\Carbon::parse($recurring['next_date'])->format('d M') }}
                            </small>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                
                <!-- Peringatan Saldo -->
                @if(isset($dashboardData['account_alerts']) && count($dashboardData['account_alerts']) > 0)
                <div class="p-3 border-bottom">
                    <h6 class="mb-3"><i class="bi bi-exclamation-triangle text-warning"></i> Peringatan Akun</h6>
                    @foreach($dashboardData['account_alerts'] as $alert)
                    <div class="alert alert-warning alert-sm mb-2 p-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-wallet me-2"></i>
                            <small>{{ $alert['message'] }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                
                <!-- Aktivitas Terakhir -->
                <div class="p-3">
                    <h6 class="mb-3"><i class="bi bi-clock-history text-muted"></i> Aktivitas Terakhir</h6>
                    @if(isset($dashboardData['recent_activity']) && count($dashboardData['recent_activity']) > 0)
                        @foreach($dashboardData['recent_activity'] as $activity)
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-shrink-0">
                                <i class="bi {{ $activity['icon'] ?? 'bi-check-circle' }} text-muted"></i>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <small class="d-block">{{ $activity['description'] }}</small>
                                <small class="text-muted">{{ $activity['time_ago'] }}</small>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <small class="text-muted">Belum ada aktivitas</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data untuk grafik transaksi
    const transactionData = {
        labels: {!! json_encode($dashboardData['monthly_chart']['labels'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun']) !!},
        datasets: [{
            label: 'Pemasukan',
            data: {!! json_encode($dashboardData['monthly_chart']['income'] ?? [0,0,0,0,0,0]) !!},
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            borderColor: '#10b981',
            borderWidth: 2,
            tension: 0.4,
            fill: true
        }, {
            label: 'Pengeluaran',
            data: {!! json_encode($dashboardData['monthly_chart']['expense'] ?? [0,0,0,0,0,0]) !!},
            backgroundColor: 'rgba(239, 68, 68, 0.2)',
            borderColor: '#ef4444',
            borderWidth: 2,
            tension: 0.4,
            fill: true
        }]
    };

    // Konfigurasi grafik transaksi
    const ctx = document.getElementById('transactionChart').getContext('2d');
    const transactionChart = new Chart(ctx, {
        type: 'line',
        data: transactionData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                minimumFractionDigits: 0
                            }).format(context.parsed.y);
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp' + (value / 1000000).toFixed(1) + 'Jt';
                            }
                            return 'Rp' + value;
                        }
                    }
                }
            }
        }
    });

    // Data untuk grafik kategori
    const categoryData = {
        labels: {!! json_encode(array_slice(array_column($dashboardData['category_analysis'] ?? [], 'name'), 0, 5)) !!},
        datasets: [{
            data: {!! json_encode(array_slice(array_column($dashboardData['category_analysis'] ?? [], 'percentage'), 0, 5)) !!},
            backgroundColor: {!! json_encode(array_slice(array_column($dashboardData['category_analysis'] ?? [], 'color'), 0, 5)) !!},
            borderWidth: 1
        }]
    };

    // Konfigurasi grafik kategori
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: categoryData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Toggle periode grafik
    document.querySelectorAll('[data-period]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-period]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const period = this.getAttribute('data-period');
            // Di sini Anda bisa menambahkan logika untuk mengubah data grafik
            // berdasarkan periode yang dipilih
        });
    });

    // Toggle tipe kategori
    document.querySelectorAll('[data-type]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-type]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const type = this.getAttribute('data-type');
            // Di sini Anda bisa menambahkan logika untuk mengubah data kategori
        });
    });

    // Format semua currency
    document.querySelectorAll('.currency').forEach(element => {
        const value = parseFloat(element.textContent.replace(/[^0-9.-]+/g,""));
        if (!isNaN(value)) {
            element.textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        }
    });

    // Auto-refresh dashboard setiap 5 menit
    setInterval(() => {
        fetch('{{ config("app.url") }}/apps/preview/refresh')
            .then(response => response.json())
            .then(data => {
                // Update statistik
                if (data.total_balance) {
                    document.querySelector('.stat-card-balance .card-title').textContent = 
                        new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            minimumFractionDigits: 0
                        }).format(data.total_balance);
                }
                
                if (data.monthly_income) {
                    document.querySelector('.stat-card-income .card-title').textContent = 
                        new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            minimumFractionDigits: 0
                        }).format(data.monthly_income);
                }
                
                if (data.monthly_expense) {
                    document.querySelector('.stat-card-expense .card-title').textContent = 
                        new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            minimumFractionDigits: 0
                        }).format(data.monthly_expense);
                }
                
                // Update waktu di sidebar
                const now = new Date();
                const timeElement = document.querySelector('.sidebar .card-body small:nth-child(3)');
                if (timeElement) {
                    timeElement.textContent = 'Update: ' + now.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            });
    }, 300000); // 5 menit
});
</script>
@endpush