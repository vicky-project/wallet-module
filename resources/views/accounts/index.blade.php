@extends('core::layouts.app')

@section('title', 'Accounts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1><i class="fas fa-wallet"></i> My Accounts</h1>
  <a href="{{ route('apps.wallet.create') }}" class="btn bg-primary">
    <i class="fas fa-fw fa-plus"></i>
  </a>
</div>

<div class="row">
  @forelse ($accounts as $account)
  <div class="col col-md-6 col-lg-4 mb-4">
    <div class="card wallet-card h-100" onclick="window.location='{{ route('apps.wallet.show', $account) }}'">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h5 class="card-title">{{$account->name}}</h5>
            <h6 class="card-subtitle mb-2 text-muted">{{ $account->type }}</h6>
          </div>
          <span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">
            {{ $account->is_active ? "Active" : "Inactive" }}
          </span>
        </div>
        <p class="card-text">{{ $account->description }}</p>
        <div class="d-flex justify-content-between align-items-center">
          <small class="text-muted">{{$account->created_at->format('d M, Y')}}</small>
          <span>{{ $account->wallets_count ?? 0}} Wallets</span>
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="col col-12">
    <div class="alert alert-warning" role="alert">
      <p>You haven't created any accounts yet. Click the button above to create your first account.</p>
    </div>
  </div>
  @endforelse
</div>
@endsection