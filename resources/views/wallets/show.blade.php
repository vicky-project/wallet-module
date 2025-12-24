@extends('core::layouts.app')

@section('title', $wallet->name)

@section('content')
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
    @forelse($transactions as $transaction)
    @empty
    <div class="alert alert-warning" role="alert">
      <p class="text-muted">No transactions recorded.</p>
    </div>
    @endforelse
  </div>
</div>
@endsection