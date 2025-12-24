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
    <h5 class="card-title">Create Account</h5>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('apps.wallet.store') }}" class="row g-3 needs-validation" novalidate>
      @csrf
      <input type="hidden" name="user_id" value="{{ \Auth::id() }}">
      <div class="col-md-4">
        <label for="account-name" class="form-label">Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="account-name" required>
        @error('name')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
      </div>
      <div class="col-md-4">
        <label for="account-type" class="form-label">Type</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" id="account-type">
          @foreach(AccountType::cases() as $type)
          <option value="{{$type->value}}">{{ str($type->value)->upper() }}</option>
          @endforeach
        </select>
        @error('type')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
      </div>
      <div class="col-md-4">
        <label for="account-description" class="form-label">Description</label>
        <textarea name="description" class="form-control" id="account-description" placeholder="Describe your account detail..."></textarea>
      </div>
      <div class="col-md-4">
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" role="switch" name="is_active" id="account-is-active" checked>
          <label for="account-is-active" class="form-check-label">Active</label>
        </div>
      </div>
      <div class="pt-2 mt-4 border-top border-primary">
        <button type="submit" class="btn btn-success">
          <i class="fas fa-paper-plane"></i>&nbsp; Save
        </button>
      </div>
    </form>
  </div>
</div>
@endsection