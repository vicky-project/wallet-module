@extends('wallet::layouts.app')

@use('Modules\Wallet\Enums\TransactionType')

@section('content')
@include('wallet::partials.fab')
<h1 class="page-title">Dashboard Keuangan</h1>
<!-- Quick Stats -->
<div class="row mb-4" id="quickStats">
  <div class="col-md-3 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="card-icon bg-income">
            <i class="bi bi-arrow-up-circle text-income"></i>
          </div>
          <div class="ms-3">
            <h6 class="card-subtitle mb-1">Pemasukan Bulan Ini</h6>
            <h3 class="card-title mb-0 text-income">{{ $stats["monthly_income"]["formatted"] }}</h3>
            <small class="text-muted">{{ $stats["monthly_income"]["change_formatted"] }} dari bulan lalu</small>
          </div>
        </div>
      </div>
    </div>
  </div>
                
  <div class="col-md-3 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="card-icon bg-expense">
            <i class="bi bi-arrow-down-circle text-expense"></i>
          </div>
          <div class="ms-3">
            <h6 class="card-subtitle mb-1">Pengeluaran Bulan Ini</h6>
            <h3 class="card-title mb-0 text-expense">{{ $stats["monthly_expense"]["formatted"] }}</h3>
            <small class="text-muted">{{ $stats["monthly_expense"]["change_formatted"] }} dari bulan lalu</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="card-icon" style="background-color: rgba(59, 130, 246, 0.1);">
            <i class="bi bi-graph-up" style="color: #3b82f6;"></i>
          </div>
          <div class="ms-3">
            <h6 class="card-subtitle mb-1">Saldo Bersih</h6>
            <h3 class="card-title mb-0">{{ $stats["net_balance"]["formatted"] }}</h3>
            <small class="{{ $stats['net_balance']['is_positive'] ? 'text-success' : 'text-danger'}}">{{$stats["net_balance"]["change_formatted"] }} dari bulan lalu</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="card-icon" style="background-color: rgba(245, 158, 11, 0.1);">
            <i class="bi bi-piggy-bank" style="color: #f59e0b;"></i>
          </div>
          <div class="ms-3">
            <h6 class="card-subtitle mb-1">Tabungan Tercapai</h6>
            <h3 class="card-title mb-0">{{ $stats["savings_progress"]["formatted"] }}</h3>
            <small class="text-muted">{{ $stats["savings_progress"]["completed_goals"] }} dari target {{ $stats["savings_progress"]["goals_count"] }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="row mb-4">
  <!-- Recent Transactions -->
  <div class="col-lg-8 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Transaksi Terbaru</h5>
        <a href="{{ route('apps.transactions.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
      </div>
      <div class="card-body p-0" id="recentTransaction">
        @if($recentTransactions->isEmpty())
        <div class="text-center py-5">
          <i class="bi bi-receipt display-4 text-muted"></i>
          <p class="text-muted mt-3">Belum ada transaksi</p>
          <a href="{{ route('apps.transactions.create') }}" class="btn btn-primary mt-2" role="button">Buat transaksi.</a>
        </div>
        @else
          @foreach ($recentTransactions as $transaction)
          @php
          $isIncome = $transaction->type == TransactionType::INCOME
          @endphp
            <div class="transaction-item">
              <div class="transaction-icon {{ $isIncome ? 'bg-income' : 'bg-expense' }}">
                <i class="bi {{ $isIncome ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle' }} {{ $isIncome ? 'text-income' : 'text-expense' }}"></i>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">{{ $transaction->title }}</h6>
                <small class="text-muted">{{ $transaction->transaction_date }} • {{ $transaction->category->name }}</small>
              </div>
              <div class="{{ $isIncome ? 'text-income' : 'text-expense'}} fw-bold">
                {{ $isIncome ? '+' : '-'}}{{ number_format($transaction->amount->getAmount()->toInt(), 0, ',', '.') }}
              </div>
            </div>
          @endforeach
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Ringkasan Anggaran</h5>
        <a href="{{ route('apps.budgets.index') }}" class="btn btn-sm btn-outline-primary">Atur Anggaran</a>
      </div>
      <div class="card-body" id="budgetSummary">
        @if($budgetSummary["budgets"]->isEmpty())
          <div class="text-center py-4">
            <i class="bi bi-pie-chart display-4 text-muted"></i>
            <p class="text-muted mt-3">Belum ada anggaran.</p>
            <a href="{{ route('apps.budgets.create') }}" class="btn btn-primary btn-sm mt-2" role="button">Buat Anggaran</a>
          </div>
        @else
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-medium">Total Anggaran</span>
            <span class="text-muted">{{ $budgetSummary["total_budget"] }}</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-medium">Total Terpakai</span>
            <span class="{{ $budgetSummary['budget_usage_percentage'] >= 100 ? 'text-danger' : 'text-success' }}">{{ $budgetSummary["total_spent"] }}</span>
          </div>
          <div class="progress mb-4" style="height: 10px;">
            <div class="progress-bar {{ $budgetSummary['budget_usage_percentage'] >= 100 ? 'bg-danger' : ($budgetSummary['budget_usage_percentage'] >= 80 ? 'bg-warning' : 'bg-success') }}" role="progressbar" style="width: {{ $budgetSummary['budget_usage_percentage'] }}%"></div>
          </div>
        </div>
        
        @foreach($budgetSummary["budgets"] as $budget)
          @php
          $progressClass = "bg-success";
          if($budget->percentage >= 100) {
            $progressClass = "bg-danger";
          } elseif($budget->percentage >= 80) {
            $progressClass = "bg-warning";
          }
          @endphp
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span>{{ $budget->category->name }}</span>
              <span>{{ $budget->spent }} / {{ $budget->amount }}</span>
            </div>
            <div class="progress {{ $progressClass }}" style="height: 10px;">
              <div class="progress-bar bg-success" role="progressbar" style="width: {{ $budget->percentage }}%" title="{{ $budget->percentage }}% terpakai"></div>
            </div>
          </div>
          @if($budget->isExceeded)
            <small class="text-danger mt-1 d-block">
              <i class="bi bi-exclamation-triangle"></i>
              Anggaran terlampaui.
            </small>
          @endif
        @endforeach
        @php
        $isExceededBudgets = $budgets->filter(fn($exceeded) => $exceeded->isExceeded);
        @endphp
        @if($isExceededBudgets->count() > 0)
          <div class="alert alert-warning mt-4">
            <small>
              <i class="bi bi-exclamation-triangle me-2"></i>
              <strong>Perhatian:</strong> {{ $isExceededBudgets->count() }} anggaran telah melebihi batas. Pertimbangkan untuk mengurangi pengeluaran di kategori ini.
            </small>
          </div>
        @endif
        @endif
                            
                            
        <div class="alert alert-info mt-3">
          <small>
            <i class="bi bi-lightbulb me-2"></i>
            <strong>Tips:</strong> Gunakan tombol <i class="bi bi-plus-lg"></i> di pojok kanan bawah untuk menambahkan transaksi dengan cepat!
          </small>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection