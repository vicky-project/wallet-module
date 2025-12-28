@extends('core::layouts.app')

@section('title', $date)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <a href="{{ route('apps.transactions.index') }}" class="btn btn-secondary" role="button" title="Back">
      <i class="fas fa-arrow-left"></i>
    </a>
  </div>
  <div></div>
</div>

<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <h5 class="card-title">{{ $date }}</h5>
    </div>
    <div class="btn-group">
      <a href="{{ route('apps.transactions.create') }}" class="btn btn-success" role="button">
        <i class="fas fa-plus"></i>
      </a>
    </div>
  </div>
  <div class="card-body">
    <h1>List Transactions</h1>
  </div>
</div>
@endsection