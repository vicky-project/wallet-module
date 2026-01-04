@extends('wallet::layouts.app')

@section('title', 'Detail Akun - ' . $account->name . ' - ' . config('app.name'))

@section('content')
@include('wallet::partials.fab')
<div class="d-flex justify-content-between align-items-center mb-4 text-end">
  <div class="d-flex justify-content-between align-items-center me-auto gap-2">
    <a href="{{ route('apps.accounts.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <a href="{{ route('apps.accounts.edit', $account) }}" class="btn btn-primary">
      <i class="bi bi-pencil"></i>
    </a>
  </div>
  <div>
    <h1 class="page-title mb-2">
      <i class="bi bi-bank me-2"></i>Detail Akun
    </h1>
    <p class="text-muted mb-0">Informasi lengkap dan transaksi akun "{{ $account->name }}"</p>
  </div>
</div>

<!-- Account Overview -->
<div class="row">
  <!-- Account Details -->
  <div class="col-lg-4 col-md-4 mb-3">
    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex align-items-center mb-4">
          <div class="account-icon-large me-3" style="width: 70px; height: 70px; background: {{ $account->color ?? '#4361ee' }}; color: white; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
            <i class="bi {{ $account->icon_class }} fs-3"></i>
          </div>
          <div>
            <h4 class="mb-1">{{ $account->name }}</h4>
            <span class="badge bg-primary">{{ $account->type_label }}</span>
          </div>
        </div>

        <div class="account-details">
          <div class="mb-3">
            <h6 class="text-muted mb-1">Saldo Saat Ini</h6>
            <h2 class="text-success">{{ $account->formatted_balance }}</h2>
          </div>

          @if($account->bank_name)
          <div class="mb-3">
            <h6 class="text-muted mb-1">Bank</h6>
            <p class="mb-0">{{ $account->bank_name }}</p>
          </div>
          @endif

          @if($account->account_number)
          <div class="mb-3">
            <h6 class="text-muted mb-1">Nomor Rekening</h6>
            <p class="mb-0">{{ $account->account_number }}</p>
          </div>
          @endif

          @if($account->description)
          <div class="mb-3">
            <h6 class="text-muted mb-1">Deskripsi</h6>
            <p class="mb-0">{{ $account->description }}</p>
          </div>
          @endif

          <div class="row mb-3">
            <div class="col-6">
              <h6 class="text-muted mb-1">Status</h6>
              @if($account->is_default)
              <span class="badge bg-success">Default</span>
              @else
              <span class="badge bg-secondary">Non Default</span>
              @endif
            </div>
            <div class="col-6">
              <h6 class="text-muted mb-1">Mata Uang</h6>
              <p class="mb-0">{{ $account->currency }}</p>
            </div>
          </div>

          <div class="mt-4">
            <small class="text-muted">
              <i class="bi bi-calendar me-1"></i>
              Dibuat: {{ $account->created_at->format('d M Y') }}
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Aksi Cepat</h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="{{ route('apps.transactions.create', ['account_id' => $account->id]) }}" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-2"></i>Tambah Transaksi
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Transactions -->
  <div class="col-lg-8 col-md-8 mb-3">
    <!-- Balance History -->
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">Statistik Transaksi</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="card bg-light">
              <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Transaksi</h6>
                <h3 class="mb-0 text-secondary">{{ $account->transactions->count() }}</h3>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="card bg-light">
              <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Pemasukan</h6>
                <h3 class="mb-0 text-success">
                  Rp {{ number_format($incomeTotal, 0, ',', '.') }}
                </h3>
              </div>
            </div>
          </div>
          <div class="col-md-4 mb-3">
            <div class="card bg-light">
              <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Pengeluaran</h6>
                <h3 class="mb-0 text-danger">
                  Rp {{ number_format($expenseTotal, 0, ',', '.') }}
                </h3>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Transaksi Terbaru</h6>
        <a href="{{ route('apps.transactions.index', ['account_id' => $account->id]) }}" class="btn btn-sm btn-outline-primary">
          Lihat Semua
        </a>
      </div>
      <div class="card-body">
        @if($account->transactions->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Jumlah</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($account->transactions()->latest()->take(10)->get() as $transaction)
                <tr>
                  <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                  <td>
                    <span class="badge bg-light text-dark">
                      <i class="bi {{ $transaction->category->icon_class ?? 'bi-tag' }} me-1"></i>
                      {{ $transaction->category->name ?? 'Tanpa Kategori' }}
                    </span>
                  </td>
                  <td>
                    <a href="{{ route('apps.transactions.show', $transaction) }}">
                      {{ Str::limit($transaction->description, 30) }}
                    </a>
                  </td>
                  <td class="{{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                  </td>
                  <td>
                    @if($transaction->status === 'completed')
                    <span class="badge bg-success">Selesai</span>
                    @elseif($transaction->status === 'pending')
                    <span class="badge bg-warning">Pending</span>
                    @else
                    <span class="badge bg-secondary">Dibatalkan</span>
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
          <p class="text-muted">Belum ada transaksi untuk akun ini</p>
          <a href="{{ route('apps.transactions.create', ['account_id' => $account->id]) }}" class="btn btn-primary">
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
.account-icon-large {
    transition: transform 0.3s;
}

.account-icon-large:hover {
    transform: scale(1.1);
}
</style>
@endpush