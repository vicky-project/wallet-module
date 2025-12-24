@extends('core::layouts.app')

@section('title', $wallet->name)

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <a href="{{ route('apps.wallet.show', $account) }}" class="btn btn-secondary" role="button">
        <i class="fas fa-arrow-left"></i>
      </a>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTransactionModal">
        <i class="fas fa-plus"></i>
      </button>
    </div>
    <h5 class="card-title"></h5>
  </div>
  <div class="card-body"></div>
</div>

<div class="modal fade" id="createTransactionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Transaction</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <form method="POST" action="{{ route('apps.') }}">
        <div class="modal-body"></div>
        <div class="modal-footer"></div>
      </form>
    </div>
  </div>
</div>
@endsection