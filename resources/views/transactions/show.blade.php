@extends('wallet::layouts.app')

@section('title', 'Detail Transaksi')

@use('Modules\Wallet\Enums\TransactionType')

@push('styles')
<style>
    .transaction-detail-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .transaction-icon-large {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto;
    }
    
    .detail-item {
        padding: 1rem 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .detail-item:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .detail-value {
        font-size: 1rem;
        font-weight: 500;
    }
    
    .tag-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
    }
    
    .action-buttons .btn {
        padding: 0.5rem 1rem;
    }
    
    body[data-bs-theme="dark"] .detail-item {
        border-bottom-color: rgba(255,255,255,0.05);
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 text-end">
  <div class="action-buttons me-auto">
    <a href="{{ route('apps.transactions.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
  </div>
  <h1 class="page-title">Detail Transaksi</h1>
</div>
    
<div class="row justify-content-center">
  <div class="col-lg-8">
    <!-- Transaction Header -->
    <div class="card transaction-detail-card mb-4">
      <div class="card-body text-center py-4">
        <div class="transaction-icon-large {{ $transaction->type == TransactionType::INCOME ? 'bg-income' : 'bg-expense' }} mb-3">
          <i class="bi {{ $transaction->type == TransactionType::INCOME ? 'bi-arrow-up-circle text-income' : 'bi-arrow-down-circle text-expense' }}"></i>
        </div>

        <h2 class="{{ $transaction->type == TransactionType::INCOME ? 'text-income' : 'text-expense' }} mb-2">
          {{ $transaction->type == TransactionType::INCOME ? '+' : '-' }}{{ $transaction->amount->formatTo('id_ID') }}
        </h2>

        <h4 class="mb-3">{{ $transaction->title }}</h4>
                    
        <div class="d-flex justify-content-center gap-2 mb-3">
          <span class="badge {{ $transaction->type == TransactionType::INCOME ? 'bg-income' : 'bg-expense' }} tag-badge">
            {{ $transaction->type == TransactionType::INCOME ? 'Pemasukan' : 'Pengeluaran' }}
          </span>

          @if($transaction->is_recurring)
            <span class="badge bg-info tag-badge">
              <i class="bi bi-repeat me-1"></i>Rutin
            </span>
          @endif
                        
          @if($transaction->is_verified === false)
            <span class="badge bg-warning tag-badge">
              <i class="bi bi-clock me-1"></i>Pending
            </span>
          @endif
        </div>

        <p class="text-muted mb-0">
          <i class="bi bi-calendar me-1"></i>
          {{ $transaction->transaction_date->translatedFormat('l, d F Y') }}
          <i class="bi bi-clock ms-3 me-1"></i>
          {{ $transaction->transaction_date->format('H:i') }}
        </p>
      </div>
    </div>
            
    <!-- Transaction Details -->
    <div class="card transaction-detail-card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-4">Informasi Transaksi</h5>
                    
        <div class="detail-item">
          <div class="row">
            <div class="col-md-6">
              <div class="detail-label">Kategori</div>
              <div class="detail-value">
                <span class="badge rounded-pill d-inline-flex align-items-center" style="background-color: {{ $transaction->category->color }}20; color: {{ $transaction->category->color }}">
                  <i class="bi {{ $transaction->category->icon }} me-1"></i>
                  {{ $transaction->category->name }}
                </span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="detail-label">Akun</div>
              <div class="detail-value">
                <i class="bi {{ $transaction->account->icon }} me-2" style="color: {{ $transaction->account->color }}"></i>
                {{ $transaction->account->name }}
              </div>
            </div>
          </div>
        </div>
        <div class="detail-item">
          <div class="row">
            <div class="col-md-6">
              <div class="detail-label">Metode Pembayaran</div>
              <div class="detail-value">
                @php
                $paymentMethods = [
                  'cash' => 'Tunai',
                  'bank_transfer' => 'Transfer Bank',
                  'credit_card' => 'Kartu Kredit',
                  'e_wallet' => 'E-Wallet',
                  'other' => 'Lainnya'
                ];
                @endphp
                {{ $paymentMethods[$transaction->payment_method] ?? $transaction->payment_method }}
              </div>
            </div>
            <div class="col-md-6">
              <div class="detail-label">Status</div>
              <div class="detail-value">
                @if($transaction->is_verified)
                <span class="badge bg-success tag-badge">
                  <i class="bi bi-check-circle me-1"></i>Terverifikasi
                </span>
                @else
                <span class="badge bg-warning tag-badge">
                  <i class="bi bi-clock me-1"></i>Belum Diverifikasi
                </span>
                @endif
              </div>
            </div>
          </div>
        </div>

        @if($transaction->is_recurring)
        <div class="detail-item">
          <div class="detail-label">Pengaturan Rutin</div>
          <div class="detail-value">
            @php
            $periods = [
              'daily' => 'Harian',
              'weekly' => 'Mingguan',
              'monthly' => 'Bulanan',
              'yearly' => 'Tahunan'
            ];
            @endphp
            {{ $periods[$transaction->recurring_period] ?? $transaction->recurring_period }}

            @if($transaction->recurring_end_date)
            <span class="text-muted ms-2">
              hingga {{ $transaction->recurring_end_date->format('d/m/Y') }}
            </span>
            @endif
          </div>
        </div>
        @endif

        @if($transaction->description)
        <div class="detail-item">
          <div class="detail-label">Deskripsi</div>
          <div class="detail-value">
            {{ $transaction->description }}
          </div>
        </div>
        @endif

        <div class="detail-item">
          <div class="row">
            <div class="col-md-6">
              <div class="detail-label">Dibuat Pada</div>
              <div class="detail-value">
                {{ $transaction->created_at->translatedFormat('d F Y H:i') }}
              </div>
            </div>
            <div class="col-md-6">
              <div class="detail-label">Diperbarui Pada</div>
              <div class="detail-value">
                {{ $transaction->updated_at->translatedFormat('d F Y H:i') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
            
    <!-- Action Buttons -->
    <div class="card transaction-detail-card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <a href="{{ route('apps.transactions.edit', $transaction->id) }}" class="btn btn-primary">
              <i class="bi bi-pencil me-2"></i>Edit Transaksi
            </a>
          </div>

          <div>
            <form action="{{ route('apps.transactions.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?')">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash me-2"></i>Hapus Transaksi
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection