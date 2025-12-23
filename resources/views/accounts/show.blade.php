@extends('core::layouts.app')

@section('title', $account->name)

@section('content')
<div class="row">
  @foreach($wallets as $wallet)
  <div class="col-md-6 col-lg-4 mb-3">
    <div class="card card-wallet" onclick="window.location='{{ route('apps.wallet.wallets.show', [$account, $wallet]) }}'">
      <div class="card-body">
        <h5 class="card-title">{{ $wallet->name }}</h5>
        <h3 class="text-success">@money(number_format($wallet->balance, 2), $account>currency)</h3>
        <p class="card-text">
          <small class="text-muted">Slug: {{ $wallet->slug }}</small>
        </p>
        <div class="d-flex justify-content-between">
          <span class="badge badge-info">{{ $wallet->transactions_count ?? 0 }} Transactions</span>
          <a href="{{ route('apps.wallet.wallets.show', [$account, $wallet]) }}" class="btn btn-sm btn-outline-primary" role="button">View Transactions</a>
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>
@endsection