<!-- resources/views/wallet/categories/index.blade.php -->
@extends('wallet::layouts.app')

@section('content')
@include('wallet::partials.fab')
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Total Kategori</h5>
                    <h2 class="card-text">{{ $stats['total'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Kategori Aktif</h5>
                    <h2 class="card-text">{{ $stats['active'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Pemasukan</h5>
                    <h2 class="card-text">{{ $stats['income'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Pengeluaran</h5>
                    <h2 class="card-text">{{ $stats['expense'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Warnings Alert -->
    @if($budgetWarnings->count() > 0)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5><i class="fas fa-exclamation-triangle"></i> Peringatan Budget!</h5>
        <p>{{ $budgetWarnings->count() }} kategori telah melebihi atau mendekati limit budget.</p>
        <ul class="mb-0">
            @foreach($budgetWarnings->take(3) as $warning)
            <li>{{ $warning['category']->name }} - {{ number_format($warning['usage_percentage'], 1) }}%</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Categories Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Kategori</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                <i class="fas fa-plus"></i> Tambah Kategori
            </button>
        </div>
        <div class="card-body">
            <!-- Search and Filter -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Cari kategori..." id="searchInput">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="typeFilter">
                        <option value="">Semua Tipe</option>
                        <option value="income">Pemasukan</option>
                        <option value="expense">Pengeluaran</option>
                    </select>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Nama</th>
                            <th>Tipe</th>
                            <th>Deskripsi</th>
                            <th>Total Bulan Ini</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td>
                                <input type="checkbox" class="category-checkbox" value="{{ $category->id }}">
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-{{ $category->icon }} me-2"></i>
                                    {{ $category->name }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $category->type === 'income' ? 'success' : 'danger' }}">
                                    {{ $category->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                </span>
                            </td>
                            <td>{{ Str::limit($category->description, 50) }}</td>
                            <td>
                                @if($category->type === 'expense')
                                <div class="d-flex flex-column">
                                    <span class="text-{{ $category->has_budget_exceeded ?? false ? 'danger' : 'dark' }}">
                                        Rp {{ number_format($category->monthly_total ?? 0, 0, ',', '.') }}
                                    </span>
                                    @if($category->budget_usage_percentage ?? 0 > 0)
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-{{ $category->has_budget_exceeded ?? false ? 'danger' : 'info' }}" 
                                             style="width: {{ min($category->budget_usage_percentage, 100) }}%">
                                        </div>
                                    </div>
                                    <small>{{ number_format($category->budget_usage_percentage, 1) }}%</small>
                                    @endif
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">
                                    {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editCategoryModal"
                                            data-id="{{ $category->id }}"
                                            data-name="{{ $category->name }}"
                                            data-type="{{ $category->type }}"
                                            data-description="{{ $category->description }}"
                                            data-icon="{{ $category->icon }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-category" 
                                            data-id="{{ $category->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <button class="btn btn-sm btn-secondary bulk-action" data-action="activate">
                        Aktifkan
                    </button>
                    <button class="btn btn-sm btn-secondary bulk-action" data-action="deactivate">
                        Nonaktifkan
                    </button>
                    <button class="btn btn-sm btn-danger bulk-action" data-action="delete">
                        Hapus
                    </button>
                </div>
                {{ $categories->links() }}
            </div>
        </div>
    </div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createCategoryForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Form fields -->
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe</label>
                        <select class="form-control" name="type" required>
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCategoryForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Form fields will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Create category form
    $('#createCategoryForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '{{ route("wallet.categories.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#createCategoryModal').modal('hide');
                    location.reload();
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON.message || 'Terjadi kesalahan');
            }
        });
    });

    // Edit category modal
    $('#editCategoryModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var modal = $(this);
        
        modal.find('.modal-body').html(`
            <div class="mb-3">
                <label class="form-label">Nama Kategori</label>
                <input type="text" class="form-control" name="name" value="${button.data('name')}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tipe</label>
                <select class="form-control" name="type" required>
                    <option value="expense" ${button.data('type') === 'expense' ? 'selected' : ''}>Pengeluaran</option>
                    <option value="income" ${button.data('type') === 'income' ? 'selected' : ''}>Pemasukan</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi (Opsional)</label>
                <textarea class="form-control" name="description" rows="3">${button.data('description') || ''}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Ikon (Opsional)</label>
                <input type="text" class="form-control" name="icon" value="${button.data('icon') || ''}" placeholder="fa-shopping-cart">
                <small class="text-muted">Nama ikon dari FontAwesome</small>
            </div>
        `);
        
        modal.find('form').attr('action', `/wallet/categories/${button.data('id')}`);
    });

    // Edit category form
    $('#editCategoryForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#editCategoryModal').modal('hide');
                    location.reload();
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON.message || 'Terjadi kesalahan');
            }
        });
    });

    // Delete category
    $('.delete-category').click(function() {
        if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
            var categoryId = $(this).data('id');
            
            $.ajax({
                url: `/wallet/categories/${categoryId}`,
                method: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON.message || 'Terjadi kesalahan');
                }
            });
        }
    });

    // Bulk actions
    $('.bulk-action').click(function() {
        var selectedIds = [];
        $('.category-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Pilih minimal satu kategori');
            return;
        }

        var action = $(this).data('action');
        
        if (action === 'delete') {
            if (!confirm(`Apakah Anda yakin ingin menghapus ${selectedIds.length} kategori?`)) {
                return;
            }
        }

        $.ajax({
            url: '{{ route("wallet.categories.bulk-update") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                category_ids: selectedIds,
                action: action
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON.message || 'Terjadi kesalahan');
            }
        });
    });

    // Select all checkbox
    $('#selectAll').change(function() {
        $('.category-checkbox').prop('checked', $(this).prop('checked'));
    });
});
</script>
@endpush