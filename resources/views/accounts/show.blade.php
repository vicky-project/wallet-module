@extends('core::layouts.app')

@section('title', $account->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <a href="{{ route('apps.wallet.index') }}" class="btn btn-secondary" role="button" title="Back">
      <i class="fas fa-arrow-left"></i>
    </a>
    <a href="{{ route('apps.wallet.edit', $account) }}" class="btn btn-success" role="button" title="Edit Account">
      <i class="fas fa-pen"></i>
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
        <h3 class="text-success">
          <x-money amount="{{number_format((float) $wallet->balance, 2, '.', ',')}}" currency="{{ $wallet->meta['currency']}}" />
        </h3>
        <p class="card-text">
          <small class="text-muted">Slug: {{ $wallet->slug }}</small>
        </p>
        <div class="d-flex justify-content-between">
          <span class="badge text-bg-secondary p-2">{{ $wallet->meta['currency'] ?? '' }}</span>
          <span class="badge text-bg-info p-2">{{ $wallet->transactions_count ?? 0 }} Transactions</span>
          <div class="btn-group">
            <a href="{{ route('apps.wallet.wallets.edit', [$account, $wallet]) }}" class="btn btn-sm btn-outline-success" role="button" title="Edit Walet">
              <i class="fas fa-fw fa-pen"></i>
            </a>
            <a href="{{ route('apps.wallet.wallets.show', [$account, $wallet]) }}" class="btn btn-sm btn-outline-primary" role="button" title="View Transaction">
              <i class="fas fa-fw fa-eye"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="col-12 col-lg-12">
    <div class="alert alert-warning" role="alert">
      <p class="text-muted">You don't have any wallets yet. Please create one first.</p>
    </div>
  </div>
  @endforelse
</div>

<div class="modal fade" id="createWalletModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Wallet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form method="POST" action="{{ route('apps.wallet.wallets.store', $account) }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label for="wallet-name" class="form-label">Wallet Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" id="wallet-name" placeholder="Enter wallet name..." required>
          </div>
          <div class="mb-3">
            <label for="wallet-initial-balance" class="form-label">Initial Balance</label>
            <input type="number" min="0" name="initial_balance" class="form-control" id="wallet-initial-balance" value="0">
          </div>
          <div class="mb-3">
            <label for="account-currency" class="form-label">Currency</label>
            <select class="form-select" name="currency" id="account-currency">
              @foreach($currencies as $currency => $name)
              <option value="{{$currency}}" @selected($currency ==="IDR")>{{$name}}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label for="wallet-description" class="form-label">Description</label>
            <textarea name="description" id="wallet-description" class="form-control" placeholder="Description of wallet.."></textarea>
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