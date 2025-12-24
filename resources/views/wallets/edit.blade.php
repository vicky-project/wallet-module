@extends('core::layouts.app')

@section('title', 'Edit '. $wallet->name)

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.wallet.show', [$account]) }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">Edit Wallet</h5>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('apps.wallet.wallets.update', [$account, $wallet]) }}" class="needs-validation" novalidate>
      @csrf
      @method('PUT')
      <div class="mb-3">
        <label for="wallet-name" class="form-label">Wallet Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="name" id="wallet-name" value="{{ old('name', $wallet->name) }}" placeholder="Enter wallet name..." required>
      </div>
      <div class="mb-3">
        <label for="wallet-initial-balance" class="form-label">Initial Balance</label>
        <input type="number" min="0" name="initial_balance" class="form-control" id="wallet-initial-balance" value="{{ old('initial_balance', $wallet->meta['initial_balance'] ?? 0) }}">
      </div>
      <div class="mb-3">
        <label for="wallet-description" class="form-label">Description</label>
        <textarea name="description" id="wallet-description" class="form-control" placeholder="Description of wallet..">{{ old('description', $wallet->description) }}</textarea>
      </div>
      <div class="pt-2 mt-4 border-top border-primary">
        <button type="submit" class="btn btn-block btn-success">
          <i class="fas fa-paper-plane"></i>
          Save
        </button>
      </div>
    </form>
  </div>
</div>
@endsection