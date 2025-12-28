@extends('core::layouts.app')

@section('title', 'Create Transaction')

@use('Modules\Wallet\Helpers\Helper')

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.transactions.index') }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">Create Transaction</h5>
    <span class="small ms-auto text-muted">{{ $wallet->name }}</span>
  </div>
  <div class="card-body">
    <ul class="nav nav-tabs justify-content-'center" id="formTransactionTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button type="button" class="nav-link active" id="deposit-tab" data-bs-toggle="tab" data-bs-target="#deposit" role="tab" aria-controls="deposit" aria-selected="true">Deposit</button>
      </li>
      <li class="nav-item" role="presentation">
        <button type="button" class="nav-link" id="withdraw-tab" data-bs-toggle="tab" data-bs-target="#withdraw" role="tab" aria-controls="withdraw" aria-selected="false">Withdraw</button>
      </li>
      <li class="nav-item" role="presentation">
        <button type="button" class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" role="tab" aria-controls="upload" aria-selected="false">Upload</button>
      </li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane active" id="deposit" role="tabpanel" aria-labelledby="deposit-tab" tabindex="0">
        <h5 class="card-text my-2 text-center text-bold">Deposit</h5>
        <form method="POST" action="{{ route('apps.wallets.deposit', $wallet) }}" class="row mt-2">
          @csrf
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="deposit-category" class="form-label">Category (<small class="small text-muted"><a href="{{ route('apps.categories.index') }}" class="btn-link">create category</a></small>)</label>
              <select name="deposit-category" class="form-select" id="deposit-category">
                @forelse($depositCategories as $category)
                <option value="{{ $category->name}}" @selected(old('deposit-category') == $category->name)>
                  @if($category->icon)
                  <i class="{{ $category->icon }}"></i>
                  @endif
                  <span class="{{ Helper::getColorCategory($category->type) }}">
                  {{ $category->name}}
                  </span>
                </option>
                @empty
                <option value="">No category available.</option>
                @endforelse
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="deposit-amount" class="form-label">Amount</label>
              <input type="number" class="form-control" name="deposit-amount" min="0" value="{{ old('deposit-amount', 0)}}" id="deposit-amount" placeholder="How many
            ..">
            </div>
            <div class="col-md-4 mb-3">
              <label for="deposit-date-at" class="form-label">Date</label>
              <input type="datetime-local" class="form-control" name="deposit-date_at" id="deposit-date-at" placeholder="d-m-Y H:i:s" value="{{ old('deposit-date_at') }}">
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="deposit-description" class="form-label">Description</label>
              <textarea class="form-control" id="deposit-description" name="deposit-description" placeholder="What is name...">{{ old('deposit-description') }}</textarea>
            </div>
          </div>
          <div class="pt-2 mt-4 border-top border-primary">
            <button type="submit" class="btn btn-block btn-success">
              <i class="fas fa-save"></i>
              Save
            </button>
          </div>
        </form>
      </div>
      <div class="tab-pane" id="withdraw" role="tabpanel" aria-labelledby="withdraw-tab" tabindex="0">
        <h5 class="card-text my-2 text-center text-bold">Withdraw</h5>
        <form method="POST" action="{{ route('apps.wallets.withdraw', $wallet) }}" class="row mt-2">
          @csrf
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="withdraw-category" class="form-label">Category (<small class="small text-muted"><a href="{{ route('apps.categories.index') }}" class="btn-link">create category</a></small>)</label>
              <select name="withdraw-category" class="form-select" id="withdraw-category">
                @forelse($withdrawCategories as $category)
                <option value="{{ $category->name}}" @selected(old('withdraw-category') == $category->name)>
                  @if($category->icon)
                  <i class="{{ $category->icon }}"></i>
                  @endif
                  <span class="{{ Helper::getColorCategory($category) }}">
                    {{ $category->name }}
                  </span>
                </option>
                @empty
                <option value="">No category available.</option>
                @endforelse
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="withdraw-amount" class="form-label">Amount</label>
              <input type="number" class="form-control" name="withdraw-amount" id="withdraw-amount" min="0" value="{{ old('withdraw-amount', 0) }}" placeholder="How many...">
            </div>
            <div class="col-md-4 mb-3">
              <label for="withdraw-date-at" class="form-label">Date</label>
              <input type="datetime-local" class="form-control" name="withdraw-date_at" id="withdraw-date-at" placeholder="d-m-Y H:i:s" value="{{ old('withdraw-date_at') }}">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label for="withdraw-description" class="form-label">Description</label>
              <textarea class="form-control" name="withdraw-description" id="withdraw-description" placeholder="What is name...">{{ old('withdraw-description') }}</textarea>
            </div>
          </div>
          <div class="pt-2 mt-4 border-top border-primary">
            <button type="submit" class="btn btn-block btn-success">
              <i class="fas fa-save"></i>
              Save
            </button>
          </div>
        </form>
      </div>
      <div class="tab-pane" id="upload" role="tabpanel" aria-labelledby="upload-tab" tabindex="0">
        <div class="row m-2">
          <h1>Upload</h1>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection