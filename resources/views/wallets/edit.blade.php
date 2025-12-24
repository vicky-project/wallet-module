@extends('core::layouts.app')

@section('title', 'Edit '. $wallet->name)

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.wallet.wallets.show', [$account, $wallet]) }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">Edit Wallet</h5>
  </div>
  <div class="card-body"></div>
</div>
@endsection