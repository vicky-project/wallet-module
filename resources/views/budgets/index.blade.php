@extends('wallet::layouts.app')

@section('title', 'Anggaran - ' . config('app.name'))

@section('content')
@include('wallet::partials.fab')
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title">
                <h1>Anggaran</h1>
                <p class="text-muted">Kelola anggaran pengeluaran bulanan Anda</p>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('apps.budgets.index') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="month" class="form-label">Bulan</label>
                            <select name="month" id="month" class="form-select">
                                @foreach(\Modules\Wallet\Models\Budget::MONTH_NAMES as $key => $name)
                                    <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label">Tahun</label>
                            <input type="number" name="year" id="year" class="form-control" 
                                   min="2020" max="{{ date('Y') + 5 }}" value="{{ $year }}">
                        </div>
                        <div class="col-md-4">
                            <label for="category_id" class="form-label">Kategori</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                        {{ $categoryId == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Anggaran</h6>
                            <h4 class="mb-0">{{ $summary['formatted_total_budget'] }}</h4>
                        </div>
                        <div class="card-icon bg-success">
                            <i class="bi bi-wallet2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Terpakai</h6>
                            <h4 class="mb-0">{{ $summary['formatted_total_spent'] }}</h4>
                        </div>
                        <div class="card-icon bg-info">
                            <i class="bi bi-currency-dollar text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Sisa</h6>
                            <h4 class="mb-0">{{ $summary['formatted_total_remaining'] }}</h4>
                        </div>
                        <div class="card-icon bg-primary">
                            <i class="bi bi-piggy-bank text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Penggunaan</h6>
                            <h4 class="mb-0">{{ $summary['budget_usage_percentage'] }}%</h4>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-{{ $summary['budget_usage_percentage'] >= 100 ? 'danger' : ($summary['budget_usage_percentage'] >= 80 ? 'warning' : 'success') }}" 
                                     style="width: {{ min($summary['budget_usage_percentage'], 100) }}%">
                                </div>
                            </div>
                        </div>
                        <div class="card-icon bg-warning">
                            <i class="bi bi-percent text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Daftar Anggaran</h5>
                    <small class="text-muted">{{ \Modules\Wallet\Models\Budget::MONTH_NAMES[$month] }} {{ $year }}</small>
                </div>
                <div>
                    <a href="{{ route('apps.budgets.create') }}" class="btn btn-primary me-2">
                        <i class="bi bi-plus-circle"></i> Tambah Anggaran
                    </a>
                    <a href="{{ route('apps.budgets.index', ['month' => date('m'), 'year' => date('Y')]) }}" 
                       class="btn btn-outline-secondary me-2">
                        <i class="bi bi-calendar-month"></i> Bulan Ini
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="updateSpentAmounts()">
                        <i class="bi bi-arrow-clockwise"></i> Perbarui Terpakai
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Budgets Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($budgets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th>Anggaran</th>
                                        <th>Terpakai</th>
                                        <th>Sisa</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($budgets as $budget)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="transaction-icon bg-{{ $budget->category->color ?? 'primary' }} me-3">
                                                        <i class="bi {{ $budget->category->icon ?? 'bi-tag' }} text-white"></i>
                                                    </div>
                                                    <div>
                                                        <strong>{{ $budget->category->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $budget->period }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong>{{ $budget->formatted_amount }}</strong>
                                            </td>
                                            <td>
                                                <span class="{{ $budget->is_exceeded ? 'text-danger' : '' }}">
                                                    {{ $budget->formatted_spent }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="{{ $budget->remaining <= 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ $budget->formatted_remaining }}
                                                </strong>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-{{ $budget->status_color }}" 
                                                         role="progressbar" 
                                                         style="width: {{ min($budget->percentage, 100) }}%;"
                                                         aria-valuenow="{{ $budget->percentage }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        {{ round($budget->percentage) }}%
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    {{ $budget->formatted_daily_budget }}/hari
                                                </small>
                                            </td>
                                            <td>
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
                                                @if($budget->is_current)
                                                    <span class="badge bg-info ms-1">Bulan Ini</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('apps.budgets.edit', $budget->id) }}" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('apps.budgets.destroy', $budget->id) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Apakah Anda yakin ingin menghapus anggaran ini?')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-calendar-x" style="font-size: 48px; color: #6c757d;"></i>
                            </div>
                            <h5>Belum ada anggaran</h5>
                            <p class="text-muted">Mulai tambahkan anggaran untuk mengelola pengeluaran Anda.</p>
                            <a href="{{ route('apps.budgets.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Tambah Anggaran Pertama
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function updateSpentAmounts() {
  if (confirm('Perbarui jumlah terpakai dari transaksi?')) {
    window.location.href = "{{ route('apps.budgets.update-spent') }}";
  }
}
</script>
@endpush