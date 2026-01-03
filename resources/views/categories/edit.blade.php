@extends('wallet::layouts.app')

@section('title', 'Edit Kategori - ' . config('app.name', 'VickyServer'))

@use('Modules\Wallet\Enums\CategoryType')

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 text-end">
  <div>
    <a href="{{ route('apps.categories.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
  </div>
  <div>
    <h1 class="page-title mb-2">
      <i class="bi bi-pencil-square me-2"></i>Edit Kategori
    </h1>
    <p class="text-muted mb-0">Ubah informasi kategori "{{ $category->name }}"</p>
  </div>
</div>

<!-- Stats and Info -->
<div class="row mb-4">
  <div class="col-md-4 mb-3">
    <div class="card">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Total Transaksi</h6>
        <h3 class="mb-0">{{ $category->transactions()->count() }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-3">
    <div class="card">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Penggunaan Bulan Ini</h6>
        <h3 class="mb-0">Rp {{ number_format($category->getMonthlyTotal(), 0, ',', '.') }}</h3>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-3">
    <div class="card">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted">Status</h6>
        <div>
          @if($category->is_active)
            <span class="badge bg-success">Aktif</span>
          @else
            <span class="badge bg-secondary">Nonaktif</span>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Form Card -->
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <div class="card-title">
          <div class="d-flex align-items-center">
            <div class="transaction-icon me-3" style="background-color: {{ $category->type === CategoryType::INCOME ? '#10b981' : '#ef4444' }}; color: white;">
              <i class="bi {{ $category->icon_class }}"></i>
            </div>
            <div>
              <h5 class="mb-0">{{ $category->name }}</h5>
              <p class="text-muted mb-0">
                {{ $category->type === CategoryType::INCOME ? 'Pemasukan' : 'Pengeluaran' }}
                @if($category->budget_limit)
                • Batas: {{ $category->formatted_budget_limit }}
                @endif
              </p>
            </div>
          </div>
        </div>

        @if(session('success'))
          <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
          </div>
        @endif

        @if($errors->any())
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            Terdapat kesalahan dalam pengisian form. Silakan periksa kembali.
          </div>
        @endif

        @include('wallet::categories._form', ['category' => $category])

        <div class="mt-4 d-flex justify-content-between align-items-center">
          <div>
            <button type="submit" form="categoryForm" class="btn btn-primary mb-2">
              <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
            </button>
            <a href="{{ route('apps.categories.index') }}" class="btn btn-secondary mb-2">
              <i class="bi bi-x-circle me-2"></i>Batal
            </a>
          </div>

          <form action="{{ route('apps.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
              <i class="bi bi-trash me-2"></i>Hapus Kategori
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Usage Statistics -->
    @if($category->type === CategoryType::EXPENSE && $category->budget_limit)
      <div class="card mt-4">
        <div class="card-header">
          <h6 class="mb-0">
            <i class="bi bi-graph-up me-2"></i>Statistik Anggaran
          </h6>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span>Penggunaan Anggaran</span>
              <span>{{ number_format($category->budget_usage_percentage, 1) }}%</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar 
                @if($category->has_budget_exceeded) bg-danger 
                @elseif($category->budget_usage_percentage >= 80) bg-warning 
                @else bg-success @endif" 
                role="progressbar" 
                style="width: {{ min($category->budget_usage_percentage, 100) }}%">
              </div>
            </div>
            <small class="text-muted">
              Rp {{ number_format($category->getMonthlyTotal(), 0, ',', '.') }} dari {{ $category->formatted_budget_limit }}
            </small>
          </div>

          @if($category->has_budget_exceeded)
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle"></i>
              Anggaran telah melebihi batas!
            </div>
          @elseif($category->budget_usage_percentage >= 80)
            <div class="alert alert-warning">
              <i class="bi bi-exclamation-triangle"></i>
              Anggaran mendekati batas ({{ number_format($category->budget_usage_percentage, 1) }}%)
            </div>
          @endif
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
    // Update budget field based on type
    document.getElementById('type').addEventListener('change', function() {
        const budgetField = document.getElementById('budget_limit');
        const budgetGroup = budgetField.closest('.mb-3');
        
        if (this.value === 'income') {
            budgetGroup.style.opacity = '0.5';
            budgetField.disabled = true;
            budgetField.value = '';
            budgetGroup.querySelector('small').textContent = 'Batas anggaran hanya berlaku untuk kategori pengeluaran';
        } else {
            budgetGroup.style.opacity = '1';
            budgetField.disabled = false;
            budgetGroup.querySelector('small').textContent = 'Hanya untuk kategori pengeluaran. Biarkan kosong jika tidak ada batasan.';
        }
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        if (typeSelect.value === 'income') {
            const budgetField = document.getElementById('budget_limit');
            const budgetGroup = budgetField.closest('.mb-3');
            budgetGroup.style.opacity = '0.5';
            budgetField.disabled = true;
            budgetGroup.querySelector('small').textContent = 'Batas anggaran hanya berlaku untuk kategori pengeluaran';
        }
    });
</script>
@endpush