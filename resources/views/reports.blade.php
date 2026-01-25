@extends('wallet::layouts.app')

@section('content')
@include('wallet::partials.fab')
<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Laporan Keuangan</h5>
        <div class="card-tools">
          <div class="input-group input-group-sm" style="width: 200px;">
            <input type="text" class="form-control form-control-sm" id="date-range" placeholder="Pilih Periode">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" type="button" onclick="updateCharts()">
                <i class="bi bi-sync"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="card-body">
        <!-- Filter Controls -->
        <div class="row mb-4">
          <div class="col-md-3">
            <select class="form-control form-control-sm" id="account-filter">
              <option value="">Semua Akun</option>
              @foreach($accounts as $account)
                <option value="{{ $account->id }}">{{ $account->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-control form-control-sm" id="chart-type">
              <option value="line">Line Chart</option>
              <option value="bar">Bar Chart</option>
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-control form-control-sm" id="time-group">
              <option value="day">Harian</option>
              <option value="week">Mingguan</option>
              <option value="month" selected>Bulanan</option>
            </select>
          </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4" id="summary-cards">
          <!-- Will be populated by JavaScript -->
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
          <div class="col-md-8">
            <div class="card">
              <div class="card-header">
                <h6 class="card-title mb-0">Trend Pendapatan vs Pengeluaran</h6>
              </div>
              <div class="card-body">
                <canvas id="incomeExpenseChart" height="250"></canvas>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <div class="card-header">
                <h6 class="card-title mb-0">Saldo per Akun</h6>
              </div>
              <div class="card-body">
                <canvas id="accountBalanceChart" height="250"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h6 class="card-title mb-0">Pengeluaran per Kategori</h6>
              </div>
              <div class="card-body">
                <canvas id="expenseCategoryChart" height="250"></canvas>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h6 class="card-title mb-0">Anggaran vs Realisasi</h6>
              </div>
              <div class="card-body">
                <canvas id="budgetChart" height="250"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row 3 -->
        <div class="row">
          <div class="col-md-12">
            <div class="card">
              <div class="card-header">
                <h6 class="card-title mb-0">Aktivitas Transaksi per Hari</h6>
              </div>
              <div class="card-body">
                <canvas id="transactionActivityChart" height="150"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
    let charts = {};
    let reportData = {};

    $(document).ready(function() {
        // Initialize date range picker
        $('#date-range').daterangepicker({
            locale: {
                format: 'DD/MM/YYYY',
                separator: ' - ',
                applyLabel: 'Terapkan',
                cancelLabel: 'Batal',
                customRangeLabel: 'Kustom',
                daysOfWeek: ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
            },
            startDate: moment().startOf('month'),
            endDate: moment().endOf('month'),
            ranges: {
                'Hari Ini': [moment(), moment()],
                'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
                '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
                'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), 
                              moment().subtract(1, 'month').endOf('month')]
            }
        });

        // Load initial data
        updateCharts();
    });

    async function updateCharts() {
        const dates = $('#date-range').val().split(' - ');
        const filters = {
            start_date: moment(dates[0], 'DD/MM/YYYY').format('YYYY-MM-DD'),
            end_date: moment(dates[1], 'DD/MM/YYYY').format('YYYY-MM-DD'),
            account_id: $('#account-filter').val(),
            group_by: $('#time-group').val()
        };

        try {
            const response = await fetch(`{{ config('app.url') }}/api/reports/dashboard-summary?${new URLSearchParams(filters)}`);
            const result = await response.json();
            
            if (result.success) {
                reportData = result.data;
                renderSummaryCards(reportData.financial_summary);
                renderCharts(reportData);
            }
        } catch (error) {
            console.error('Error loading report data:', error);
        }
    }

    function renderSummaryCards(summary) {
        const cardsHtml = `
            <div class="col-md-3">
                <div class="summary-card summary-income">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase">Pendapatan</h6>
                            <h3 class="mb-0">${formatCurrency(summary.total_income)}</h3>
                            <small>${summary.income_count} transaksi</small>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-arrow-up"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card summary-expense">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase">Pengeluaran</h6>
                            <h3 class="mb-0">${formatCurrency(summary.total_expense)}</h3>
                            <small>${summary.expense_count} transaksi</small>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-arrow-down"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card summary-balance">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase">Saldo Bersih</h6>
                            <h3 class="mb-0">${formatCurrency(summary.net_flow)}</h3>
                            <small>Arus Kas</small>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-balance-scale"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card summary-transfer">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase">Transfer</h6>
                            <h3 class="mb-0">${formatCurrency(summary.total_transfer)}</h3>
                            <small>Total Transfer</small>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-exchange-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#summary-cards').html(cardsHtml);
    }

    function renderCharts(data) {
        // Destroy existing charts
        Object.values(charts).forEach(chart => chart.destroy());
        
        // Income Expense Trend Chart
        const chartType = $('#chart-type').val();
        charts.incomeExpense = createLineChart('incomeExpenseChart', 
            data.income_expense_trend, chartType);
        
        // Account Balance Chart
        charts.accountBalance = createDoughnutChart('accountBalanceChart', 
            data.account_analysis);
        
        // Expense Category Chart
        charts.expenseCategory = createPieChart('expenseCategoryChart', 
            data.category_analysis);
        
        // Budget Chart
        charts.budget = createBarChart('budgetChart', 
            data.budget_analysis);
    }

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
                    },
                    tooltip: {
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
                plugins: {
                    legend: {
                        position: 'right',
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

    function createPieChart(canvasId, chartData) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        return new Chart(ctx, {
            type: 'pie',
            data: {
                labels: chartData.labels,
                datasets: chartData.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    }

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
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }
</script>
@endpush

@push('styles')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .summary-card {
        border-radius: 10px;
        padding: 15px;
        color: white;
        margin-bottom: 15px;
    }
    .summary-income { background: linear-gradient(135deg, #10b981, #059669); }
    .summary-expense { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .summary-balance { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .summary-transfer { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .chart-container { position: relative; }
</style>
@endpush