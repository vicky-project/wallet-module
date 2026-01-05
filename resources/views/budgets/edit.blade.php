@extends('wallet::layouts.app')

@section('title', 'Edit Anggaran - ' . config('app.name'))

@section('content')
@include('wallet::partials.fab')
<div class="row mb-4">
  <div class="col-12">
    <div class="page-title">
      <h1>Edit Anggaran</h1>
      <p class="text-muted">Perbarui informasi anggaran</p>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 mx-auto">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('apps.budgets.update', $budget) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="category_id" class="form-label">Kategori *</label>
            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id',$budget->category_id) == $category->id)>
                  {{ $category->name }}
                </option>
              @endforeach
            </select>
            @error('category_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="amount" class="form-label">Jumlah Anggaran *</label>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $budget->amount->getMinorAmount()->toInt()) }}" placeholder="0" min="1" required>
            </div>
            @error('amount')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="month" class="form-label">Bulan *</label>
                <select name="month" id="month" class="form-select @error('month') is-invalid @enderror" required>
                  @foreach(\Modules\Wallet\Models\Budget::MONTH_NAMES as $key => $name)
                    <option value="{{ $key }}" @selected(old('month', $budget->month) == $key)>
                      {{ $name }}
                    </option>
                  @endforeach
                </select>
                @error('month')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="year" class="form-label">Tahun *</label>
                <input type="number" name="year" id="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', $budget->year) }}" min="2020" max="{{ date('Y') + 5 }}" required>
                @error('year')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          <div class="alert alert-info">
            <div class="d-flex">
              <i class="bi bi-info-circle me-2"></i>
              <div>
                <strong>Informasi Anggaran</strong>
                <ul class="mb-0 mt-1">
                  <li>Jumlah Terpakai: {{ $budget->formatted_spent }}</li>
                  <li>Sisa: {{ $budget->formatted_remaining }}</li>
                  <li>Progress: {{ round($budget->percentage) }}%</li>
                  <li>Status: 
                    <span class="badge bg-{{ $budget->status_color }}">
                      @if($budget->status == 'exceeded')
                        Melebihi
                      @elseif($budget->status == 'warning')
                        Peringatan
                      @elseif($budget->status == 'moderate')
                        Sedang
                      @else
                        Baik
                      @endif
                    </span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('apps.budgets.index') }}" class="btn btn-secondary">
              <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <div>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Perbarui
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection