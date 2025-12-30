@extends('core::layouts.app')

@section('title', $date)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <a href="{{ route('apps.transactions.index') }}" class="btn btn-secondary" role="button" title="Back">
      <i class="fas fa-arrow-left"></i>
    </a>
  </div>
  <div>
    <a href="{{ route('apps.transactions.create') }}" class="btn btn-success" role="button">
      <i class="fas fa-plus"></i>
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <h5 class="card-title">{{ $date }}</h5>
    </div>
    <div class="btn-group">
      <a href="{{ route('apps.transactions.trash') }}" class="btn btn-outline-danger" role="button"><i class="fas fa-trash-alt"></i></a>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <th>Date</th>
          <th>Name</th>
          <th>Amount</th>
          <th>Action</th>
        </thead>
        <tbody>
          @foreach($byDate as $item)
          <tr>
            <td>
              <strong>
                {{ $item->transaction_date->format('d-m-Y') }}
              </strong><br />
              <small class="small text-muted">{{ $item->transaction_date->format('H:i:s') }}</small>
            </td>
            <td>{{ $item->description }}</td>
            <td>
              @if($item->isDeposit())
              <span class="badge text-bg-success">{{ $item->amount }}</span>
              @elseif($item->isWithdraw())
              <span class="badge text-bg-danger">{{ $item->amount }}</span>
              @else
              <span class="badge text-bg-secondary">{{ $item->amount }}</span>
              @endif
            </td>
            <td>
              <div class="btn-group">
                <a href="{{ route('apps.transactions.show', $item) }}" class="btn btn-sm btn-secondary" role="button"><i class="fas fa-eye"></i></a>
                <a href="{{ route('apps.transactions.edit', $item) }}" class="btn btn-sm btn-success" role="button"><i class="fas fa-pen"></i></a>
                <a href="{{ route('apps.transactions.destroy', $item) }}" class="btn btn-sm btn-danger" role="button"><i class="fas fa-trash"></i></a>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection