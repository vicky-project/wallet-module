@extends('core::layouts.app')

@section('title', 'Create Account')

@use('Modules\Wallet\Enums\AccountType')

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.accounts.index') }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">Create Account</h5>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('apps.accounts.store') }}" class="row g-3 needs-validation" novalidate>
      @csrf
      <input type="hidden" name="user_id" value="{{ \Auth::id() }}">
      <div class="col-md-4">
        <label for="account-name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="account-name" placeholder="Enter account name..." value="{{ old('name') }}" required>
        @error('name')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
      </div>
      <div class="col-md-4">
        <label for="account_number" class="form-label">Account Number</label>
        <input type="text" class="form-control" name="account_number" id="account_number" placeholder="Enter number or numeric..." value="{{ old('account_number') }}">
      </div>
      <div class="col-md-4">
        <label for="account-type" class="form-label">Type</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" id="account-type">
          @foreach(AccountType::cases() as $type)
          <option value="{{$type->value}}" @selected(old('type') === $type->value)>{{ str($type->value)->upper() }}</option>
          @endforeach
        </select>
        @error('type')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
      </div>
      <div class="col-md-4">
        <label for="account-description" class="form-label">Description</label>
        <textarea name="description" class="form-control" id="account-description" placeholder="Describe your account detail...">{{ old('description') }}</textarea>
      </div>
      <div class="col-md-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="account-is_default" name="is_default" value="1" checked>
          <label class="form-check-label" for="account-is_default">Default</label>
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