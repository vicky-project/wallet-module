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
    </d>

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
                                        @if($category->is_global)
                                            (Global)
                                        @endif
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
                            <small class="text-muted">{{ count($budgets) }} kategori</small>
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
                            <small class="text-muted">{{ $summary['budget_usage_percentage'] }}% dari anggaran</small>
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
                            <small class="text-muted">Belum digunakan</small>
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
                            <h6 class="text-muted mb-2">Pengeluaran Lain</h6>
                            <h4 class="mb-0">{{ $summary['formatted_unbudgeted_expenses'] }}</h4>
                            <small class="text-muted">{{ $summary['budgeted_expense_percentage'] }}% pengeluaran ter-anggaran</small>
                        </div>
                        <div class="card-icon bg-warning">
                            <i class="bi bi-exclamation-triangle text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Kesehatan Anggaran</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="progress mb-3" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: {{ $health['health_score'] }}%">
                                    {{ $health['health_score'] }}%
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-3">
                                    <div class="text-success">
                                        <h4>{{ $health['good'] }}</h4>
                                        <small>Baik</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-info">
                                        <h4>{{ $health['moderate'] }}</h4>
                                        <small>Sedang</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-warning">
                                        <h4>{{ $health['warning'] }}</h4>
                                        <small>Peringatan</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-danger">
                                        <h4>{{ $health['exceeded'] }}</h4>
                                        <small>Melebihi</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>Statistik Anggaran</h6>
                                <ul class="mb-0">
                                    <li>Total kategori dengan anggaran: {{ $health['total_budgeted'] }}</li>
                                    <li>Kategori tanpa anggaran: {{ $health['unbudgeted_categories'] }}</li>
                                    <li>Pengeluaran ter-anggaran: {{ $summary['budgeted_expense_percentage'] }}%</li>
                                    <li>Pengeluaran di luar anggaran: {{ $summary['formatted_unbudgeted_expenses'] }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Visualization -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Visualisasi Anggaran</h6>
                    <canvas id="budgetChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Performa Anggaran</h6>
                    <div class="list-group">
                        @php
                            $topBudgets = $budgets->sortByDesc('percentage')->take(5);
                        @endphp
                        @foreach($topBudgets as $budget)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $budget->category->name }}</strong>
                                        <div class="progress mt-1" style="height: 6px;">
                                            <div class="progress-bar bg-{{ $budget->status_color }}" 
                                                 style="width: {{ min($budget->percentage, 100) }}%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">{{ round($budget->percentage) }}%</small>
                                        <br>
                                        <small>{{ $budget->formatted_spent }} / {{ $budget->formatted_amount }}</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
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
                    <button type="button" class="btn btn-outline-info me-2" onclick="updateSpentAmounts()">
                        <i class="bi bi-arrow-clockwise"></i> Perbarui Terpakai
                    </button>
                    @if(Route::has('apps.budgets.export'))
                    <div class="dropdown d-inline-block me-2">
                        <button class="btn btn-outline-success dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i> Ekspor
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('apps.budgets.export', ['format' => 'pdf', 'month' => $month, 'year' => $year]) }}">
                                    <i class="bi bi-file-pdf me-2"></i> PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('apps.budgets.export', ['format' => 'excel', 'month' => $month, 'year' => $year]) }}">
                                    <i class="bi bi-file-excel me-2"></i> Excel
                                </a>
                            </li>
                        </ul>
                    </div>
                    @endif
                    <form action="{{ route('apps.budgets.create-from-suggestions') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <button type="submit" class="btn btn-outline-warning" 
                                onclick="return confirm('Buat anggaran dari saran pengeluaran bulan sebelumnya?')">
                            <i class="bi bi-magic"></i> Buat dari Saran
                        </button>
                    </form>
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
                                        <th>Periode</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($budgets as $budget)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="transaction-icon" style="background-color: {{ $budget->category->color ?? '#6b7280' }}">
                                                        <i class="bi {{ $budget->category->icon ?? 'bi-tag' }} text-white"></i>
                                                    </div>
                                                    <div>
                                                        <strong>{{ $budget->category->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            @if($budget->category->is_global)
                                                                <i class="bi bi-globe"></i> Global
                                                            @else
                                                                <i class="bi bi-person"></i> Pribadi
                                                            @endif
                                                        </small>
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
                                                @if($budget->is_active)
                                                    <span class="badge bg-success ms-1">Aktif</span>
                                                @else
                                                    <span class="badge bg-secondary ms-1">Nonaktif</span>
                                                @endif
                                                @if($budget->is_current)
                                                    <span class="badge bg-info ms-1">Bulan Ini</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $budget->period }}</small>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $budget->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('apps.budgets.edit', $budget->id) }}" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-{{ $budget->is_active ? 'warning' : 'success' }}"
                                                            onclick="toggleActive({{ $budget->id }})"
                                                            title="{{ $budget->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                        <i class="bi bi-toggle-{{ $budget->is_active ? 'on' : 'off' }}"></i>
                                                    </button>
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

function toggleActive(budgetId) {
    if (confirm('Apakah Anda yakin ingin mengubah status aktif anggaran ini?')) {
        window.location.href = "{{ route('apps.budgets.toggle-active', '') }}/" + budgetId;
    }
}
</script>
@endpush