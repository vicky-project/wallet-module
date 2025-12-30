@extends('core::layouts.app')

@section('title', 'Detail')

@use('Modules\Wallet\Helpers\Helper')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <a href="{{ route('apps.transactions.index') }}" class="btn btn-secondary" role="button" title="Back">
      <i class="fas fa-arrow-left"></i>
    </a>
  </div>
  <div>
    <a href="{{ route('apps.transactions.create') }}" class="btn btn-primary" role="button" title="Create Transaction">
      <i class="fas fa-plus"></i>
    </a>
    <a href="{{ route('apps.transactions.edit', $transaction) }}" class="btn btn-success" role="button" title="Edit Transaction">
      <i class="fas fa-pen"></i>
    </a>
    <form method="POST" action="">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure to delete this transaction ?')">
        <i class="fas fa-trash"></i>
      </button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="card-title">{{ $transaction->wallet->name }}<span class="small ms-2">{{ $transaction->wallet->wallet_code }}</span></h5>
  </div>
  <div class="card-body">
    <p class="card-text small text-muted">{{ $transaction->transaction_code }}</p>
    <div class="text-end">
      <h3 class="text-bold ms-auto {{ Helper::getColorCategory($transaction->type )}}">{{ $transaction->amount }}</h3>
    </div>
    <div class="text-end">
      <small class="text-muted ms-auto">{{ $transaction->description}}</small>
    </div>
    <p class="card-text mt-2">{{ $transaction->category }} (<span class="small {{ Helper::getColorCategory($transaction->type)}}">{{ $transaction->type }}</span>)</p>
  </div>
</div>
@endsection