@extends('core::layouts.app')

@section('title', 'Create Transaction')

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.wallet.wallets.show', [$account, $wallet]) }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
    </div>
    <h5 class="card-title">Create Transaction</h5>
  </div>
  <div class="card-body">
    <ul class="nav nav-underline nav-justified" id="formWalletTab" role="tablist">
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
        <div class="row m-2">
          <h1>Deposit</h1>
        </div>
      </div>
      <div class="tab-pane" id="withdraw" role="tabpanel" aria-labelledby="withdraw-tab" tabindex="0">
        <div class="row m-2">
          <h1>Withdraw</h1>
        </div>
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