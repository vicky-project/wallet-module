@extends('core::layouts.app')

@section('title', 'Keuangan')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Ringkasan Saldo -->
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, var(--tg-theme-button-color) 0%, rgba(var(--tg-theme-button-color-rgb, 64,167,227), 0.8) 100%);">
            <div class="card-body text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75">Total Saldo</small>
                        <h2 class="display-6 mb-0 fw-bold">Rp {{ number_format($totalBalance, 0, ',', '.') }}</h2>
                    </div>
                    <div class="text-end">
                        <a href="#" class="btn btn-light btn-sm rounded-pill px-3" onclick="showToast('Tambah transaksi', 'info')">
                            <i class="bi bi-plus-circle me-1"></i> Transaksi
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Akun -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">
                    <i class="bi bi-wallet2 me-2" style="color: var(--tg-theme-button-color);"></i>Akun Saya
                </h5>
                <a href="#" class="small" style="color: var(--tg-theme-button-color);" onclick="showToast('Tambah akun', 'info')">
                    + Tambah
                </a>
            </div>
            <div class="row g-3">
                @forelse($accounts as $account)
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
                            <small class="text-muted">{{ $account->account_number ?? 'Tanpa nomor' }}</small>
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

        <!-- Anggaran Bulan Ini (jika ada) -->
        @if($budgets->count() > 0)
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">
                    <i class="bi bi-pie-chart me-2" style="color: var(--tg-theme-button-color);"></i>Anggaran {{ now()->format('F Y') }}
                </h5>
                <a href="#" class="small" style="color: var(--tg-theme-button-color);" onclick="showToast('Kelola anggaran', 'info')">Kelola</a>
            </div>
            <div class="row g-3">
                @foreach($budgets as $budget)
                @php
                    $percentage = $budget->amount > 0 ? min(round(($budget->spent / $budget->amount) * 100), 100) : 0;
                    $status = $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'success');
                @endphp
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="background-color: var(--tg-theme-secondary-bg-color);">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="color: var(--tg-theme-text-color);">{{ $budget->category->name }}</span>
                                <span style="color: var(--tg-theme-text-color);">
                                    Rp {{ number_format($budget->spent, 0, ',', '.') }} / Rp {{ number_format($budget->amount, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="progress" style="height: 8px; background-color: var(--tg-theme-hint-color);">
                                <div class="progress-bar bg-{{ $status }}" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Transaksi Terbaru -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">
                    <i class="bi bi-clock-history me-2" style="color: var(--tg-theme-button-color);"></i>Transaksi Terbaru
                </h5>
                <a href="#" class="small" style="color: var(--tg-theme-button-color);" onclick="showToast('Lihat semua', 'info')">Lihat semua</a>
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

        <!-- Pengeluaran per Kategori Bulan Ini (grafik sederhana) -->
        @if($expensesByCategory->count() > 0)
        <div class="mb-4">
            <h5 class="fw-bold mb-3" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-bar-chart me-2" style="color: var(--tg-theme-button-color);"></i>Pengeluaran Bulan Ini
            </h5>
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
@endsection

@push('scripts')
<script>
    // Fungsi showToast sudah ada di layout, tapi kita pastikan
    if (typeof showToast !== 'function') {
        function showToast(message, type) {
            alert(message);
        }
    }
</script>
@endpush