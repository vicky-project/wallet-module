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

<!-- Filter Controls -->
<div class="row mb-4">
  <div class="col-12">
    <div class="filter-section">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Periode Tanggal</label>
          <div class="input-group date-range-input">
            <span class="input-group-text">
              <i class="bi bi-calendar"></i>
            </span>
            <input type="date" class="form-control" id="start-date">
            <span class="input-group-text">s/d</span>
            <input type="date" class="form-control" id="end-date">
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Akun</label>
          <select class="form-select" id="account-filter">
            <option value="">Semua Akun</option>
            @foreach($accounts as $account)
              <option value="{{ $account->id }}">{{ $account->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Grup Waktu</label>
          <select class="form-select" id="time-group">
            <option value="day">Harian</option>
            <option value="week">Mingguan</option>
            <option value="month" selected>Bulanan</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Tipe Chart</label>
          <select class="form-select" id="chart-type">
            <option value="line">Line</option>
            <option value="bar">Bar</option>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button class="btn btn-primary w-100" onclick="applyFilters()">
            <i class="bi bi-funnel me-1"></i>Terapkan
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4" id="summary-cards">
  <!-- Cards will be populated by JavaScript -->
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
          <canvas id="incomeExpenseChart">
            <span class="placeholder col-12"></span>
          </canvas>
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
          <!-- Legend will be populated dynamically -->
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
          <!-- Legend will be populated dynamically -->
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
          <!-- Budget summary will be populated dynamically -->
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
          <!-- Activity summary will be populated dynamically -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Loading Spinner -->
<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0">
      <div class="modal-body text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="mb-0">Memuat data laporan...</h5>
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
    let loadingModal = null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    // Initialize date inputs
    function initDateInputs() {
      const today = new Date();
      const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
      document.getElementById('start-date').value = formatDate(firstDay);
      document.getElementById('end-date').value = formatDate(today);
    }

    function formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    // Format currency
    function formatCurrency(value) {
      return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(value);
    }

        // Show loading modal
        function showLoading() {
            if (!loadingModal) {
                loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            }
            loadingModal.show();
        }

        // Hide loading modal
        function hideLoading() {
            if (loadingModal) {
                loadingModal.hide();
            }
            
            loadingModal = null;
        }
        
        // Custom fetch dengan authentication
    async function authFetch(url, options = {}) {
      const defaultOptions = {
        credentials: 'same-origin', // Mengirim session cookies
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
        
        // Handle unauthorized access
        if (response.status === 401) {
          window.location.href = '{{ route("login") }}';
          throw new Error('Unauthorized - Redirecting to login');
        }
        
        // Handle session timeout or CSRF token mismatch
        if (response.status === 419) {
          // Try to get new CSRF token
          await getNewCsrfToken();
          // Retry request with new token
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
          // CSRF token sudah otomatis di-set di cookie
          // Kita bisa reload halaman atau lanjutkan dengan token baru
          console.log('CSRF token refreshed');
        }
      } catch (error) {
        console.error('Failed to refresh CSRF token:', error);
      }
    }

        // Apply filters and update charts
        async function applyFilters() {
            showLoading();
            
            const filters = {
                start_date: document.getElementById('start-date').value,
                end_date: document.getElementById('end-date').value,
                account_id: document.getElementById('account-filter').value,
                group_by: document.getElementById('time-group').value
            };

            try {
                const queryString = new URLSearchParams(filters).toString();
                const response = await authFetch(`{{ config('app.url') }}/api/apps/reports/dashboard-summary?${queryString}`);
                
                if (!response.ok) {
                    hideLoading();
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    hideLoading();
                    reportData = result.data;
                    updateSummaryCards(reportData.financial_summary);
                    updateCharts(reportData);
                } else {
                    hideLoading();
                    throw new Error(result.message || 'Failed to load data');
                }
            } catch (error) {
                hideLoading();
                console.error('Error loading report data:', error);
                alert('Gagal memuat data laporan. Silakan coba lagi. ' + error.message);
            } finally {
                hideLoading();
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
            const total = summary.total_income + summary.total_expense;
            if (total > 0) {
                const incomePercent = Math.round((summary.total_income / total) * 100);
                const expensePercent = Math.round((summary.total_expense / total) * 100);
                
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

        // Update all charts
        function updateCharts(data) {
            // Destroy existing charts
            Object.values(charts).forEach(chart => {
                if (chart) chart.destroy();
            });
            
            // Create new charts
            const chartType = document.getElementById('chart-type').value;
            
            charts.incomeExpense = createLineChart('incomeExpenseChart', 
                data.income_expense_trend, chartType);
            
            charts.expenseCategory = createDoughnutChart('expenseCategoryChart', 
                data.category_analysis);
            
            charts.accountBalance = createDoughnutChart('accountBalanceChart', 
                data.account_analysis);
            
            charts.budget = createBarChart('budgetChart', 
                data.budget_analysis);
            
            charts.transactionActivity = createBarChart('transactionActivityChart', 
                data.transaction_analysis || { labels: [], datasets: [] });
            
            // Update legends and summaries
            updateCategoryLegend(data.category_analysis);
            updateAccountLegend(data.account_analysis);
            updateBudgetSummary(data.budget_analysis);
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

        // Create doughnut chart
        function createDoughnutChart(canvasId, chartData) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Create bar chart
        function createBarChart(canvasId, chartData) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'bar',
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
                charts.incomeExpense = createLineChart('incomeExpenseChart', 
                    reportData.income_expense_trend, type);
            }
        }

        // Refresh charts
        function refreshCharts() {
            applyFilters();
        }

        // Export report
        async function exportReport() {
            showLoading();
            
            try {
                const filters = {
                    start_date: document.getElementById('start-date').value,
                    end_date: document.getElementById('end-date').value,
                    account_id: document.getElementById('account-filter').value
                };
                
                const queryString = new URLSearchParams(filters).toString();
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
            } finally {
                hideLoading();
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initDateInputs();
            try {
              applyFilters(); // Load initial data
            } catch (error) {
              console.error(error)
              alert(error.message);
            }
        });
    </script>
@endpush

@push('styles')
    <style>
        .stat-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .summary-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .date-range-input {
            max-width: 250px;
        }
        
        .progress-thin {
            height: 6px;
        }
        
        .chart-toolbar {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
            }
            
            .date-range-input {
                max-width: 100%;
            }
        }
    </style>
@endpush