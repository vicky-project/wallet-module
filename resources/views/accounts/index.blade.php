@if(Module::has('Core') && Module::isEnabled('Core'))
  @extends('core::layouts.app')
@else 
  @extends('wallet::layouts.app')
@endif

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
          <div></div>
          <span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">
            {{ $account->is_active ? "Active" : "Inactive" }}
          </span>
        </div>
        <p class="card-text">{{ $account->description }}</p>
      </div>
    </div>
  </div>
  @empty
  <div class="col col-12">
    <div class="alert bg-info text-white d-flex align-items-center" role="alert">
      <p>You haven't created any accounts yet. Click the button above to create your first account.</p>
    </div>
  </div>
  @endforelse
</div>
@endsection