@extends('core::layouts.app')

@section('title', $wallet->name)

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.wallets.index') }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">{{ $wallet->name }}</h5><span class="small text-muted ms-2">{{ $wallet->wallet_code }}</span>
  </div>
  <div class="card-body">
    <ul class="list-group list-group-flush">
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Account</strong>
        <span class="text-muted">{{ $wallet->account->name }}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Type</strong>
        <span class="text-muted">{{ $wallet->type }}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Balance</strong>
        <span class="text-muted">{{ $wallet->balance }}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Initial Balance</strong>
        <span class="text-muted">{{ $wallet->initial_balance }}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Currency</strong>
        <span class="text-muted">{{ $wallet->currency }}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Description</strong>
        <span class="text-muted text-end">{{ $wallet->description }}</span>
      </li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <strong>Default</strong>
        @if($wallet->is_default)
        <span class="badge text-bg-success rounded-pill">YES</span>
        @else
        <span class="badge text-bg-danger rounded-pill">NO</span>
        @endif
      </li>
    </ul>
  </div>
</div>
@endsection