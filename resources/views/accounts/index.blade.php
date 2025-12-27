@extends('core::layouts.app')

@section('title', 'Accounts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1><i class="fas fa-user-circle"></i> My Accounts</h1>
  <a href="{{ route('apps.accounts.create') }}" class="btn bg-primary">
    <i class="fas fa-fw fa-plus"></i>
  </a>
</div>

<div class="row">
  @forelse ($accounts as $account)
  <div class="col col-md-6 col-lg-4 mb-4">
    <div class="card wallet-card h-100" onclick="window.location='{{ route('apps.wallets.index', ['account_id' => $account->id]) }}'">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h5 class="card-title">{{$account->name}}</h5>
            <h6 class="card-subtitle mb-2 text-muted">{{ $account->type }}
              @if($account->is_default)
              <span class="position-absolute top-0 start-100 translate-middle p-2 bg-success border border-light rounded-circle">
                <span class="visually-hidden">Default</span>
              </span>
              @endif
            </h6>
          </div>
          <span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">
            {{ $account->is_active ? "Active" : "Inactive" }}
          </span>
        </div>
        <p class="card-text text-muted">{{ $account->description }}</p>
        <div class="d-flex justify-content-between align-items-center">
          <small class="text-muted">{{$account->created_at->format('d M, Y')}}</small>
          <span>{{ $account->wallets_count ?? 0}} Wallets</span>
          <div class="btn-group">
            <a href="{{ route('apps.accounts.show', $account) }}" class="btn btn-sm btn-outline-secondary" role="button" title="View Account">
              <i class="fas fa-fw fa-eye"></i>
            </a>
            <a href="{{ route('apps.accounts.edit', $account) }}" class="btn btn-sm btn-outline-success" role="button" title="Edit Account">
              <i class="fas fa-fw fa-pen"></i>
            </a>
          </div>
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