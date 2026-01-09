@extends('wallet::layouts.app')

@section('title', 'Dashboard Keuangan')

@use('Modules\Wallet\Helpers\Helper')

@push('styles')
<style>
    .stat-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    
    .change-indicator {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .change-positive {
        background-color: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
    
    .change-negative {
        background-color: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }
    
    .chart-container {
        height: 250px;
        position: relative;
    }
    
    .account-distribution-chart {
        height: 200px;
    }
    
    .quick-action-btn {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        text-align: center;
        transition: all 0.3s;
    }
    
    .quick-action-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .recent-activity {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .activity-item {
        padding: 12px 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    body[data-bs-theme="dark"] .activity-item {
        border-bottom-color: rgba(255,255,255,0.05);
    }
    
    .empty-state {
        padding: 3rem 1rem;
        text-align: center;
        color: #6c757d;
    }
    
    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    body[data-bs-theme="dark"] .empty-state {
        color: #adb5bd;
    }
    
    /* Performance metrics */
    .metric-card {
        padding: 1.5rem;
        border-radius: 12px;
        height: 100%;
    }
    
    .metric-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .metric-label {
        font-size: 0.875rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    body[data-bs-theme="dark"] .metric-label {
        color: #adb5bd;
    }
    
    /* Progress bars */
    .progress-container {
        margin-top: 1rem;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.25rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .stat-card {
            margin-bottom: 1rem;
        }
        
        .chart-container {
            height: 200px;
        }
        
        .metric-value {
            font-size: 1.5rem;
        }
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <h2 class="page-title mb-2">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard Keuangan
            </h2>
            <p class="text-muted mb-0">Tinjauan menyeluruh kondisi keuangan Anda.</p>
        </div>
        <div class="col-auto">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-calendar me-2"></i>{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item period-filter" href="#" data-period="monthly">Bulan Ini</a></li>
                    <li><a class="dropdown-item period-filter" href="#" data-period="last_month">Bulan Lalu</a></li>
                    <li><a class="dropdown-item period-filter" href="#" data-period="quarterly">Kuartal Ini</a></li>
                    <li><a class="dropdown-item period-filter" href="#" data-period="yearly">Tahun Ini</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4" id="quickStats">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted fw-semibold mb-2">Total Saldo</h6>
                            <h2 class="mb-2 currency">{{ $accountSummary['total_balance'] ?? 0 }}</h2>
                            <div class="d-flex align-items-center">
                                <span class="change-indicator change-positive me-2">
                                    <i class="bi bi-arrow-up-right me-1"></i>+2.5%
                                </span>
                                <small class="text-muted">vs bulan lalu</small>
                            </div>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="bi bi-check-circle me-1"></i>{{ $accountSummary['active'] ?? 0 }} akun aktif
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted fw-semibold mb-2">Total Pemasukan</h6>
                            <h2 class="mb-2 currency">{{ $totalIncome ?? 0 }}</h2>
                            <div class="d-flex align-items-center">
                                <span class="change-indicator change-positive me-2">
                                    <i class="bi bi-arrow-up-right me-1"></i>+15%
                                </span>
                                <small class="text-muted">vs bulan lalu</small>
                            </div>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-success">
                            <i class="bi bi-calendar me-1"></i>Bulan {{ \Carbon\Carbon::now()->translatedFormat('F') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted fw-semibold mb-2">Total Pengeluaran</h6>
                            <h2 class="mb-2 currency">{{ $totalExpense ?? 0 }}</h2>
                            <div class="d-flex align-items-center">
                                <span class="change-indicator change-negative me-2">
                                    <i class="bi bi-arrow-down-right me-1"></i>-8%
                                </span>
                                <small class="text-muted">vs bulan lalu</small>
                            </div>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-danger">
                            <i class="bi bi-calendar me-1"></i>Bulan {{ \Carbon\Carbon::now()->translatedFormat('F') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted fw-semibold mb-2">Aliran Kas Bersih</h6>
                            <h2 class="mb-2 currency">{{ $netCashFlow ?? 0 }}</h2>
                            <div class="d-flex align-items-center">
                                @if($netCashFlow >= 0)
                                    <span class="change-indicator change-positive me-2">
                                        <i class="bi bi-arrow-up-right me-1"></i>Positif
                                    </span>
                                @else
                                    <span class="change-indicator change-negative me-2">
                                        <i class="bi bi-arrow-down-right me-1"></i>Negatif
                                    </span>
                                @endif
                                <small class="text-muted">bulan ini</small>
                            </div>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="{{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi bi-graph-{{ $netCashFlow >= 0 ? 'up' : 'down' }} me-1"></i>
                            {{ $netCashFlow >= 0 ? 'Surplus' : 'Defisit' }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="row mb-4">
        <!-- Monthly Trends Chart -->
        <div class="col-xl-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Tren Bulanan
                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            6 Bulan Terakhir
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item trend-filter" href="#" data-months="3">3 Bulan</a></li>
                            <li><a class="dropdown-item trend-filter" href="#" data-months="6">6 Bulan</a></li>
                            <li><a class="dropdown-item trend-filter" href="#" data-months="12">12 Bulan</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="monthlyTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Account Distribution -->
        <div class="col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Distribusi Akun
                    </h5>
                </div>
                <div class="card-body">
                    <div class="account-distribution-chart">
                        <canvas id="accountDistributionChart"></canvas>
                    </div>
                    <div class="mt-3">
                        @if($accountTypeDistribution->isNotEmpty())
                            @foreach($accountTypeDistribution as $distribution)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">{{ $distribution->type->label() ?? $distribution->type }}</span>
                                    <span class="fw-semibold currency">{{ $distribution->total_balance ?? 0 }}</span>
                                </div>
                                <div class="progress mb-3" style="height: 6px;">
                                    @php
                                        $percentage = $accountSummary['total_balance'] > 0 
                                            ? round(($distribution->total_balance / $accountSummary['total_balance']) * 100, 2) 
                                            : 0;
                                    @endphp
                                    <div class="progress-bar" 
                                         role="progressbar" 
                                         style="width: {{ $percentage }}%; background-color: {{ Helper::accountTypeMap($distribution->type->value)['color'] }};"
                                         aria-valuenow="{{ $percentage }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            @endforeach
                        @else
                            <div class="empty-state py-3">
                                <div class="empty-state-icon">
                                    <i class="bi bi-pie-chart"></i>
                                </div>
                                <p class="text-muted mb-0">Belum ada data distribusi</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Performance and Quick Actions -->
    <div class="row">
        <!-- Account Performance -->
        <div class="col-xl-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Kinerja Akun (Bulan Ini)
                    </h5>
                    <a href="{{ route('apps.accounts.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="card-body">
                    @if(count($accountAnalytics) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Akun</th>
                                        <th class="text-end">Saldo</th>
                                        <th class="text-end">Pemasukan</th>
                                        <th class="text-end">Pengeluaran</th>
                                        <th class="text-end">Aliran Bersih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($accountAnalytics as $analytic)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="account-icon me-3" style="background-color: {{ $analytic['account']->color }}20; color: {{ $analytic['account']->color }}">
                                                        <i class="{{ $analytic['account']->icon }}"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="d-block">{{ $analytic['account']->name }}</strong>
                                                        <small class="text-muted">{{ $analytic['account']->type->label() }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end currency">{{ $analytic['account']->balance->getAmount()->toInt() }}</td>
                                            <td class="text-end text-success currency">{{ $analytic['income']->getAmount()->toInt() }}</td>
                                            <td class="text-end text-danger currency">{{ $analytic['expense']->getAmount()->toInt() }}</td>
                                            <td class="text-end {{ $analytic['net_flow']->getAmount()->toInt() >= 0 ? 'text-success' : 'text-danger' }} currency">
                                                {{ $analytic['net_flow']->getAmount()->toInt() }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state py-4">
                            <div class="empty-state-icon">
                                <i class="bi bi-bar-chart"></i>
                            </div>
                            <h5 class="mb-3">Belum ada data kinerja</h5>
                            <p class="text-muted mb-4">Mulai dengan menambahkan transaksi ke akun Anda.</p>
                            <a href="{{ route('apps.accounts.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-2"></i>Tambah Akun
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Quick Actions & Recent Activity -->
        <div class="col-xl-4 mb-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning me-2"></i>Aksi Cepat
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="{{ route('apps.accounts.create') }}" class="btn btn-primary quick-action-btn">
                                <i class="bi bi-plus-circle display-6 mb-2"></i>
                                <div>Tambah Akun</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('apps.accounts.index') }}" class="btn btn-success quick-action-btn">
                                <i class="bi bi-arrow-left-right display-6 mb-2"></i>
                                <div>Transfer Dana</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-info quick-action-btn" data-bs-toggle="modal" data-bs-target="#reportsModal">
                                <i class="bi bi-file-text display-6 mb-2"></i>
                                <div>Laporan</div>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-warning quick-action-btn" id="recalculateBalances">
                                <i class="bi bi-arrow-clockwise display-6 mb-2"></i>
                                <div>Hitung Ulang</div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Popular Accounts -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-star me-2"></i>Akun Populer
                    </h5>
                </div>
                <div class="card-body">
                    @if($popularAccounts && $popularAccounts->isNotEmpty())
                        @foreach($popularAccounts as $account)
                            <div class="d-flex align-items-center mb-3">
                                <div class="account-icon me-3" style="background-color: {{ $account->color }}20; color: {{ $account->color }}">
                                    <i class="{{ $account->icon }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $account->name }}</h6>
                                    <small class="text-muted">{{ $account->type->label() }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="currency">{{ $account->balance->getAmount()->toInt() }}</div>
                                    @if($account->is_default)
                                        <small class="text-primary"><i class="bi bi-star-fill"></i> Default</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-3">
                            <i class="bi bi-star text-muted display-6 mb-2"></i>
                            <p class="text-muted mb-0">Belum ada akun populer</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

<!-- Reports Modal -->
<div class="modal fade" id="reportsModal" tabindex="-1" aria-labelledby="reportsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportsModalLabel">
                    <i class="bi bi-file-text me-2"></i>Generate Laporan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="bi bi-cash-stack me-2"></i>Laporan Saldo Akun
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="bi bi-arrow-left-right me-2"></i>Laporan Arus Kas
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="bi bi-pie-chart me-2"></i>Laporan Kategori
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar me-2"></i>Laporan Bulanan
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format currency for all elements with .currency class
        document.querySelectorAll('.currency').forEach(element => {
            const value = textContent;
            if (!isNaN(value)) {
                // Divide by 100 because we store in minor units (cents)
                element.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(value);
            }
        });
        
        // Monthly Trends Chart
        const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        
        // Prepare data for chart
        const monthlyTrends = @json($monthlyTrends);
        const labels = monthlyTrends.map(trend => trend.month);
        const incomeData = monthlyTrends.map(trend => trend.income);
        const expenseData = monthlyTrends.map(trend => trend.expense);
        const netFlowData = monthlyTrends.map(trend => trend.net_flow);
        
        // Create chart
        const monthlyTrendsChart = new Chart(monthlyTrendsCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: incomeData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Pengeluaran',
                        data: expenseData,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Aliran Bersih',
                        data: netFlowData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: false,
                        borderDash: [5, 5]
                    }
                ]
            },
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
                                }).format(context.raw);
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
                                return new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: 'IDR',
                                    minimumFractionDigits: 0
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });
        
        // Account Distribution Chart
        const accountDistributionCtx = document.getElementById('accountDistributionChart').getContext('2d');
        const accountTypeDistribution = @json($accountTypeDistribution);
        
        // Prepare data
        const distributionLabels = accountTypeDistribution.map(item => {
            return item.type && item.type.label ? item.type.label : item.type;
        });
        const distributionData = accountTypeDistribution.map(item => item.total_balance);
        const distributionColors = accountTypeDistribution.map(item => getAccountTypeColor(item.type));
        
        // Create chart
        const accountDistributionChart = new Chart(accountDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: distributionLabels,
                datasets: [{
                    data: distributionData,
                    backgroundColor: distributionColors,
                    borderWidth: 1
                }]
            },
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
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = distributionData.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                
                                return `${label}: ${new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: 'IDR',
                                    minimumFractionDigits: 0
                                }).format(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Helper function to get color for account type
        function getAccountTypeColor(type) {
            const typeStr = type && typeof type === 'object' ? type.value : type;
            
            const colorMap = {
                'cash': '#20c997',
                'bank': '#0d6efd',
                'ewallet': '#6f42c1',
                'credit_card': '#fd7e14',
                'investment': '#17a2b8',
                'savings': '#198754',
                'loan': '#dc3545',
                'other': '#6c757d'
            };
            
            return colorMap[typeStr] || '#6c757d';
        }
        
        // Period filter
        document.querySelectorAll('.period-filter').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const period = this.getAttribute('data-period');
                loadDashboardData(period);
            });
        });
        
        // Trend filter
        document.querySelectorAll('.trend-filter').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const months = this.getAttribute('data-months');
                loadTrendsData(months);
            });
        });
        
        // Recalculate balances button
        const recalculateBtn = document.getElementById('recalculateBalances');
        if (recalculateBtn) {
            recalculateBtn.addEventListener('click', function() {
                if (confirm('Apakah Anda yakin ingin menghitung ulang semua saldo akun?')) {
                    fetch('{{ route("apps.financial") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', 'Berhasil', data.message);
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showToast('error', 'Gagal', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('error', 'Gagal', 'Terjadi kesalahan');
                    });
                }
            });
        }
        
        // Function to load dashboard data
        function loadDashboardData(period) {
            const button = document.querySelector('.dropdown-toggle');
            const loadingText = 'Memuat...';
            const originalText = button.innerHTML;
            
            button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
            button.disabled = true;
            
            fetch('{{ route("apps.financial") }}?period=' + period)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update summary cards
                        updateSummaryCards(data.data);
                        
                        // Update chart data
                        updateCharts(data.data);
                        
                        showToast('success', 'Berhasil', 'Data dashboard diperbarui');
                    } else {
                        showToast('error', 'Gagal', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Gagal', 'Terjadi kesalahan');
                })
                .finally(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }
        
        // Function to update summary cards
        function updateSummaryCards(data) {
            // Update total balance
            const totalBalanceEl = document.querySelector('.stat-card:nth-child(1) h2.currency');
            if (totalBalanceEl && data.account_summary.total_balance) {
                totalBalanceEl.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(data.account_summary.total_balance);
            }
            
            // Update income
            const incomeEl = document.querySelector('.stat-card:nth-child(2) h2.currency');
            if (incomeEl && data.total_income) {
                incomeEl.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(data.total_income);
            }
            
            // Update expense
            const expenseEl = document.querySelector('.stat-card:nth-child(3) h2.currency');
            if (expenseEl && data.total_expense) {
                expenseEl.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(data.total_expense);
            }
            
            // Update net cash flow
            const cashFlowEl = document.querySelector('.stat-card:nth-child(4) h2.currency');
            if (cashFlowEl && data.net_cash_flow) {
                cashFlowEl.textContent = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(data.net_cash_flow);
                
                // Update indicator
                const indicator = cashFlowEl.closest('.card-body').querySelector('.change-indicator');
                if (indicator) {
                    if (data.net_cash_flow >= 0) {
                        indicator.className = 'change-indicator change-positive me-2';
                        indicator.innerHTML = '<i class="bi bi-arrow-up-right me-1"></i>Positif';
                    } else {
                        indicator.className = 'change-indicator change-negative me-2';
                        indicator.innerHTML = '<i class="bi bi-arrow-down-right me-1"></i>Negatif';
                    }
                }
            }
        }
        
        // Function to update charts
        function updateCharts(data) {
            // Update monthly trends chart
            if (data.monthly_trends) {
                monthlyTrendsChart.data.labels = data.monthly_trends.map(trend => trend.month);
                monthlyTrendsChart.data.datasets[0].data = data.monthly_trends.map(trend => trend.income);
                monthlyTrendsChart.data.datasets[1].data = data.monthly_trends.map(trend => trend.expense);
                monthlyTrendsChart.data.datasets[2].data = data.monthly_trends.map(trend => trend.net_flow);
                monthlyTrendsChart.update();
            }
        }
        
        // Function to load trends data
        function loadTrendsData(months) {
            // Update button text
            const button = document.querySelector('.card-header .dropdown-toggle');
            button.textContent = `${months} Bulan Terakhir`;
            
            // Reload page with query parameter
            const url = new URL(window.location.href);
            url.searchParams.set('months', months);
            window.location.href = url.toString();
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
            
            toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', function () {
                toastContainer.remove();
            });
        }
        
        // Auto-refresh dashboard every 5 minutes
        setTimeout(() => {
            fetch('{{ route("apps.financial") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update stats silently
                        console.log('Dashboard stats refreshed');
                    }
                });
        }, 300000); // 5 minutes
    });
</script>
@endpush