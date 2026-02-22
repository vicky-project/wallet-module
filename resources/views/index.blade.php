@extends('core::layouts.app')

@section('title', 'Keuangan')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Ringkasan Saldo (di tengah) -->
        <div class="text-center my-4">
            <small class="text-uppercase" style="letter-spacing: 1px; color: var(--tg-theme-hint-color);">Total Saldo</small>
            <h1 class="display-4 fw-bold" style="color: var(--tg-theme-text-color);">Rp {{ number_format($totalBalance, 0, ',', '.') }}</h1>
        </div>

        <!-- Daftar Akun (maksimal 5) -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">Akun</h5>
                @if($accounts->count() > 5)
                <a href="#" class="small" style="color: var(--tg-theme-button-color);" onclick="showToast('Lihat semua akun', 'info')">Lihat semua</a>
                @endif
            </div>
            <div class="row g-3">
                @forelse($accounts->take(5) as $account)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: {{ $account->color }}20; color: {{ $account->color }};">
                                    <i class="bi {{ $account->icon }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-truncate" style="color: var(--tg-theme-text-color);">{{ $account->name }}</h6>
                                    @if($account->is_default)
                                        <span class="badge bg-info" style="font-size: 0.6rem;">Utama</span>
                                    @endif
                                </div>
                            </div>
                            <p class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">
                                Rp {{ number_format($account->balance, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center py-4" style="color: var(--tg-theme-hint-color);">
                        <i class="bi bi-wallet2 display-6"></i>
                        <p class="mt-2">Belum ada akun. Tambahkan akun baru.</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Transaksi Terbaru -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">Transaksi Terbaru</h5>
                @if($recentTransactions->count() > 0)
                <a href="#" class="small" style="color: var(--tg-theme-button-color);" onclick="showToast('Lihat semua transaksi', 'info')">Lihat semua</a>
                @endif
            </div>
            @forelse($recentTransactions as $transaction)
            <div class="card border-0 shadow-sm mb-2" style="background-color: var(--tg-theme-secondary-bg-color);">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: {{ $transaction->category?->color ?? '#6c757d' }}20; color: {{ $transaction->category?->color ?? '#6c757d' }};">
                            <i class="bi {{ $transaction->category?->icon ?? 'bi-tag' }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0" style="color: var(--tg-theme-text-color);">{{ $transaction->description }}</h6>
                                    <small class="text-muted">{{ $transaction->account->name }} • {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M H:i') }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->type == 'income' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
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

        <!-- Kategori Pengeluaran Bulan Ini -->
        @if($expensesByCategory->count() > 0)
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">Kategori Pengeluaran</h5>
                <a href="#" class="small" style="color: var(--tg-theme-button-color);" onclick="showToast('Lihat semua kategori', 'info')">Lihat semua</a>
            </div>
            @foreach($expensesByCategory as $expense)
            @php
                $totalExpense = $expensesByCategory->sum('total');
                $percentage = $totalExpense > 0 ? round(($expense->total / $totalExpense) * 100) : 0;
            @endphp
            <div class="d-flex align-items-center mb-2">
                <div class="me-2" style="width: 30px; color: {{ $expense->category->color ?? '#6c757d' }};">
                    <i class="bi {{ $expense->category->icon ?? 'bi-tag' }}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between small mb-1">
                        <span style="color: var(--tg-theme-text-color);">{{ $expense->category->name }}</span>
                        <span style="color: var(--tg-theme-text-color);">{{ $percentage }}%</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: var(--tg-theme-hint-color);">
                        <div class="progress-bar" role="progressbar" style="width: {{ $percentage }}%; background-color: {{ $expense->category->color ?? '#3490dc' }};" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<!-- Floating Action Button untuk Tambah Transaksi -->
<button class="btn rounded-circle shadow-lg position-fixed" style="bottom: 20px; right: 20px; width: 56px; height: 56px; background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none; z-index: 1000;" onclick="showToast('Tambah transaksi', 'info')">
    <i class="bi bi-plus-lg fs-4"></i>
</button>
@endsection

@push('styles')
<style>
    /* Tambahkan padding-bottom agar konten tidak tertutup FAB */
    .content {
        padding-bottom: 80px;
    }
</style>
@endpush

@push('scripts')
<script>
    // Pastikan showToast tersedia (fallback jika belum)
    if (typeof showToast !== 'function') {
        window.showToast = function(message, type) {
            alert(message);
        };
    }
</script>
@endpush