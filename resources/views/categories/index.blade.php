@extends('core::layouts.app')

@section('title', 'Categories')

@use('Modules\Wallet\Enums\CategoryType')
@use('Modules\Wallet\Helpers\Helper')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1><i class="fas fa-tags"></i> My Category</h1>
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
    <i class="fas fa-fw fa-plus"></i>
  </button>
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

<div class="card my-2">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <h5 class="card-title">Categories</h5>
    </div>
    <span class="small ms-auto">Total {{ $categories->count() }} items.</span>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead>
          <th>Name</th>
          <th>Type</th>
          <th>Active</th>
          <th>Action</th>
        </thead>
        <tbody>
          @forelse($categories as $category)
          <tr>
            <td>
              @if($category->icon)
              <i class="{{ $category->icon }} fa-fw"></i>
              @endif
              <strong>{{ $category->name }}</strong>
            </td>
            <td class="{{ Helper::getColorCategory($category->type)}}">{{ $category->type }}</td>
            <td>
              @if($category->is_active)
              <span class="badge text-bg-success">YES</span>
              @else
              <span class="badge text-bg-danger">NO</span>
              @endif
            </td>
            <td>
              <div class="btn-group">
                <a href="{{ route('apps.categories.show', $category) }}" class="btn btn-sm btn-outline-secondary" role="button" title="View Category">
                  <i class="fas fa-fw fa-eye"></i>
                </a>
                <a href="{{ route('apps.categories.edit', $category) }}" class="btn btn-sm btn-outline-success" role="button">
                  <i class="fas fa-fw fa-pen"></i>
                </a>
                <form method="POST" action="{{ route('apps.categories.destroy', $category) }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-fw fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="3" class="text-center"><em>No category found.</em></td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>


<div class="modal fade" id="createCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('apps.categories.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label for="category-name" class="form-label">Name</label>
            <input type="text" class="form-control" name="name" id="category-name" placeholder="Input category name" required>
          </div>
          <div class="mb-3">
            <label for="category-type" class="form-label">Type</label>
            <select name="type" class="form-select" id="category-type">
              @foreach(CategoryType::cases() as $type)
              <option value="{{ $type->value }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Icon</label>
            <input type="text" class="form-control iconpicker" name="icon" placeholder="Pilih icon...">
          </div>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="category-is_active" name="is_active" value="1" checked>
              <label class="form-check-label" for="category-is_active">Active</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/vanilla-icon-picker@1.3.1/dist/icon-picker.min.js"></script>
<script>
  const iconPicker = new IconPicker('.iconpicker', {
    // Options
    theme: 'bootstrap-5',
    iconSource: [
      'FontAwesome Brands 6',
      'FontAwesome Solid 6',
      'FontAwesome Regular 6'
    ]
  });
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanilla-icon-picker@1.3.1/dist/themes/bootstrap-5.min.css">
@endpush