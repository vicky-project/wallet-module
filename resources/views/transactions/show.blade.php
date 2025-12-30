@extends('core::layouts.app')

@section('title', 'Detail')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <a href="{{ route('apps.transactions.index') }}" class="btn btn-secondary" role="button" title="Back">
      <i class="fas fa-arrow-left"></i>
    </a>
  </div>
  <div>
    <a href="{{ route('apps.transactions.create') }}" class="btn btn-success" role="button" title="Create Transaction">
      <i class="fas fa-plus"></i>
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="card-title">{{ $transaction->wallet->name }}</h5><span class="small ms-2">{{ $transaction->wallet_code }}</span>
  </div>
  <div class="card-body">
    <p class="card-text small text-muted">{{ $transaction->transaction_code }}</p>
    <div class="text-end">
      <h3 class="text-bold ms-auto">{{ $transaction->amount }}</h3>
    </div>
    <div class="text-end">
      <small class="text-muted ms-auto">{{ $transaction->description}}</small>
    </div>
    <p class="card-text mt-2">{{ $transaction->category }} (<span class="small text-muted">{{ $transaction->type }}</span>)</p>
  </div>
</div>
@endsection