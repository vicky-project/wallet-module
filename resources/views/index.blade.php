@extends('wallet::layouts.app')

@section('content')
<h1 class="page-title">Dashboard Keuangan</h1>
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
<div class="row mb-4">
  <!-- Recent Transactions -->
  <div class="col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Transaksi Terbaru</h5>
        <a href="#" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
      </div>
      <div class="card-body p-0">
        <div class="transaction-item">
          <div class="transaction-icon bg-income">
            <i class="bi bi-arrow-up-circle text-income"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0">Gaji Bulanan</h6>
            <small class="text-muted">12 April 2023 • Gaji</small>
          </div>
          <div class="text-income">
            +Rp 5.000.000
          </div>
        </div>
                            
        <div class="transaction-item">
          <div class="transaction-icon bg-expense">
            <i class="bi bi-cart-check text-expense"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0">Belanja Bulanan</h6>
            <small class="text-muted">10 April 2023 • Belanja</small>
          </div>
          <div class="text-expense">
            -Rp 1.250.000
          </div>
        </div>
                            
        <div class="transaction-item">
          <div class="transaction-icon bg-expense">
            <i class="bi bi-lightning-charge text-expense"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0">Bayar Listrik</h6>
            <small class="text-muted">8 April 2023 • Utilitas</small>
          </div>
          <div class="text-expense">
            -Rp 450.000
          </div>
        </div>
                            
        <div class="transaction-item">
          <div class="transaction-icon bg-income">
            <i class="bi bi-cash-coin text-income"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0">Freelance Project</h6>
            <small class="text-muted">5 April 2023 • Freelance</small>
          </div>
          <div class="text-income">
            +Rp 2.500.000
          </div>
        </div>
                            
        <div class="transaction-item">
          <div class="transaction-icon bg-expense">
            <i class="bi bi-train-front text-expense"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0">Transportasi</h6>
            <small class="text-muted">3 April 2023 • Transportasi</small>
          </div>
          <div class="text-expense">
            -Rp 320.000
          </div>
        </div>
      </div>
    </div>
  </div>
                
  <div class="col-lg-4 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Ringkasan Anggaran</h5>
        <a href="#" class="btn btn-sm btn-outline-primary">Atur Anggaran</a>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span>Makanan & Minuman</span>
            <span>Rp 850.000 / Rp 1.000.000</span>
          </div>
          <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-warning" role="progressbar" style="width: 85%"></div>
          </div>
        </div>
                            
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span>Transportasi</span>
            <span>Rp 320.000 / Rp 500.000</span>
          </div>
          <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: 64%"></div>
          </div>
        </div>
                            
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span>Hiburan</span>
            <span>Rp 450.000 / Rp 400.000</span>
          </div>
          <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-danger" role="progressbar" style="width: 113%"></div>
          </div>
        </div>
                            
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span>Belanja</span>
            <span>Rp 1.250.000 / Rp 1.500.000</span>
          </div>
          <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-info" role="progressbar" style="width: 83%"></div>
          </div>
        </div>
                            
        <div class="alert alert-warning mt-4">
          <small>
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Perhatian:</strong> Anggaran untuk Hiburan telah melebihi batas. Pertimbangkan untuk mengurangi pengeluaran di kategori ini.
          </small>
        </div>
                            
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