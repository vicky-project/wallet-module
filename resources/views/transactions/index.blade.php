@extends('core::layouts.app')

@section('title', 'Transactions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
  <div>
    <h5><i class="fas fa-arrow-right-arrow-left"></i> Transactions</h5>
    @if($wallets->isNotEmpty())
    <span class="small text-muted">{{ $wallets->where('is_default', true)->first()->name }}</span>
    @endif
  </div>
  <div>
    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#changeDefaultWalletModal">
      <i class="fas fa-wallet"></i>
    </button>
    <a href="{{ route('apps.transactions.create') }}" role="button" class="btn btn-primary">
      <i class="fas fa-plus"></i>
    </a>
  </div>
</div>

<div class="row">
  @forelse($transactions as $date => $transaction)
  <div class="col-md-6 col-lg-6">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title" onclick="window.location='{{ route('apps.transactions.dates', ['date' => $date]) }}'">{{ $date }}</h5>
      </div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between">
            <strong>Total</strong>
            <span class="text-muted">{{ $transaction['total'] }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <strong>Deposit</strong>
            <span class="text-success">{{ $transaction['deposit'] }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <strong>Withdraw</strong>
            <span class="text-danger">{{ $transaction['withdraw'] }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
  @empty
  <div class="alert alert-warning" role="alert">
    <p class="text-muted text-center">No transactions recorded.</p>
  </div>
  @endforelse
</div>

<div class="modal fade" id="changeDefaultWalletModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Wallet default</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('apps.wallets.default') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label for="wallet-id" class="form-label">Set Default</label>
            <select name="wallet_id" class="form-select" id="wallet-id">
              @foreach($wallets as $wallet)
              <option value="{{ $wallet->id }}" @selected($wallet->is_default)>{{ $wallet->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"  data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection