@extends('wallet::layouts.app')

@section('content')
<!-- Quick Stats -->
<div class="row mb-4">
  <div class="col-md-3 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="card-icon bg-income">
            <i class="bi bi-arrow-up-circle text-income"></i>
          </div>
          <div class="ms-3">
            <h6 class="card-subtitle mb-1">Pemasukan Bulan Ini</h6>
            <h3 class="card-title mb-0 text-income">Rp 8.250.000</h3>
            <small class="text-muted">+12% dari bulan lalu</small>
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
            <h3 class="card-title mb-0 text-expense">Rp 5.120.000</h3>
            <small class="text-muted">-5% dari bulan lalu</small>
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
            <h3 class="card-title mb-0">Rp 3.130.000</h3>
            <small class="text-muted">+25% dari bulan lalu</small>
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
            <h3 class="card-title mb-0">78%</h3>
            <small class="text-muted">Rp 3.9jt dari target 5jt</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection