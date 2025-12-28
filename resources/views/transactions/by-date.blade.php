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
    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <th>Date</th>
          <th>Name</th>
          <th>Amount</th>
        </thead>
        <tbody>
          @foreach($byDate as $item)
          <tr>
            <td>{{ $item->transaction_date->format('d-m-Y H:i:s') }}</td>
            <td>
              @if($item->isDeposit())
              @elseif($item->isWithdraw())
              @else
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection