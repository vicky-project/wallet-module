@extends('core::layouts.app')

@section('title', $account->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <a href="{{ route('apps.wallet.index') }}" class="btn btn-secondary" role="button">
      <i class="fas fa-arrow-left"></i>
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWalletModal">
      <i class="fas fa-plus"></i>
    </button>
  </div>
  <div class="text-end">
    <h5>{{ $account->name }}</h5>
  </div>
</div>
<div class="row">
  @forelse($wallets as $wallet)
  <div class="col-md-6 col-lg-4 mb-3">
    <div class="card card-wallet" onclick="window.location='{{ route('apps.wallet.wallets.show', [$account, $wallet]) }}'">
      <div class="card-body">
        <h5 class="card-title">{{ $wallet->name }}</h5>
        <h3 class="text-success">@money(number_format($wallet->balance, 2), $account>currency)</h3>
        <p class="card-text">
          <small class="text-muted">Slug: {{ $wallet->slug }}</small>
        </p>
        <div class="d-flex justify-content-between">
          <span class="badge badge-info">{{ $wallet->transactions_count ?? 0 }} Transactions</span>
          <a href="{{ route('apps.wallet.wallets.show', [$account, $wallet]) }}" class="btn btn-sm btn-outline-primary" role="button">View Transactions</a>
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="col-12 col-lg-12">
    <div class="alert alert-warning" role="alert">
      <p class="text-muted">You don't have any wallets. Please create one first.</p>
    </div>
  </div>
  @endforelse
</div>

<div class="modal fade" id="createWalletModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Wallet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('apps.wallet.wallets.store', $account) }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label for="wallet-name" class="form-label">Wallet Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" id="wallet-name" placeholder="Enter wallet name..." required>
          </div>
          <div class="mb-3">
            <label for="wallet-initial-balance" class="form-label">Initi Balance</label>
            <input type="number" min="0" name="initial_balance" class="form-control" id="wallet-initial-balance" value="0">
          </div>
          <div class="mb-3">
            <label for="wallet-description" class="form-label">Description</label>
            <textarea name="description" id="wallet-description" class="form-control" placeholder="Description of wallet-description.."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection