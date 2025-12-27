@extends('core::layouts.app')

@section('title', 'Edit '.$account->name)

@use('Modules\Wallet\Enums\AccountType')

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.wallet.show', $account) }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">Edit {{ $account->name}}</h5>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('apps.wallet.update', $account) }}" class="needs-validation" novalidate>
      @csrf
      @method('PUT')
      <div class="mb-3">
        <label for="account-name" class="form-label">Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="account-name" value="{{ old('name', $account->name) }}" required>
        @error('name')
          <div class="invalid-feedback">{{$message}}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label for="account-type" class="form-label">Type</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" id="account-type">
          @foreach(AccountType::cases() as $type)
          <option value="{{$type->value}}" @selected($account->type === $type->value)>{{ str($type->value)->upper() }}</option>
          @endforeach
        </select>
        @error('type')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
      </div>
      <div class="mb-3">
        <label for="account-description" class="form-label">Description</label>
        <textarea name="description" class="form-control" id="account-description" placeholder="Describe your account detail...">{{ $account->description }}</textarea>
      </div>
      <div class="mb-3">
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" role="switch" name="is_active" id="account-is-active" @checked($account->is_active)>
          <label for="account-is-active" class="form-check-label">Active</label>
        </div>
      </div>
      <div class="pt-2 mt-4 border-top border-info">
        <button type="submit" class="btn btn-block btn-success">
          <i class="fas fa-fw fa-paper-plane"></i>
          Save
        </button>
      </div>
    </form>
  </div>
</div>
@endsection