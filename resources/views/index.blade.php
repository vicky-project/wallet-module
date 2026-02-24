@extends('core::layouts.app')

@section('title', 'Keuangan')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Total Saldo -->
    <div class="text-center my-4">
      <small class="text-uppercase" style="color: var(--tg-theme-hint-color);">Total Saldo</small>
      <h1 class="display-1 fw-bold" style="color: var(--tg-theme-text-color);">Rp {{ number_format($dashboardData['total_balance'], 0, ',', '.') }}</h1>
    </div>

    <!-- Widget Stats -->
    <div class="row g-3 mb-4">
      <!-- Akun Widget -->
      <div class="col-md-4">
        <a href="{{ route('apps.accounts.index') }}" class="text-decoration-none">
          <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
            <div class="card-body p-3">
              <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle p-2 me-2" style="background-color: rgba(64, 167, 227, 0.1); color: #40a7e3;">
                  <i class="bi bi-wallet2"></i>
                </div>
                <h6 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">Akun</h6>
              </div>
              <p class="fw-bold mb-1" style="color: var(--tg-theme-text-color); font-size: 1.2rem;">{{ $dashboardData['account_stats']['total'] }}</p>
              @if($dashboardData['account_stats']['active'])
                <small style="color: var(--tg-theme-hint-color);">Active: {{ $dashboardData['account_stats']['active'] }}</small>
              @else
                <small style="color: var(--tg-theme-hint-color);">Belum ada akun aktif</small>
              @endif
            </div>
          </div>
        </a>
      </div>

      <!-- Kategori Widget -->
      <div class="col-md-4">
        <a href="{{ route('apps.categories.index') }}" class="text-decoration-none">
          <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
            <div class="card-body p-3">
              <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle p-2 me-2" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
                  <i class="bi bi-tags"></i>
                </div>
                <h6 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">Kategori</h6>
              </div>
              <p class="fw-bold mb-1" style="color: var(--tg-theme-text-color); font-size: 1.2rem;">{{ $dashboardData['category_stats']['total'] }}</p>
              <small style="color: var(--tg-theme-hint-color);">{{ $dashboardData['category_stats']['expense'] }} pengeluaran, {{ $dashboardData['category_stats']['income'] }} pemasukan</small>
            </div>
          </div>
        </a>
      </div>

      <!-- Anggaran Widget -->
      <div class="col-md-4">
        <a href="{{ route('apps.budgets.index') }}" class="text-decoration-none">
          <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
            <div class="card-body p-3">
              <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle p-2 me-2" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
                  <i class="bi bi-pie-chart"></i>
                </div>
                <h6 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">Anggaran</h6>
              </div>
              <p class="fw-bold mb-1" style="color: var(--tg-theme-text-color); font-size: 1.2rem;">{{ $dashboardData['budget_stats']['total'] }}</p>
              @if($dashboardData['budget_stats']['total'] > 0)
                @php $percentage = $budgetStats['total_amount'] > 0 ? round(($dashboardData['budget_stats']['total_spent'] / $dashboardData['budget_stats']['total_amount']) * 100) : 0; @endphp
                <small style="color: var(--tg-theme-hint-color);">Terpakai {{ $percentage }}%</small>
              @else
                <small style="color: var(--tg-theme-hint-color);">Belum ada anggaran</small>
              @endif
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- Transaksi Terbaru -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">Transaksi Terbaru</h5>
          <a href="{{ route('apps.transactions.index') }}" class="small" style="color: var(--tg-theme-button-color);">Lihat semua</a>
        </div>
        @forelse($dashboardData['recent_transactions'] as $transaction)
          <div class="card border-0 shadow-sm mb-2" style="background-color: var(--tg-theme-secondary-bg-color);">
            <div class="card-body p-3">
              <div class="d-flex align-items-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #6c757d20; color: #6c757d;">
                  <i class="bi {{ $transaction['category_icon'] ?? 'bi-tag' }}"></i>
                </div>
                <div class="flex-grow-1" style="min-width: 0;">
                  <div class="row g-0">
                    <div class="col-8 col-sm-9 col-md-10">
                      <h6 class="mb-0 text-truncate" style="color: var(--tg-theme-text-color);">{{ $transaction['description'] }}</h6>
                      <small class="text-truncate d-block" style="color: var(--tg-theme-hint-color);">{{ $transaction['account_name'] }} • {{ \Carbon\Carbon::parse($transaction['transaction_date'])->format('d M H:i') }}</small>
                    </div>
                    <div class="col-4 col-sm-3 col-md-2 text-end">
                      <span class="fw-bold {{ $transaction['type'] == 'income' ? 'text-success' : 'text-danger' }}">
                        {{ $transaction['type'] == 'income' ? '+' : '-' }} Rp {{ number_format($transaction['amount'], 0, ',', '.') }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="text-center py-4" style="color: var(--tg-theme-hint-color);">
            <i class="bi bi-receipt display-6"></i>
            <p class="mt-2">Belum ada transaksi.</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>

<!-- Floating Action Button (FAB) -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;" id="fabContainer">
  <div class="fab-menu" id="fabMenu">
    <a href="#" class="fab-item" id="fabIncome">
      <i class="bi bi-plus-circle fab-income"></i>
      <span class="fab-label">Tambah Pemasukan</span>
    </a>
    <a href="#" class="fab-item" id="fabExpense">
      <i class="bi bi-dash-circle fab-expense"></i>
      <span class="fab-label">Tambah Pengeluaran</span>
    </a>
    <a href="#" class="fab-item" id="fabReport">
      <i class="bi bi-file-earmark-text fab-report"></i>
      <span class="fab-label">Laporan</span>
    </a>
    <a href="#" class="fab-item" id="fabUpload">
      <i class="bi bi-cloud-upload fab-upload"></i>
      <span class="fab-label">Upload</span>
    </a>
  </div>
  <button class="fab-main" id="fabMain">
    <i class="bi bi-plus-lg" id="fabIcon"></i>
  </button>
</div>
@endsection

@push('scripts')
<script>
    // Elemen FAB
  const fabMain = document.getElementById('fabMain');
  const fabMenu = document.getElementById('fabMenu');
  const fabIcon = document.getElementById('fabIcon');
  const fabIncome = document.getElementById('fabIncome');
  const fabExpense = document.getElementById('fabExpense');
  const fabRecurring = document.getElementById('fabRecurring');
  const fabReport = document.getElementById('fabReport');
  const fabUpload= document.getElementById('fabUpload');
  
  // FAB Toggle Functionality
  function toggleFabMenu() {
    fabMain.classList.toggle('active');
    fabMenu.classList.toggle('active');
                
    if (fabMain.classList.contains('active')) {
      fabIcon.classList.remove('bi-plus-lg');
      fabIcon.classList.add('bi-x');
    } else {
      fabIcon.classList.remove('bi-x');
      fabIcon.classList.add('bi-plus-lg');
    }
  }

  // Toggle FAB Menu
  fabMain.addEventListener('click', function(e) {
    e.stopPropagation();
    toggleFabMenu();
  });

  // Tutup FAB Menu ketika klik di luar
  document.addEventListener('click', function(e) {
    if (!fabMain.contains(e.target) && !fabMenu.contains(e.target)) {
      if (fabMenu.classList.contains('active')) {
        toggleFabMenu();
      }
    }
  });
  
  window.addEventListener('resize', function(){
    if(fabMenu.classList.contains('active')) {
      toggleFabMenu();
    }
  });

  // Tutup FAB Menu ketika klik item menu
  [fabIncome, fabExpense, fabRecurring, fabReport, fabUpload].forEach(item => {
    item.addEventListener('click', function(e) {
      e.stopPropagation();
      const action = this.id.replace('fab', '').toLowerCase();
      console.log(`Aksi FAB: ${action}`);

      // Tutup menu setelah memilih
      setTimeout(() => {
        if (fabMenu.classList.contains('active')) {
          toggleFabMenu();
        }
      }, 300);

      // Simulasi aksi (dalam implementasi nyata akan redirect ke halaman tertentu)
      switch(action) {
        case 'income':
          window.location.href = '{{ route("apps.transactions.create", ["type" => "income"]) }}';
          break;
        case 'expense':
          window.location.href = '{{ route("apps.transactions.create", ["type" => "expense"]) }}';
          break;
        case 'recurring':
          window.location.href = '{{ route("apps.recurrings.index") }}';
          break;
        case 'report':
          window.location.href = '{{ route("apps.reports") }}';
          break;
        case 'upload':
          //alert('Upload feature is coming soon.');
          window.location.href = '{{ route("apps.uploads") }}';
          break;
      }
    });
  });
</script>
@endpush

@push('styles')
<style>
    .content {
        padding-bottom: 80px;
    }
    .card {
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .text-truncate {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }
    
            .fab-main {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--tg-theme-button-color), var(--tg-theme-button-color));
            color: var(--tg-theme-button-text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .fab-main:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.5);
        }
        
        .fab-main.active {
            transform: rotate(45deg);
        }
        
        .fab-menu {
            position: absolute;
            bottom: 100px;
            right: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        
        .fab-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .fab-item {
            display: flex;
            align-items: center;
            background-color: var(--tg-theme-button-color);
            color: var(--tg-theme-button-text-color);
            padding: 12px 20px;
            border-radius: 50px 0 0 50px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            text-decoration: none;
            transform: translateX(10px);
            opacity: 0;
        }
        
        .fab-menu.active .fab-item {
            transform: translateX(0);
            opacity: 1;
        }
        
        .fab-menu.active .fab-item:nth-child(1) {
            transition-delay: 0.05s;
        }
        
        .fab-menu.active .fab-item:nth-child(2) {
            transition-delay: 0.1s;
        }
        
        .fab-menu.active .fab-item:nth-child(3) {
            transition-delay: 0.15s;
        }
        
        .fab-menu.active .fab-item:nth-child(4) {
            transition-delay: 0.2s;
        }
        
        .fab-item:hover {
            transform: translateX(-5px) !important;
        }
        
        .fab-item i {
            font-size: 1.2rem;
            margin-right: 10px;
            width: 24px;
            text-align: center;
        }
        
        .fab-label {
            font-size: 0.9rem;
            font-weight: 500;
            margin-right: 10px;
        }
    
    @media (min-width: 768px) {
      .text-truncate {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: clip !important;
      }
    }
</style>
@endpush
