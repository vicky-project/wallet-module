@if(Module::has('ViewManager'))
@extends('viewmanager::layouts.app')
@else 
@extends('wallet::layouts.app')
@endif

@section('title', 'Accounts')
@section('page-title', 'Accounts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  @if(Module::has('ViewManager'))
  <h1></h1>
  @else 
  <h1><i class="bi bi-credit-card"></i> My Accounts</h1>
  @endif
  <a href="{{ route('wallet.accounts.create') }}" class="btn bg-primary">
    @if(Module::has('ViewManager'))
    <svg class="icon">
      <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-plus') }}"></use>
    </svg>
    New Account
    @else 
    <i class="bi bi-plus-circle"></i> New Account
    @endif
  </a>
</div>

<div class="row">
  @forelse ($accounts as $account)
  <div class="col col-md-6 col-lg-4 mb-4">
    <div class="card wallet-card h-100" onclick="window.location='{{ route('wallet.accounts.show', $account) }}'">
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