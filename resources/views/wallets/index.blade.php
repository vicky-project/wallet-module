@extends('core::layouts.app')

@section('title', 'Wallets')

@use('Illuminate\Support\Number')
@use('Modules\Wallet\Enums\WalletType')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5>Wallets</h5>
  </div>
  <div class="text-end">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWalletModal">
      <i class="fas fa-plus"></i>
    </button>
  </div>
</div>

<div class="accordion" id="filterWallet">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#filterContent" aria-expanded="false" aria-controls="filterContent">
        <i class="fas fa-fw fa-filter me-2"></i>
        Filter
      </button>
    </h2>
    <div id="filterContent" class="accordion-collapse collapse" data-bs-parent="filterWallet">
      <div class="accordion-body">
        <form method="GET" action="{{ route('apps.wallets.index') }}">
          <div class="row">
            <div class="col-md-3">
              <label for="filter-account_id" class="form-label">Account</label>
              <select name="account_id" class="form-select" id="filter-account_id">
                <option value="">All</option>
                @foreach($accounts as $account)
                <option value="{{ $account->id }}" @selected(request('account_id') == $account->id)>{{ $account->name }} ({{ $account->wallets->count() }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label for="filter-type" class="form-label">Type</label>
              <select name="type" class="form-select" id="filter-type">
                <option value="">All</option>
                @foreach(WalletType::cases() as $type)
                <option value="{{ $type->value }}" @selected(request('type') == $type->value)>{{ $type->value }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mt-4 pt-2 border-top border-primary">
            <div class="col-md-12">
              <div class="d-flex justify-content-end">
                <button type="reset" class="btn btn-outline-secondary me-2">Reset</button>
                <button type="submit" class="btn btn-outline-success">Apply</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="row mt-2">
  @forelse($wallets as $wallet)
  <div class="col-md-6 col-lg-4 mb-3">
    <div class="card card-wallet" onclick="window.location='{{ route('apps.wallets.show', $wallet) }}'">
      <div class="card-body">
        <h5 class="card-title">{{ $wallet->name }}</h5>
        <h3 class="text-success">
          {{ $wallet->balance }}
        </h3>
        <p class="card-text"><span class="small text-muted">{{ $wallet->type }}</span></p>
        <div class="d-flex justify-content-between">
          <span class="badge text-bg-secondary p-2">{{ $wallet->currency }}</span>
          <span class="badge text-bg-info p-2">{{ $wallet->transactions()->count ?? 0 }} Transactions</span>
          <div class="btn-group">
            <a href="{{ route('apps.wallets.edit', $wallet) }}" class="btn btn-sm btn-outline-success" role="button" title="Edit Walet">
              <i class="fas fa-fw fa-pen"></i>
            </a>
            <a href="{{ route('apps.wallets.show', $wallet) }}" class="btn btn-sm btn-outline-primary" role="button" title="View Transaction">
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
        </button>
      </div>
      <form method="POST" action="{{ route('apps.wallets.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label for="wallet-name" class="form-label">Wallet Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" id="wallet-name" placeholder="Enter wallet name..." required>
          </div>
          <div class="mb-3">
            <label for="wallet-type" class="form-label">Type</label>
            <select id="wallet-type" name="type" class="form-select">
              @foreach(WalletType::cases() as $type)
              <option value="{{ $type->value}}" @selected(old('type') == $type->value)>{{ $type->value}}</option>
              @endforeach
            </select>
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
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="wallet-is_default" name="is_default" value="1" checked>
              <label class="form-check-label" for="wallet-is_default">Default</label>
            </div>
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