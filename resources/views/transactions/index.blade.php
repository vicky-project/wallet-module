@extends('core::layouts.app')

@section('title', $wallet->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
  <div>
    <a href="{{ route('apps.transactions.create') }}" role="button" class="btn btn-primary">
      <i class="fas fa-plus"></i>
    </a>
  </div>
  <h5>Transactions</h5>
</div>

<div class="row">
  @forelse($transactions as $date => $transaction)
  <div class="col-md-6 col-lg-6">
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
  </div>
  @empty
  <div class="alert alert-warning" role="alert">
    <p class="text-muted text-center">No transactions recorded.</p>
  </div>
  @endforelse
</div>
@endsection