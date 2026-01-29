@extends('wallet::layouts.app')

@section('content')
@include('wallet::partials.fab')
<!-- Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-2">
                    <i class="bi bi-bar-chart-line me-2"></i>Laporan Keuangan
                </h1>
                <p class="text-muted mb-0">Analisis dan visualisasi data keuangan Anda</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="exportReport()">
                    <i class="bi bi-download me-1"></i>Ekspor
                </button>
                <button class="btn btn-primary" onclick="refreshCharts()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filter Controls - Hanya Akun -->
<div class="row mb-4">
    <div class="col-12">
        <div class="filter-section">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Filter Akun</label>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="account-filter">
                            <option value="">Semua Akun</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary" onclick="applyAccountFilter()">
                            <i class="bi bi-funnel me-1"></i>Filter Akun
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4" id="summary-cards">
    <div class="col-md-6 mb-3">
        <div class="card stat-card border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Total Pendapatan</h6>
                        <h3 class="mb-0" id="total-income">Rp 0</h3>
                        <small class="text-muted" id="income-count">0 transaksi</small>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10">
                        <i class="bi bi-arrow-up-circle fs-4 text-success"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-success bg-opacity-10 text-success summary-badge">
                        <i class="bi bi-arrow-up me-1"></i>100%
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card stat-card border-start border-danger border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Total Pengeluaran</h6>
                        <h3 class="mb-0" id="total-expense">Rp 0</h3>
                        <small class="text-muted" id="expense-count">0 transaksi</small>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10">
                        <i class="bi bi-arrow-down-circle fs-4 text-danger"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-danger bg-opacity-10 text-danger summary-badge">
                        <i class="bi bi-arrow-down me-1"></i>100%
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card stat-card border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Saldo Bersih</h6>
                        <h3 class="mb-0" id="net-flow">Rp 0</h3>
                        <small class="text-muted" id="net-flow-label">Arus Kas</small>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10">
                        <i class="bi bi-cash-stack fs-4 text-primary"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary summary-badge">
                        <i class="bi bi-graph-up me-1"></i>100%
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card stat-card border-start border-purple border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted mb-2">Total Transfer</h6>
                        <h3 class="mb-0" id="total-transfer">Rp 0</h3>
                        <small class="text-muted">Antar Akun</small>
                    </div>
                    <div class="stat-icon" style="background-color: rgba(139, 92, 246, 0.1);">
                        <i class="bi bi-arrow-left-right fs-4" style="color: #8b5cf6;"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge summary-badge" style="background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                        <i class="bi bi-repeat me-1"></i>100%
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Chart - Terpisah untuk Chart -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-sliders me-2"></i>Pengaturan Chart
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tipe Laporan</label>
                        <select class="form-select" id="report-type">
                            <option value="monthly">Bulanan (Per Tahun)</option>
                            <option value="yearly">Tahunan</option>
                            <option value="daily">Harian (Per Bulan)</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="year-selection">
                        <label class="form-label">Tahun</label>
                        <select class="form-select" id="year-filter">
                            @for($i = date('Y'); $i >= 2020; $i--)
                                <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3" id="month-selection" style="display: none;">
                        <label class="form-label">Bulan</label>
                        <select class="form-select" id="month-filter">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $i == date('m') ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-outline-primary w-100" onclick="loadChartData()">
                            <i class="bi bi-eye me-1"></i>Lihat Chart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1: Trend Chart -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>Trend Pendapatan vs Pengeluaran
                </h5>
                <div class="chart-toolbar">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" onclick="toggleChartType('line')">
                            <i class="bi bi-graph-up"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleChartType('bar')">
                            <i class="bi bi-bar-chart"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="incomeExpenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2: Category & Account -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart me-2"></i>Pengeluaran per Kategori
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="expenseCategoryChart"></canvas>
                </div>
                <div class="mt-3" id="category-legend">
                    <!-- Legend akan diisi dinamis -->
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-wallet me-2"></i>Distribusi Saldo Akun
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="accountBalanceChart"></canvas>
                </div>
                <div class="mt-3" id="account-legend">
                    <!-- Legend akan diisi dinamis -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 3: Budget Analysis -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-currency-exchange me-2"></i>Analisis Anggaran vs Realisasi
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="budgetChart"></canvas>
                </div>
                <div class="row mt-3" id="budget-summary">
                    <!-- Summary anggaran akan diisi dinamis -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 4: Transaction Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-week me-2"></i>Aktivitas Transaksi per Hari
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="transactionActivityChart"></canvas>
                </div>
                <div class="row mt-3" id="activity-summary">
                    <!-- Summary aktivitas akan diisi dinamis -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Global variables
    let charts = {};
    let reportData = {};
    let currentAccountId = '';
    let currentReportType = 'monthly';
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth() + 1;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initReportTypeListener();
        loadInitialData();
    });

    // Initialize report type listener
    function initReportTypeListener() {
        const reportTypeSelect = document.getElementById('report-type');
        const monthSelection = document.getElementById('month-selection');
        const yearSelection = document.getElementById('year-selection');
        
        reportTypeSelect.addEventListener('change', function() {
            currentReportType = this.value;
            
            if (currentReportType === 'daily') {
                monthSelection.style.display = 'block';
                yearSelection.style.display = 'block';
            } else if (currentReportType === 'monthly') {
                monthSelection.style.display = 'none';
                yearSelection.style.display = 'block';
            } else if (currentReportType === 'yearly') {
                monthSelection.style.display = 'none';
                yearSelection.style.display = 'none';
            }
        });
    }

    // Apply account filter
    async function applyAccountFilter() {
        currentAccountId = document.getElementById('account-filter').value;
        await loadDashboardSummary();
    }

    // Load chart data based on selected type
    async function loadChartData() {
        currentReportType = document.getElementById('report-type').value;
        currentYear = document.getElementById('year-filter').value;
        
        if (currentReportType === 'daily') {
            currentMonth = document.getElementById('month-filter').value;
            await loadMonthlyReport(currentYear, currentMonth);
        } else if (currentReportType === 'monthly') {
            await loadYearlyReport(currentYear);
        } else if (currentReportType === 'yearly') {
            await loadYearlyComparison();
        }
    }

    // Load initial data
    async function loadInitialData() {
        await loadDashboardSummary();
        await loadChartData();
    }

    // Load dashboard summary with account filter
    async function loadDashboardSummary() {
        try {
            const filters = {
                account_id: currentAccountId || ''
            };

            const queryString = new URLSearchParams(filters).toString();
            const response = await authFetch(`{{ config('app.url') }}/api/apps/reports/dashboard-summary?${queryString}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                reportData = result.data;
                updateSummaryCards(reportData.financial_summary);
                updateChartLegends(reportData);
            } else {
                throw new Error(result.message || 'Failed to load data');
            }
        } catch (error) {
            console.error('Error loading dashboard summary:', error);
            alert('Gagal memuat summary data. Silakan coba lagi. ' + error.message);
        }
    }

    // Load monthly report for daily chart
    async function loadMonthlyReport(year, month) {
        try {
            const response = await authFetch(`{{ config('app.url') }}/api/apps/reports/monthly/${year}/${month}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                updateMonthlyCharts(result.data);
            } else {
                throw new Error(result.message || 'Failed to load monthly data');
            }
        } catch (error) {
            console.error('Error loading monthly report:', error);
            alert('Gagal memuat data bulanan. Silakan coba lagi. ' + error.message);
        }
    }

    // Load yearly report for monthly chart
    async function loadYearlyReport(year) {
        try {
            const response = await authFetch(`{{ config('app.url') }}/api/apps/reports/yearly/${year}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                updateYearlyCharts(result.data);
            } else {
                throw new Error(result.message || 'Failed to load yearly data');
            }
        } catch (error) {
            console.error('Error loading yearly report:', error);
            alert('Gagal memuat data tahunan. Silakan coba lagi. ' + error.message);
        }
    }

    // Load yearly comparison for multi-year chart
    async function loadYearlyComparison() {
        try {
            const currentYear = new Date().getFullYear();
            const startYear = currentYear - 5; // Tampilkan 5 tahun terakhir
            
            const response = await authFetch(`{{ config('app.url') }}/api/apps/reports/custom`, {
                method: 'POST',
                body: JSON.stringify({
                    report_type: 'income_expense_trend',
                    start_date: `${startYear}-01-01`,
                    end_date: `${currentYear}-12-31`,
                    group_by: 'year'
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                updateYearlyComparisonCharts(result.data);
            } else {
                throw new Error(result.message || 'Failed to load yearly comparison data');
            }
        } catch (error) {
            console.error('Error loading yearly comparison:', error);
            alert('Gagal memuat perbandingan tahunan. Silakan coba lagi. ' + error.message);
        }
    }

    // Update monthly charts
    function updateMonthlyCharts(data) {
        // Update income expense trend for daily view
        if (charts.incomeExpense) {
            charts.incomeExpense.destroy();
        }
        
        const chartType = document.getElementById('chart-type').value;
        charts.incomeExpense = createLineChart('incomeExpenseChart', data.daily_trend, chartType);
        
        // Update other charts from dashboard data if available
        if (reportData) {
            updateChartLegends(reportData);
        }
    }

    // Update yearly charts
    function updateYearlyCharts(data) {
        // Update income expense trend for monthly view
        if (charts.incomeExpense) {
            charts.incomeExpense.destroy();
        }
        
        const chartType = document.getElementById('chart-type').value;
        charts.incomeExpense = createLineChart('incomeExpenseChart', data.monthly_trend, chartType);
        
        // Update other charts from dashboard data if available
        if (reportData) {
            updateChartLegends(reportData);
        }
    }

    // Update yearly comparison charts
    function updateYearlyComparisonCharts(data) {
        // Update income expense trend for yearly comparison
        if (charts.incomeExpense) {
            charts.incomeExpense.destroy();
        }
        
        const chartType = document.getElementById('chart-type').value;
        charts.incomeExpense = createLineChart('incomeExpenseChart', data, chartType);
        
        // Update other charts from dashboard data if available
        if (reportData) {
            updateChartLegends(reportData);
        }
    }

    // Update chart legends
    function updateChartLegends(data) {
        if (data.category_analysis) {
            updateCategoryLegend(data.category_analysis);
        }
        
        if (data.account_analysis) {
            updateAccountLegend(data.account_analysis);
        }
        
        if (data.budget_analysis) {
            updateBudgetSummary(data.budget_analysis);
        }
    }

    // Update summary cards
    function updateSummaryCards(summary) {
        document.getElementById('total-income').textContent = summary.total_income;
        document.getElementById('income-count').textContent = `${summary.income_count} transaksi`;
        
        document.getElementById('total-expense').textContent = summary.total_expense;
        document.getElementById('expense-count').textContent = `${summary.expense_count} transaksi`;
        
        document.getElementById('net-flow').textContent = formatCurrency(summary.net_flow);
        document.getElementById('total-transfer').textContent = summary.total_transfer;
        
        // Update progress badges
        const total = (parseInt(summary.total_income) || 0) + (parseInt(summary.total_expense) || 0);
        if (total > 0) {
            const incomePercent = Math.round((parseInt(summary.total_income) / total) * 100);
            const expensePercent = Math.round((parseInt(summary.total_expense) / total) * 100);
            
            document.querySelector('#summary-cards .col-md-3:nth-child(1) .summary-badge').innerHTML = 
                `<i class="bi bi-arrow-up me-1"></i>${incomePercent}%`;
            document.querySelector('#summary-cards .col-md-3:nth-child(2) .summary-badge').innerHTML = 
                `<i class="bi bi-arrow-down me-1"></i>${expensePercent}%`;
            
            // Update net flow label
            const netLabel = summary.net_flow >= 0 ? 'Surplus' : 'Defisit';
            document.getElementById('net-flow-label').textContent = netLabel;
            
            // Update net flow color
            const netCard = document.querySelector('#summary-cards .col-md-3:nth-child(3) .card');
            const netIcon = document.querySelector('#summary-cards .col-md-3:nth-child(3) .stat-icon');
            const netBadge = document.querySelector('#summary-cards .col-md-3:nth-child(3) .summary-badge');
            
            if (summary.net_flow >= 0) {
                netCard.className = netCard.className.replace(/border-\w+-\d+/, 'border-success border-4');
                netIcon.className = netIcon.className.replace(/bg-\w+-\d+/, 'bg-success bg-opacity-10');
                netIcon.querySelector('i').className = netIcon.querySelector('i').className.replace(/text-\w+-\d+/, 'text-success');
                netBadge.className = netBadge.className.replace(/bg-\w+-\d+/, 'bg-success bg-opacity-10');
                netBadge.className = netBadge.className.replace(/text-\w+-\d+/, 'text-success');
            } else {
                netCard.className = netCard.className.replace(/border-\w+-\d+/, 'border-danger border-4');
                netIcon.className = netIcon.className.replace(/bg-\w+-\d+/, 'bg-danger bg-opacity-10');
                netIcon.querySelector('i').className = netIcon.querySelector('i').className.replace(/text-\w+-\d+/, 'text-danger');
                netBadge.className = netBadge.className.replace(/bg-\w+-\d+/, 'bg-danger bg-opacity-10');
                netBadge.className = netBadge.className.replace(/text-\w+-\d+/, 'text-danger');
            }
        }
    }

    // Format currency
    function formatCurrency(value) {
        if (!value && value !== 0) return 'Rp 0';
        
        try {
            // Jika value sudah dalam format string Rp, return langsung
            if (typeof value === 'string' && value.includes('Rp')) {
                return value;
            }
            
            // Jika value adalah integer (minor currency), convert ke rupiah
            const numValue = parseInt(value) || 0;
            const majorValue = numValue / 100; // Convert dari minor ke major
            
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(majorValue);
        } catch (error) {
            console.error('Error formatting currency:', error, value);
            return 'Rp 0';
        }
    }

    // Custom fetch dengan authentication
    async function authFetch(url, options = {}) {
        const defaultOptions = {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const mergedOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, mergedOptions);
            
            if (response.status === 401) {
                window.location.href = '{{ route("login") }}';
                throw new Error('Unauthorized - Redirecting to login');
            }
            
            if (response.status === 419) {
                await getNewCsrfToken();
                mergedOptions.headers['X-CSRF-TOKEN'] = csrfToken;
                return fetch(url, mergedOptions);
            }
            
            return response;
        } catch (error) {
            console.error('Fetch error:', error);
            throw error;
        }
    }

    // Get new CSRF token jika expired
    async function getNewCsrfToken() {
        try {
            const response = await fetch('/sanctum/csrf-cookie', {
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                console.log('CSRF token refreshed');
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
        }
    }

    // Create line chart
    function createLineChart(canvasId, chartData, type = 'line') {
        const ctx = document.getElementById(canvasId).getContext('2d');
        return new Chart(ctx, {
            type: type,
            data: {
                labels: chartData.labels,
                datasets: chartData.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
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
                                label += formatCurrency(context.raw);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'nearest'
                }
            }
        });
    }

    // Update category legend
    function updateCategoryLegend(chartData) {
        const legendContainer = document.getElementById('category-legend');
        if (!chartData.labels || chartData.labels.length === 0) {
            legendContainer.innerHTML = '<p class="text-muted text-center">Tidak ada data kategori</p>';
            return;
        }
        
        let legendHtml = '<div class="row g-2">';
        const total = chartData.datasets[0]?.data?.reduce((a, b) => a + b, 0) || 0;
        const colors = chartData.datasets[0]?.backgroundColor || [];
        
        chartData.labels.forEach((label, index) => {
            const value = chartData.datasets[0]?.data[index] || 0;
            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
            const color = colors[index] || '#cccccc';
            
            legendHtml += `
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge me-2" style="background-color: ${color}; width: 12px; height: 12px; border-radius: 2px;"></span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <small class="text-truncate" style="max-width: 120px;">${label}</small>
                                <small class="text-muted">${percentage}%</small>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar" style="width: ${percentage}%; background-color: ${color};"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        legendHtml += '</div>';
        legendContainer.innerHTML = legendHtml;
    }

    // Update account legend
    function updateAccountLegend(chartData) {
        const legendContainer = document.getElementById('account-legend');
        if (!chartData.labels || chartData.labels.length === 0) {
            legendContainer.innerHTML = '<p class="text-muted text-center">Tidak ada data akun</p>';
            return;
        }
        
        let legendHtml = '<div class="row g-2">';
        const total = chartData.datasets[0]?.data?.reduce((a, b) => a + b, 0) || 0;
        const colors = chartData.datasets[0]?.backgroundColor || [];
        
        chartData.labels.forEach((label, index) => {
            const value = chartData.datasets[0]?.data[index] || 0;
            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
            const color = colors[index] || '#cccccc';
            
            legendHtml += `
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge me-2" style="background-color: ${color}; width: 12px; height: 12px; border-radius: 2px;"></span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <small class="text-truncate" style="max-width: 120px;">${label}</small>
                                <small>${formatCurrency(value)}</small>
                            </div>
                            <div class="progress progress-thin">
                                <div class="progress-bar" style="width: ${percentage}%; background-color: ${color};"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        legendHtml += '</div>';
        legendContainer.innerHTML = legendHtml;
    }

    // Update budget summary
    function updateBudgetSummary(chartData) {
        const summaryContainer = document.getElementById('budget-summary');
        if (!chartData.summary) {
            summaryContainer.innerHTML = '<p class="text-muted text-center">Tidak ada data anggaran</p>';
            return;
        }
        
        const summary = chartData.summary;
        const usagePercentage = summary.total_budget > 0 ? 
            Math.round((summary.total_spent / summary.total_budget) * 100) : 0;
        
        const statusColor = usagePercentage >= 90 ? 'danger' : 
                           usagePercentage >= 70 ? 'warning' : 'success';
        
        const statusIcon = usagePercentage >= 90 ? 'bi-exclamation-triangle' :
                          usagePercentage >= 70 ? 'bi-exclamation-circle' : 'bi-check-circle';
        
        summaryContainer.innerHTML = `
            <div class="col-md-4 mb-3">
                <div class="card border-0 text-bg-light">
                    <div class="card-body text-center">
                        <h3 class="text-primary">${formatCurrency(summary.total_budget)}</h3>
                        <p class="text-muted mb-0">Total Anggaran</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 text-bg-light">
                    <div class="card-body text-center">
                        <h3 class="text-${statusColor}">${formatCurrency(summary.total_spent)}</h3>
                        <p class="text-muted mb-0">Total Terpakai</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 text-bg-light">
                    <div class="card-body text-center">
                        <h3 class="text-success">${formatCurrency(summary.total_remaining)}</h3>
                        <p class="text-muted mb-0">Sisa Anggaran</p>
                    </div>
                </div>
            </div>
            <div class="col-12 mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi ${statusIcon} text-${statusColor} me-2"></i>
                        <span class="text-${statusColor} fw-medium">${usagePercentage}% Terpakai</span>
                    </div>
                    <div class="progress" style="width: 70%; height: 10px;">
                        <div class="progress-bar bg-${statusColor}" style="width: ${usagePercentage}%"></div>
                    </div>
                </div>
            </div>
        `;
    }

    // Toggle chart type
    function toggleChartType(type) {
        document.getElementById('chart-type').value = type;
        
        // Update button states
        const buttons = document.querySelectorAll('.chart-toolbar .btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        
        if (type === 'line') {
            buttons[0].classList.add('active');
        } else {
            buttons[1].classList.add('active');
        }
        
        if (charts.incomeExpense) {
            charts.incomeExpense.destroy();
            // Load ulang chart dengan tipe yang baru
            loadChartData();
        }
    }

    // Refresh charts
    function refreshCharts() {
        loadInitialData();
    }

    // Export report
    async function exportReport() {
        try {
            const filters = {
                account_id: currentAccountId || '',
                start_date: `${currentYear}-01-01`,
                end_date: `${currentYear}-12-31`,
                format: 'json'
            };
            
            const response = await authFetch(`{{ config('app.url') }}/api/apps/reports/export`, {
                method: 'POST',
                body: JSON.stringify(filters)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Create download link
                const blob = new Blob([JSON.stringify(data.data, null, 2)], { 
                    type: 'application/json' 
                });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `laporan-keuangan-${new Date().toISOString().slice(0,10)}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
        } catch (error) {
            console.error('Error exporting report:', error);
            alert('Gagal mengekspor laporan. Silakan coba lagi.' + error.message);
        }
    }
</script>
@endpush