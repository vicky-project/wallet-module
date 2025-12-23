@extends('core::layouts.app')

@section('title', 'Create Account')

@use('Modules\Wallet\Enums\AccountType')

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.wallet.index') }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">Create Wallet</h5>
  </div>
  <div class="card-body">
    <form method="POST" action="" class="row g-3 needs-validation" novalidate>
      @csrf
      <input type="hidden" name="user_id" value="{{ \Auth::id() }}">
      <div class="col-md-4">
        <label for="account-name" class="form-label">Name</label>
        <input type="text" class="form-control" name="name" id="account-name" required>
      </div>
      <div class="col-md-4">
        <label for="account-type" class="form-label">Type</label>
        <select name="type" class="form-select" id="account-type">
          @foreach(AccountType->cases() as $type)
          <option value="{{$type->value}}">{{ $type->value}}</option>
          @endforeach
        </select>
      </div>
    </form>
  </div>
</div>
@endsection