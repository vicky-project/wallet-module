@extends('core::layouts.app')

@section('title', $wallet->name)

@section('content')
<div class="d-flex justify-content-between align-items-center">
  <span>Left</span>
  <h5>Transactions</h5>
</div>
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.wallet.show', $account) }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
      <a href="{{ route('apps.wallet.transactions.create', [$account, $wallet]) }}" role="button" class="btn btn-primary">
        <i class="fas fa-plus"></i>
      </a>
    </div>
    <h5 class="card-title">Transactions</h5>
  </div>
  <div class="card-body">
    @forelse($transactions as $date => $transaction)
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">{{ $date }}</h5>
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
    @empty
    <div class="alert alert-warning" role="alert">
      <p class="text-muted text-center">No transactions recorded.</p>
    </div>
    @endforelse
  </div>
</div>
@endsection