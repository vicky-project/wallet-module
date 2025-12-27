@extends('core::layouts.app')

@section('title', 'Categories')

@use('Modules\Wallet\Enums\CategoryType')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1><i class="fas fa-user-circle"></i> My Category</h1>
  <a href="{{ route('apps.categories.create') }}" class="btn bg-primary">
    <i class="fas fa-fw fa-plus"></i>
  </a>
</div>

<div class="accordion" id="filterWallet">
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#filterContent" aria-expanded="false" aria-controls="filterContent">
        <i class="fas fa-fw fa-filter me-2"></i>
        Filter
      </button>
    </h2>
    <div id="filterContent" class="accordion-collapse collapse" data-bs-parent="filterWallet">
      <div class="accordion-body">
        <form method="GET" action="{{ route('apps.categories.index') }}">
          <div class="row">
            <div class="col-md-12">
              <label for="filter-type" class="form-label">Type</label>
              <select name="type" class="form-select" id="filter-type">
                <option value="">All</option>
                @foreach(CategoryType::cases() as $type)
                <option value="{{ $type->value }}" @selected(request('type') == $type->value)>{{ $type->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mt-4 pt-2 border-top border-primary">
            <div class="col-md-12">
              <div class="d-flex justify-content-end">
                <button type="reset" class="btn btn-outline-secondary me-2">Reset</button>
                <button type="submit" class="btn btn-outline-success">Apply</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  @forelse ($categories as $category)
  <div class="col col-md-6 col-lg-4 mb-4">
    <div class="card wallet-card h-100" onclick="window.location='{{ route('apps.wallets.index', ['account_id' => $account->id]) }}'">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h5 class="card-title">{{$account->name}}</h5>
            <h6 class="card-subtitle mb-2 text-muted">{{ $account->type }}
              @if($account->is_default)
              <span class="position-absolute top-0 start-100 translate-middle p-2 bg-success border border-light rounded-circle">
                <span class="visually-hidden">Default</span>
              </span>
              @endif
            </h6>
          </div>
          <span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">
            {{ $account->is_active ? "Active" : "Inactive" }}
          </span>
        </div>
        <p class="card-text text-muted">{{ $account->description }}</p>
        <div class="d-flex justify-content-between align-items-center">
          <small class="text-muted">{{$account->created_at->format('d M, Y')}}</small>
          <span>{{ $account->wallets_count ?? 0}} Wallets</span>
          <div class="btn-group">
            <a href="{{ route('apps.accounts.show', $account) }}" class="btn btn-sm btn-outline-secondary" role="button" title="View Account">
              <i class="fas fa-fw fa-eye"></i>
            </a>
            <a href="{{ route('apps.accounts.edit', $account) }}" class="btn btn-sm btn-outline-success" role="button" title="Edit Account">
              <i class="fas fa-fw fa-pen"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="col col-12">
    <div class="alert alert-warning" role="alert">
      <p>You haven't created any category yet. Click the button above to create your first category.</p>
    </div>
  </div>
  @endforelse
</div>
@endsection