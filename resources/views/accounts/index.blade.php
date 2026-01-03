@extends('wallet::layouts.app')

@section('title', 'Akun Bank - ' . config('app.name'))

@section('content')
@include('wallet:partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-2">
                <i class="bi bi-bank me-2"></i>Akun & Dompet
            </h1>
            <p class="text-muted mb-0">Kelola semua akun bank, dompet digital, dan uang tunai Anda</p>
        </div>
        <a href="{{ route('wallet.accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Tambah Akun
        </a>
    </div>

<!-- Stats Cards -->
<div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Akun</h6>
                            <h3 class="mb-0">{{ $stats['total_accounts'] ?? 0 }}</h3>
                        </div>
                        <div class="card-icon bg-primary text-white">
                            <i class="bi bi-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Saldo</h6>
                            <h3 class="mb-0">{{ $stats['total_balance'] ?? 'Rp 0' }}</h3>
                        </div>
                        <div class="card-icon bg-success text-white">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Akun Default</h6>
                            <h3 class="mb-0">{{ $stats['default_account'] ? 1 : 0 }}</h3>
                        </div>
                        <div class="card-icon bg-warning text-white">
                            <i class="bi bi-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Jenis Akun</h6>
                            <h3 class="mb-0">{{ count(array_filter($stats['accounts_by_type'] ?? [])) }}</h3>
                        </div>
                        <div class="card-icon bg-info text-white">
                            <i class="bi bi-tags"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Account Type Summary -->
@if(isset($stats['summary']) && count($stats['summary']) > 0)
  <div class="row mb-4">
        @foreach($stats['summary'] as $type => $data)
        @if($data['count'] > 0)
        <div class="col-md-2 col-sm-4 mb-3">
            <div class="card">
                <div class="card-body text-center p-3">
                    <div class="account-type-icon mb-2" 
                         style="width: 50px; height: 50px; background: {{ [
                             'cash' => '#10b981',
                             'bank' => '#3b82f6',
                             'ewallet' => '#8b5cf6',
                             'credit_card' => '#ef4444',
                             'investment' => '#f59e0b'
                         ][$type] ?? '#6366f1' }}; color: white; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi {{ [
                            'cash' => 'bi-cash-stack',
                            'bank' => 'bi-bank',
                            'ewallet' => 'bi-phone',
                            'credit_card' => 'bi-credit-card',
                            'investment' => 'bi-graph-up'
                        ][$type] ?? 'bi-wallet' }} fs-4"></i>
                    </div>
                    <h6 class="mb-1">{{ ucfirst($type) }}</h6>
                    <h5 class="mb-0">{{ $data['count'] }}</h5>
                    <small class="text-muted">{{ $data['formatted_total'] ?? 'Rp 0' }}</small>
                </div>
            </div>
        </div>
        @endif
        @endforeach
    </div>
@endif

<!-- Accounts Grid -->
<div class="row">
        @forelse($accounts as $account)
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
            <div class="card account-card h-100" 
                 style="border-left: 5px solid {{ $account->color }};">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="account-icon mb-2" 
                                 style="width: 50px; height: 50px; background: {{ $account->color }}; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi {{ $account->icon_class }} fs-4"></i>
                            </div>
                            <h5 class="card-title mb-1">{{ $account->name }}</h5>
                            <p class="text-muted small mb-0">{{ $account->bank_name ?? 'Uang Tunai' }}</p>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('wallet.accounts.show', $account) }}">
                                        <i class="bi bi-eye me-2"></i>Detail
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('wallet.accounts.edit', $account) }}">
                                        <i class="bi bi-pencil me-2"></i>Edit
                                    </a>
                                </li>
                                @if(!$account->is_default)
                                <li>
                                    <form action="{{ route('wallet.accounts.toggle-default', $account) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-star me-2"></i>Jadikan Default
                                        </button>
                                    </form>
                                </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('wallet.accounts.destroy', $account) }}" method="POST" 
                                          onsubmit="return confirm('Hapus akun ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-trash me-2"></i>Hapus
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="account-info mb-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Tipe</small>
                                <span class="badge bg-light text-dark">{{ $account->type_label }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted d-block">Status</small>
                                @if($account->is_default)
                                <span class="badge bg-warning"><i class="bi bi-star me-1"></i>Default</span>
                                @else
                                <span class="badge bg-secondary">Biasa</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="account-balance mb-3">
                        <small class="text-muted d-block">Saldo Saat Ini</small>
                        <h3 class="mb-1">{{ $account->formatted_current_balance }}</h3>
                        @if($account->balance_change != 0)
                        <small class="{{ $account->is_balance_positive ? 'text-success' : 'text-danger' }}">
                            <i class="bi {{ $account->is_balance_positive ? 'bi-arrow-up' : 'bi-arrow-down' }} me-1"></i>
                            {{ $account->formatted_balance_change }}
                        </small>
                        @endif
                    </div>

                    @if($account->account_number)
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="bi bi-credit-card me-1"></i>{{ $account->account_number }}
                        </small>
                    </div>
                    @endif

                    <div class="mt-3">
                        <a href="{{ route('wallet.accounts.show', $account) }}" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-arrow-right-circle me-1"></i>Kelola Akun
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-bank display-1 text-muted mb-3"></i>
                    <h4 class="mb-3">Belum ada akun</h4>
                    <p class="text-muted mb-4">Mulai dengan menambahkan akun pertama Anda</p>
                    <a href="{{ route('wallet.accounts.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Akun Pertama
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>
@endsection

@push('styles')
<style>
.account-card {
    transition: transform 0.3s, box-shadow 0.3s;
    border-radius: 12px;
}

.account-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.account-icon {
    transition: transform 0.3s;
}

.account-card:hover .account-icon {
    transform: scale(1.1);
}

.account-type-icon {
    transition: transform 0.3s;
}

.account-type-icon:hover {
    transform: scale(1.1);
}
</style>
@endpush