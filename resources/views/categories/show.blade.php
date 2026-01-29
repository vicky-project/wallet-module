@extends('wallet::layouts.app')

@section('title', 'Detail Kategori - ' . $category->name . ' - ' . config('app.name'))

@use('Modules\Wallet\Enums\CategoryType')
@use('Modules\Wallet\Enums\TransactionType')

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 text-end">
  <div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('apps.categories.index') }}" class="btn btn-outline-secondary me-2">
      <i class="bi bi-arrow-left"></i>
    </a>
    <a href="{{ route('apps.categories.edit', $category) }}" class="btn btn-primary">
      <i class="bi bi-pencil"></i>
    </a>
  </div>
  <div>
    <h1 class="page-title mb-2">
      <i class="bi bi-pie-chart me-2"></i>Detail Kategori
    </h1>
    <p class="text-muted mb-0">Informasi lengkap dan statistik kategori "{{ $category->name }}"</p>
  </div>
</div>

<!-- Category Information -->
<div class="row g-2">
  <!-- Left Column: Category Details -->
  <div class="col-lg-4 col-md-4 mb-3">
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center mb-4">
          <div class="category-icon-large me-3" style="width: 70px; height: 70px; background: {{ $category->type === CategoryType::INCOME ? '#10b981' : '#ef4444' }}; color: white; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
            <i class="bi {{ $category->icon ?? 'bi-tag' }} fs-3"></i>
          </div>
          <div>
            <h4 class="mb-1">{{ $category->name }}</h4>
            <span class="badge {{ $category->type === CategoryType::INCOME ? 'bg-success' : 'bg-danger' }} fs-6">
              <i class="bi {{ $category->type === 'income' ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle' }} me-1"></i>
              {{ $category->type === CategoryType::INCOME ? 'Pemasukan' : 'Pengeluaran' }}
            </span>
          </div>
        </div>

        <div class="category-details">
          <div class="row">
            <div class="col-md-6 mb-3">
              <h6 class="text-muted mb-1">Status</h6>
              <div>
                @if($category->is_active)
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aktif</span>
                @else
                <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Nonaktif</span>
                @endif
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <h6 class="text-muted mb-1">Jumlah Transaksi</h6>
              <h5 class="mb-0">{{ $category->transactions->count() }}</h5>
            </div>
          </div>

          @if($category->description)
          <div class="mb-3">
            <h6 class="text-muted mb-1">Deskripsi</h6>
            <p class="mb-0">{{ $category->description }}</p>
          </div>
          @endif

          @if($category->budget_limit && $category->type === CategoryType::EXPENSE)
          <div class="budget-info p-3 rounded mb-3" style="background: {{ $category->has_budget_exceeded ? '#fee2e2' : '#f0f9ff' }}; border-left: 4px solid {{ $category->has_budget_exceeded ? '#ef4444' : '#3b82f6' }};">
            <h6 class="text-muted mb-2">Informasi Anggaran</h6>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>Batas Anggaran:</span>
              <strong>{{ $category->formatted_budget_limit }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span>Penggunaan:</span>
              <strong class="{{ $category->has_budget_exceeded ? 'text-danger' : '' }}">
                {{ number_format($category->budget_usage_percentage, 1) }}%
              </strong>
            </div>
          </div>
          @endif

          <div class="mt-4">
            <small class="text-muted">
              <i class="bi bi-calendar me-1"></i>
              Dibuat: {{ $category->created_at->format('d M Y, H:i') }}
              @if($category->created_at != $category->updated_at)
              <br><i class="bi bi-arrow-repeat me-1"></i>
              Diperbarui: {{ $category->updated_at->format('d M Y, H:i') }}
              @endif
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">Aksi Cepat</h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="{{ route('apps.transactions.create', ['category_id' => $category->id]) }}" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi
          </a>
          <form action="{{ route('apps.categories.toggle-status', $category) }}" method="POST">
            @csrf
            @method('PUT')
            <button type="submit" class="btn btn-outline-warning w-100">
              @if($category->is_active)
              <i class="bi bi-toggle-off me-2"></i>Nonaktifkan
              @else
              <i class="bi bi-toggle-on me-2"></i>Aktifkan
              @endif
            </button>
          </form>
          <form action="{{ route('apps.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Hapus kategori ini? Transaksi yang terkait tidak akan dihapus.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger w-100">
              <i class="bi bi-trash me-2"></i>Hapus Kategori
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Column: Statistics -->
  <div class="col-lg-8 col-md-8 mb-3">
    <!-- Monthly Statistics -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Statistik Bulanan</h6>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            {{ date('F Y', strtotime(request('year'). ' '. request('month'))) }}
          </button>
          <ul class="dropdown-menu">
            @for($i = 0; $i < 6; $i++)
              <li><a class="dropdown-item {{ date('F Y', strtotime(-$i .' months')) == date('F Y', strtotime(request('year') . ' '. request('month'))) ? 'active' : '' }}" href="?month={{ date('m', strtotime(-$i. ' months')) }}&year={{ date('Y', strtotime(-$i. ' months')) }}">{{ date('F Y', strtotime("-$i months")) }}</a></li>
            @endfor
          </ul>
        </div>
      </div>
      <div class="card-body">
        @if($category->type === CategoryType::EXPENSE && $category->budget_limit)
        <div class="mb-4">
          <div class="d-flex justify-content-between mb-2">
            <span>Penggunaan Anggaran</span>
            <span class="{{ $category->has_budget_exceeded ? 'text-danger fw-bold' : '' }}">
              {{ number_format($category->budget_usage_percentage, 1) }}%
            </span>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar 
              @if($category->has_budget_exceeded) bg-danger 
              @elseif($category->budget_usage_percentage >= 80) bg-warning 
              @else bg-success @endif" 
              role="progressbar" style="width: {{ min($category->budget_usage_percentage, 100) }}%">
              <span class="progress-text">{{ number_format($category->budget_usage_percentage, 1) }}%</span>
            </div>
          </div>
          <div class="d-flex justify-content-between mt-2">
            <small>Rp 0</small>
            <small>{{ $category->formatted_budget_limit }}</small>
          </div>
        </div>
        @endif

        <!-- Transaction Summary -->
        <div class="row">
          <div class="col-md-6 mb-2">
            <div class="card bg-light">
              <div class="card-body">
                <h6 class="text-muted mb-2">Total Bulan {{ date("F Y", strtotime(request("year") . ' ' . request("month"))) }}</h6>
                <h3 class="mb-0 {{ $category->type === CategoryType::INCOME ? 'text-success' : 'text-danger' }}">
                  Rp {{ number_format($category->getIncomeTotal(date(request('month', 'm')), date(request('year', 'Y'))) / 100 + $category->getExpenseTotal(date(request('month', 'm')), date(request('year', 'Y'))) / 100, 0, ',', '.') }}
                </h3>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-2">
            <div class="card bg-light">
              <div class="card-body">
                <h6 class="text-muted mb-2">Rata-rata Transaksi</h6>
                @php
                $count = $category->transactions()->count();
                $avg = $count > 0 ? ($category->getIncomeTotal(date(request('month', 'm')), date(request('year', 'Y'))) / 100 + $category->getExpenseTotal(date(request('month', 'm')), date(request('year', 'Y'))) / 100) / $count : 0;
                @endphp
                <h3 class="text-secondary mb-0">Rp {{ number_format($avg, 0, ',', '.') }}</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card mb-4">
      <div class="card-header d-flex   justify-content-between align-items-center">
        <h6 class="mb-0">Transaksi Terbaru</h6>
        <a href="{{ route('apps.transactions.index', ['category_id' => $category->id]) }}" class="btn btn-sm btn-outline-primary">
          Lihat Semua
        </a>
      </div>
      <div class="card-body">
        @if($category->transactions->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Jumlah</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($category->transactions()->latest()->take(10)->get() as $transaction)
                <tr>
                  <td>{{ $transaction->transaction_date->format('d M Y H:i:s') }}</td>
                  <td>
                    <a href="{{ route('apps.transactions.show', $transaction) }}">
                      {{ Str::limit($transaction->description ?? $transaction->title, 30) }}
                    </a>
                  </td>
                  <td class="{{ $transaction->type === TransactionType::INCOME ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($transaction->amount->getAmount()->toInt(), 0, ',', '.') }}
                  </td>
                  <td>
                    @if($transaction->is_verified)
                      <span class="badge bg-success">Verified</span>
                    @else
                      <span class="badge bg-secondary">Unverified</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center py-4">
          <i class="bi bi-receipt display-4 text-muted mb-3"></i>
          <p class="text-muted">Belum ada transaksi untuk kategori ini</p>
          <a href="{{ route('apps.transactions.create', ['category_id' => $category->id]) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi Pertama
          </a>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
.category-icon-large {
    transition: transform 0.3s;
}

.category-icon-large:hover {
    transform: scale(1.1);
}

.progress {
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    position: relative;
    border-radius: 10px;
}

.progress-text {
    position: absolute;
    right: 10px;
    color: white;
    font-size: 0.8rem;
    font-weight: 500;
}

.category-details .row > div {
    padding: 0.5rem;
}

.budget-info {
    transition: all 0.3s;
}

.budget-info:hover {
    transform: translateX(5px);
}
</style>
@endpush