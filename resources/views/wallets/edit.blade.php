@extends('core::layouts.app')

@section('title', 'Edit '. $wallet->name)

@use('Modules\Wallet\Enums\WalletType')

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.wallets.index') }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">Edit Wallet</h5>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('apps.wallets.update', $wallet) }}" class="needs-validation" novalidate>
      @csrf
      @method('PUT')
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="wallet-account_id" class="form-label">Account</label>
          <select name="account_id" class="form-select" id="wallet-account_id">
            @foreach($accounts as $account)
            <option value="{{ $account->id }}" @selected($account->id == $wallet->account_id)>{{ $account->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="wallet-name" class="form-label">Wallet Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="name" id="wallet-name" value="{{ old('name', $wallet->name) }}" placeholder="Enter wallet name..." required>
        </div>
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label for="wallet-type" class="form-label">Type</label>
          <select name="type" class="form-select" id="wallet-type">
            @foreach(WalletType::cases() as $type)
            <option value="{{ $type->value }}" @selected($type === $wallet->type)>{{ $type->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4 mb-3">
          <label for="wallet-initial-balance" class="form-label">Initial Balance</label>
          <input type="number" min="0" name="initial_balance" class="form-control" id="wallet-initial-balance" value="{{ old('initial_balance', $wallet->initial_balance->getMinorAmount()->toInt()) }}">
        </div>
        <div class="col-md-4 mb-3">
          <label for="account-currency" class="form-label">Currency</label>
          <select class="form-select" name="currency" id="account-currency">
            @foreach($currencies as $currency => $name)
            <option value="{{$currency}}" @selected($currency === ($wallet->currency ?? 'IDR'))>{{$name}}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12 mb-3">
          <label for="wallet-description" class="form-label">Description</label>
          <textarea name="description" id="wallet-description" class="form-control" placeholder="Description of wallet..">{{ old('description', $wallet->description) }}</textarea>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="wallet-default" name="is_default" value="1" @checked($wallet->is_default)>
            <label class="form-check-label" for="wallet-default">Default</label>
          </div>
        </div>
      </div>
      <div class="pt-2 mt-4 border-top border-primary text-end">
        <button type="submit" class="btn btn-block btn-success">
          <i class="fas fa-paper-plane"></i>
          Save
        </button>
      </div>
    </form>
  </div>
</div>
@endsection