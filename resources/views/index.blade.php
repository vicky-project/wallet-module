@extends('wallet::layouts.app')

@section('title', 'Dashboard - ' . config('app.name', 'Vicky Server'))

@use('Modules\Wallet\Enums\TransactionType')

@push('styles')
<style>
    /* Custom dashboard styles */
    .stat-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
    }
    
    .stat-card.income {
        border-left-color: #10b981;
    }
    
    .stat-card.expense {
        border-left-color: #ef4444;
    }
    
    .stat-card.balance {
        border-left-color: #3b82f6;
    }
    
    .stat-card.budget {
        border-left-color: #f59e0b;
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
    
    .progress-thin {
        height: 6px;
        border-radius: 3px;
    }
    
    .category-item {
        transition: all 0.2s ease;
        border-radius: 8px;
        padding: 10px 15px;
        margin-bottom: 8px;
    }
    
    .category-item:hover {
        background-color: rgba(0, 0, 0, 0.03);
        transform: translateX(5px);
    }
    
    body[data-bs-theme="dark"] .category-item:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    
    .account-card {
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .account-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15) !important;
    }
    
    .account-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }
    
    .account-card.asset::before {
        background-color: #10b981;
    }
    
    .account-card.liability::before {
        background-color: #ef4444;
    }
    
    .transaction-badge {
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 4px;
    }
    
    .alert-card {
        border-left: 4px solid #f59e0b;
        background-color: rgba(245, 158, 11, 0.05);
    }
    
    body[data-bs-theme="dark"] .alert-card {
        background-color: rgba(245, 158, 11, 0.1);
    }
    
    .recurring-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 12px;
        background-color: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    .floating-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .chart-container {
            height: 250px;
        }
        
        .stat-card .fs-4 {
            font-size: 1.2rem !important;
        }
        
        .stat-card .fs-5 {
            font-size: 1rem !important;
        }
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="page-title mb-1">Dashboard Keuangan</h1>
    <p class="text-muted mb-0">Ringkasan keuangan Anda {{ now()->format('d M Y') }}</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
      <i class="bi bi-download"></i> Ekspor
    </button>
    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" id="refreshDashboard">
      <i class="bi bi-arrow-clockwise"></i> Refresh
    </button>
  </div>
</div>

<!-- Quick Stats Row -->
<div class="row g-3 mb-4">
  <!-- Total Balance -->
  <div class="col-xl-3 col-lg-6 col-md-6">
    <div class="card stat-card balance h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="text-muted small">Total Saldo</span>
            <h3 class="mt-1 mb-0 fw-bold currency">{{ $dashboardData['total_balance'] }}</h3>
          </div>
          <div class="stat-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-wallet"></i>
          </div>
        </div>
        <div class="d-flex align-items-center mt-3">
          @if($dashboardData['balance_trend'] >= 0)
            <span class="badge bg-success bg-opacity-10 text-success me-2">
              <i class="bi bi-arrow-up-right me-1"></i>
              {{ number_format($dashboardData['balance_trend'], 1) }}%
            </span>
          @else
            <span class="badge bg-danger bg-opacity-10 text-danger me-2">
              <i class="bi bi-arrow-down-right me-1"></i>
              {{ number_format(abs($dashboardData['balance_trend']), 1) }}%
            </span>
          @endif
          <span class="text-muted small">dari bulan lalu</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Monthly Income -->
  <div class="col-xl-3 col-lg-6 col-md-6">
    <div class="card stat-card income h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="text-muted small">Pemasukan Bulan Ini</span>
            <h3 class="mt-1 mb-0 fw-bold currency">{{ $dashboardData['monthly_income'] }}</h3>
          </div>
          <div class="stat-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-arrow-down-left"></i>
          </div>
        </div>
        <div class="d-flex align-items-center mt-3">
          <span class="badge bg-success bg-opacity-10 text-success me-2">
            {{ $dashboardData['income_count'] }} transaksi
          </span>
          <span class="text-muted small">sampai hari ini</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Monthly Expense -->
  <div class="col-xl-3 col-lg-6 col-md-6">
    <div class="card stat-card expense h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="text-muted small">Pengeluaran Bulan Ini</span>
            <h3 class="mt-1 mb-0 fw-bold currency">{{ $dashboardData['monthly_expense'] }}</h3>
          </div>
          <div class="stat-icon bg-danger bg-opacity-10 text-danger">
            <i class="bi bi-arrow-up-right"></i>
          </div>
        </div>
        <div class="d-flex align-items-center mt-3">
          <span class="badge bg-danger bg-opacity-10 text-danger me-2">
            {{ $dashboardData['expense_count'] }} transaksi
          </span>
          <span class="text-muted small">sampai hari ini</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Budget Usage -->
  <div class="col-xl-3 col-lg-6 col-md-6">
    <div class="card stat-card budget h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="text-muted small">Penggunaan Budget</span>
            <h3 class="mt-1 mb-0 fw-bold">{{ number_format($dashboardData['budget_usage_percentage'], 1) }}%</h3>
          </div>
          <div class="stat-icon bg-warning bg-opacity-10 text-warning">
            <i class="bi bi-wallet-fill"></i>
          </div>
        </div>
        <div class="mt-3">
          <div class="progress progress-thin mb-1">
            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ min($dashboardData['budget_usage_percentage'], 100) }}%" aria-valuenow="{{ $dashboardData['budget_usage_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
            </div>
          </div>
          <span class="text-muted small">{{ count($dashboardData['budget_warnings']) }} peringatan budget</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Charts and Main Content -->
<div class="row g-4">
  <!-- Monthly Chart -->
  <div class="col-md-8 mb-2">
    <div class="card h-100">
      <div class="card-header bg-transparent border-0 pb-2">
        <h5 class="card-title mb-0">Grafik Keuangan Bulan {{ now()->format('F') }}</h5>
        <p class="text-muted small mb-0">Pemasukan vs Pengeluaran Harian</p>
      </div>
      <div class="card-body pt-0">
        <div class="chart-container">
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Account Summary -->
  <div class="col-md-4 mb-2">
    <div class="card h-100">
      <div class="card-header bg-transparent border-0 pb-2">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="card-title mb-0">Akun Saya</h5>
            <p class="text-muted small mb-0">{{ $dashboardData['account_stats']['total'] }} akun aktif</p>
          </div>
          <a href="{{ route('apps.accounts.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-lg"></i> Baru
          </a>
        </div>
      </div>
      <div class="card-body pt-0">
        @if(count($dashboardData['accounts']) > 0)
          @foreach($dashboardData['accounts']->take(5) as $account)
            <div class="account-card {{ $account['type'] == 'asset' ? 'asset' : 'liability' }} bg-body">
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <div class="rounded-circle p-2 me-3" style="background-color: {{ $account['color'] }}; color: white;">
                    <i class="{{ $account['icon'] }}"></i>
                  </div>
                  <div>
                    <h6 class="mb-0">{{ $account['name'] }}</h6>
                    <small class="text-muted">{{ ucfirst($account['type']->value) }}</small>
                  </div>
                </div>
                <div class="text-end">
                  <div class="fw-bold currency">{{ $account['balance'] }}</div>
                  <small class="{{ $account['net_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                    @if($account['net_flow'] >= 0)
                      <i class="bi bi-arrow-up-right"></i>
                    @else
                      <i class="bi bi-arrow-down-right"></i>
                    @endif
                    <span class="currency">{{ abs($account['net_flow']) }}</span>
                  </small>
                </div>
              </div>
              @if($account['is_default'])
                <span class="badge bg-primary bg-opacity-10 text-primary mt-2">
                  <i class="bi bi-star-fill me-1"></i> Utama
                </span>
              @endif
            </div>
          @endforeach
                        
          @if($dashboardData['account_stats']['total'] > 5)
            <div class="text-center mt-3">
              <a href="{{ route('apps.accounts.index') }}" class="btn btn-sm btn-outline-secondary">
                Lihat Semua Akun ({{ $dashboardData['account_stats']['total'] }})
              </a>
            </div>
          @endif
        @else
          <div class="empty-state">
            <i class="bi bi-wallet text-muted"></i>
            <p class="mt-3 mb-2">Belum ada akun</p>
            <a href="{{ route('apps.accounts.create') }}" class="btn btn-sm btn-primary">
              <i class="bi bi-plus-lg me-1"></i> Tambah Akun Pertama
            </a>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Second Row: Recent Transactions and Budgets -->
<div class="row g-4 mt-2">
  <!-- Recent Transactions -->
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header bg-transparent border-0 pb-2">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="card-title mb-0">Transaksi Terbaru</h5>
            <p class="text-muted small mb-0">
              {{ $dashboardData['transaction_stats']['total_this_month'] }} transaksi bulan ini
            </p>
          </div>
          <a href="{{ route('apps.transactions.index') }}" class="btn btn-sm btn-outline-primary">
            Lihat Semua
          </a>
        </div>
      </div>
      <div class="card-body pt-0">
        @forelse($dashboardData['recent_transactions'] as $transaction)
          <div class="d-flex justify-content-between align-items-center mb-2 bg-transparent">
            <div class="d-flex align-items-center">
              <div class="transaction-icon {{ $transaction['type'] == TransactionType::INCOME->value ? 'bg-income' : 'bg-expense' }} me-3">
                <i class="{{ $transaction['category_icon'] ?? 'bi-arrow-left-right' }} {{ $transaction['type'] == TransactionType::INCOME->value ? 'text-success' : 'text-danger' }}"></i>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">{{ $transaction['description'] }}</h6>
                <div class="d-flex align-items-center mt-1">
                  <span class="badge transaction-badge text-bg-light text-dark me-2">
                    {{ $transaction['category_name'] }}
                  </span>
                  <small class="text-muted">{{ $transaction['account_name'] }}</small>
                </div>
              </div>
            </div>
            <div class="text-end">
              <div class="fw-bold {{ $transaction['type'] == TransactionType::INCOME->value ? 'text-success' : 'text-danger' }} currency">
                {{ $transaction['amount'] }}
              </div>
              <small class="text-muted">
                {{ \Carbon\Carbon::parse($transaction['transaction_date'])->format('d M H:i') }}
              </small>
            </div>
          </div>
        @empty
          <div class="empty-state py-4">
            <i class="bi bi-receipt text-muted"></i>
            <p class="mt-3 mb-2">Belum ada transaksi</p>
            <a href="{{ route('apps.transactions.create') }}" class="btn btn-sm btn-primary">
              <i class="bi bi-plus-lg me-1"></i> Tambah Transaksi
            </a>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Budget Warnings and Upcoming Recurring -->
  <div class="col-xl-6">
    <div class="row g-4 h-100">
      <!-- Budget Warnings -->
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header bg-transparent border-0 pb-2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title mb-0">Peringatan Budget</h5>
                <p class="text-muted small mb-0">
                  {{ $dashboardData['budget_stats']['over_budget'] ?? 0 }} melebihi budget
                </p>
              </div>
              @if(count($dashboardData['budget_warnings']) > 0)
                <span class="badge bg-danger floating-badge">
                  {{ count($dashboardData['budget_warnings']) }}
                </span>
              @endif
            </div>
          </div>
          <div class="card-body pt-0">
            @if(count($dashboardData['budget_warnings']) > 0)
              @foreach(array_slice($dashboardData['budget_warnings'], 0, 3) as $warning)
                <div class="alert-card p-3 mb-3 rounded">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">{{ $warning['category_name'] }}</h6>
                      <p class="mb-0 small">{{ $warning['message'] }}</p>
                    </div>
                    <div class="text-end">
                      <div class="fw-bold text-warning">
                        {{ number_format($warning['usage_percentage'], 0) }}%
                      </div>
                      <small class="text-muted currency">{{ $warning['spent'] }}</small> /
                      <small class="text-muted currency">{{ $warning['amount'] }}</small>
                    </div>
                  </div>
                  <div class="progress progress-thin mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ min($warning['usage_percentage'], 100) }}%" aria-valuenow="{{ $warning['usage_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                    </div>
                  </div>
                </div>
              @endforeach
                                
              @if(count($dashboardData['budget_warnings']) > 3)
                <div class="text-center">
                  <a href="{{ route('apps.budgets.index') }}" class="btn btn-sm btn-outline-warning">
                    Lihat {{ count($dashboardData['budget_warnings']) - 3 }} peringatan lainnya
                  </a>
                </div>
              @endif
            @else
              <div class="empty-state py-4">
                <i class="bi bi-check-circle text-success"></i>
                <p class="mt-3 mb-2">Semua budget berjalan baik</p>
                <small class="text-muted">Tidak ada peringatan budget</small>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Upcoming Recurring Transactions -->
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header bg-transparent border-0 pb-2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title mb-0">Transaksi Rutin Mendatang</h5>
                <p class="text-muted small mb-0">7 hari ke depan</p>
              </div>
              <a href="{{ route('apps.recurrings.index') }}" class="btn btn-sm btn-outline-primary">
                Kelola
              </a>
            </div>
          </div>
          <div class="card-body pt-0">
            @forelse($dashboardData['upcoming_recurring'] as $recurring)
              <div class="transaction-item position-relative">
                @if($recurring['is_today'])
                  <span class="badge bg-success bg-opacity-10 text-success mb-1">Hari Ini</span>
                @endif
                                        
                <div class="d-flex align-items-center">
                  <div class="transaction-icon bg-info bg-opacity-10 text-info me-3">
                    <i class="{{ $recurring['category_icon'] }}"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-0">{{ $recurring['description'] }}</h6>
                    <div class="d-flex align-items-center mt-1">
                      <span class="badge transaction-badge bg-light text-dark me-2">
                        {{ $recurring['frequency'] }}
                      </span>
                      <small class="text-muted">{{ $recurring['account_name'] }}</small>
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold {{ $recurring['recurring']->type == 'income' ? 'text-success' : 'text-danger' }} currency">
                      {{ $recurring['amount'] }}
                      </div>
                    <small class="text-muted">
                      {{ $recurring['next_date'] }}
                      @if($recurring['days_until'])
                        <br><span class="text-info">({{ $recurring['days_until'] }})</span>
                      @endif
                    </small>
                  </div>
                </div>
              </div>
            @empty
              <div class="empty-state py-4">
                <i class="bi bi-calendar-event text-muted"></i>
                <p class="mt-3 mb-2">Tidak ada transaksi rutin mendatang</p>
                <a href="{{ route('apps.recurrings.create') }}" class="btn btn-sm btn-primary">
                  <i class="bi bi-plus-lg me-1"></i> Buat Transaksi Rutin
                </a>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Third Row: Category Analysis and Recent Activity -->
<div class="row g-4 mt-2">
  <!-- Category Analysis -->
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header bg-transparent border-0 pb-2">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="card-title mb-0">Analisis Kategori</h5>
            <p class="text-muted small mb-0">Pengeluaran berdasarkan kategori</p>
          </div>
          <a href="{{ route('apps.categories.index') }}" class="btn btn-sm btn-outline-primary">
            Kelola
          </a>
        </div>
      </div>
      <div class="card-body pt-0">
        @forelse($dashboardData['category_analysis'] as $category)
          <div class="category-item">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <div class="rounded-circle p-2 me-3 bg-light">
                  <i class="bi {{ $category['icon'] }} text-primary"></i>
                </div>
                <div>
                  <h6 class="mb-0">{{ $category['name'] }}</h6>
                  @if($category['budget_limit'] > 0)
                    <small class="text-muted currency">
                      {{ $category['monthly_total'] }} / {{ $category['budget_limit'] }}
                    </small>
                  @endif
                </div>
              </div>
              <div class="text-end">
                <div class="fw-bold currency">{{ $category['monthly_total'] }}</div>
                @if($category['budget_limit'] > 0)
                  <div class="d-flex align-items-center mt-1">
                    <div class="progress progress-thin flex-grow-1 me-2" style="width: 80px;">
                      <div class="progress-bar {{ $category['has_budget_exceeded'] ? 'bg-danger' : 'bg-success' }}" role="progressbar" style="width: {{ min($category['budget_usage_percentage'], 100) }}%" aria-valuenow="{{ $category['budget_usage_percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                      </div>
                    </div>
                    <span class="small {{ $category['has_budget_exceeded'] ? 'text-danger' : 'text-success' }}">
                      {{ number_format($category['budget_usage_percentage'], 0) }}%
                    </span>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @empty
          <div class="empty-state py-4">
            <i class="bi bi-chart-pie text-muted"></i>
            <p class="mt-3 mb-2">Belum ada kategori dengan pengeluaran</p>
            <a href="{{ route('apps.categories.create') }}" class="btn btn-sm btn-primary">
              <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
            </a>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Recent Activity and Account Alerts -->
  <div class="col-xl-6">
    <div class="row g-4 h-100">
      <!-- Recent Activity -->
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header bg-transparent border-0 pb-2">
            <h5 class="card-title mb-0">Aktivitas Terbaru</h5>
          </div>
          <div class="card-body pt-0">
            @if(count($dashboardData['recent_activity']) > 0)
              @foreach($dashboardData['recent_activity'] as $activity)
                <div class="transaction-item">
                  <div class="d-flex align-items-center">
                    <div class="transaction-icon bg-light text-dark me-3">
                      <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-0">{{ $activity->description }}</h6>
                      <small class="text-muted">
                        {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                      </small>
                    </div>
                  </div>
                </div>
              @endforeach
            @else
              <div class="empty-state py-4">
                <i class="bi bi-activity text-muted"></i>
                <p class="mt-3 mb-2">Belum ada aktivitas</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Account Alerts -->
      <div class="col-12">
        <div class="card h-100">
          <div class="card-header bg-transparent border-0 pb-2">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Peringatan Akun</h5>
              @if(count($dashboardData['account_alerts']) > 0)
                <span class="badge bg-danger floating-badge">
                  {{ count($dashboardData['account_alerts']) }}
                </span>
              @endif
            </div>
          </div>
          <div class="card-body pt-0">
            @forelse($dashboardData['account_alerts'] as $alert)
              <div class="alert alert-warning alert-dismissible fade show mb-2" role="alert">
                <div class="d-flex align-items-center">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  <div class="flex-grow-1">
                    <strong>{{ $alert['account_name'] }}</strong> - {{ $alert['message'] }}
                    <div class="mt-1">
                      <small>Saldo: <span class="currency">{{ $alert['balance'] }}</span></small>
                    </div>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              </div>
            @empty
              <div class="empty-state py-4">
                <i class="bi bi-shield-check text-success"></i>
                <p class="mt-3 mb-2">Semua akun dalam kondisi baik</p>
                <small class="text-muted">Tidak ada peringatan akun</small>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format semua currency
    document.querySelectorAll('.currency').forEach(element => {
        const value = element.textContent;
        if (!isNaN(value)) {
            element.textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        }
    });

    // Refresh dashboard button
    const refreshBtn = document.getElementById('refreshDashboard');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const btn = this;
            const originalHtml = btn.innerHTML;
            
            btn.innerHTML = '<i class="bi bi-arrow-clockwise animate-spin"></i> Memuat...';
            btn.disabled = true;
            
            setTimeout(() => {
                window.location.reload();
            }, 500);
        });
    }

    // Monthly Chart
    const chartData = @json($dashboardData['monthly_chart']);
    
    if (chartData && chartData.length > 0) {
        const days = chartData.map(item => item.day);
        const income = chartData.map(item => item.income || 0);
        const expense = chartData.map(item => item.expense || 0);
        
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: days,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: income,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    },
                    {
                        label: 'Pengeluaran',
                        data: expense,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: '#ef4444',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
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
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR',
                                        minimumFractionDigits: 0
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            callback: function(value, index) {
                                return 'Hari ' + this.getLabelForValue(value);
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return 'Rp' + (value / 1000000).toFixed(1) + 'Jt';
                                }
                                if (value >= 1000) {
                                    return 'Rp' + (value / 1000).toFixed(0) + 'rb';
                                }
                                return 'Rp' + value;
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    } else {
        // Jika tidak ada data chart
        const chartContainer = document.querySelector('.chart-container');
        if (chartContainer) {
            chartContainer.innerHTML = `
                <div class="empty-state h-100 d-flex flex-column justify-content-center align-items-center">
                    <i class="bi bi-bar-chart text-muted mb-3" style="font-size: 3rem;"></i>
                    <p class="text-muted">Belum ada data transaksi bulan ini</p>
                </div>
            `;
        }
    }
});

// Add animate-spin class for refresh button
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
`;
document.head.appendChild(style);
</script>
@endpush