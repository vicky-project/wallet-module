@extends('core::layouts.app')

@section('title', 'Semua Kategori')

@use('Modules\Wallet\Enums\CategoryType')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold" style="color: var(--tg-theme-text-color);">
        <i class="bi bi-tags me-2" style="color: var(--tg-theme-accent-text-color);"></i>Semua Kategori
      </h4>
      <a href="{{ route('apps.categories.create') }}" class="btn btn-sm" style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none;">
        <i class="bi bi-plus-circle me-1"></i>Tambah
      </a>
    </div>

    <!-- Statistik Kategori -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
          <div class="card-body p-3">
            <div class="d-flex align-items-center">
              <div class="rounded-circle p-2 me-2" style="background-color: rgba(64, 167, 227, 0.1); color: #40a7e3;">
                <i class="bi bi-tags"></i>
              </div>
              <div>
                <small style="color: var(--tg-theme-hint-color);">Total Kategori</small>
                <h5 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">{{ $stats['total'] }}</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
          <div class="card-body p-3">
            <div class="d-flex align-items-center">
              <div class="rounded-circle p-2 me-2" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="bi bi-arrow-down-circle"></i>
              </div>
              <div>
                <small style="color: var(--tg-theme-hint-color);">Pengeluaran</small>
                <h5 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">{{ $stats['expense'] }}</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
          <div class="card-body p-3">
            <div class="d-flex align-items-center">
              <div class="rounded-circle p-2 me-2" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="bi bi-arrow-up-circle"></i>
              </div>
              <div>
                <small style="color: var(--tg-theme-hint-color):">Pemasukan</small>
                <h5 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">{{ $stats['income'] }}</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
          <div class="card-body p-3">
            <div class="d-flex align-items-center">
              <div class="rounded-circle p-2 me-2" style="background-color: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <i class="bi bi-pie-chart"></i>
              </div>
              <div>
                <small style="color: var(--tg-theme-hint-color);">Anggaran</small>
                <h5 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">{{ $stats['with_budget'] }}</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabel Kategori -->
    <div class="card border-0 shadow-sm" style="background-color: var(--tg-theme-secondary-bg-color);">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="color: var(--tg-theme-text-color);">
            <thead style="background-color: var(--tg-theme-bg-color); border-bottom: 2px solid var(--tg-theme-section-separator-color);">
              <tr>
                <th class="ps-4">Nama</th>
                <th>Tipe</th>
                <th>Ikon</th>
                <th>Deskripsi</th>
                <th>Jumlah Transaksi</th>
                <th>Anggaran</th>
                <th class="text-end pe-4">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($categories as $category)
                <tr style="border-bottom: 1px solid var(--tg-theme-section-separator-color);">
                  <td class="ps-4">
                    <div class="d-flex align-items-center">
                      <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: {{ $category->type == CategoryType::EXPENSE ? '#ef444420' : '#10b98120' }}; color: {{ $category->type == CategoryType::EXPENSE ? '#ef4444' : '#10b981' }};">
                        <i class="bi {{ $category->icon ?? ($category->type == CategoryType::EXPENSE ? 'bi-arrow-down' : 'bi-arrow-up') }}"></i>
                      </div>
                      <span style="color: var(--tg-theme-text-color);">{{ $category->name }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="badge {{ $category->type == CategoryType::EXPENSE ? 'bg-danger' : 'bg-success' }}">{{ ucfirst($category->type->value) }}</span>
                  </td>
                  <td><code>{{ $category->icon ?? '-' }}</code></td>
                  <td>
                    <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $category->description }}">{{ $category->description ?? '-' }}</span>
                  </td>
                  <td>{{ $category->transactions_count }}</td>
                  <td>
                    @if($category->is_budgetable)
                      <span class="badge bg-warning">Ya</span>
                    @else
                      <span class="badge bg-secondary">Tidak</span>
                    @endif
                  </td>
                  <td class="text-end pe-4">
                    <div class="d-flex gap-1 justify-content-end">
                      <a href="{{ route('apps.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 32px; height: 32px;" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" style="width: 32px; height: 32px; padding: 0;" onclick="showDeleteModal({{ $category->id }}, '{{ $category->name }}')" title="Hapus">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-5" style="color: var(--tg-theme-hint-color);">
                    <i class="bi bi-tags display-4"></i>
                    <p class="mt-3">Belum ada kategori. <a href="{{ route('apps.categories.create') }}" style="color: var(--tg-theme-button-color);">Tambah sekarang</a></p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex d-flex-column flex-md-row justify-content-center mt-4 gap-3">
      @if($categories->total() > 0)
        <div style="color: var(--tg-theme-hint-color);font-size: 0.9rem;">
          Menampilkan {{ $categories->firstItem() }} - {{ $categories->lastItem() }} dari {{ $categories->total() }} data.
        </div>
      @else
        <div></div>
      @endif
      <div>
        {{ $categories->links() }}
      </div>
    </div>

    <!-- Tombol Kembali -->
    <div class="mt-4">
      <a href="{{ route('apps.financial') }}" class="btn px-4 py-2" style="background-color: transparent; color: var(--tg-theme-button-color); border: 1px solid var(--tg-theme-button-color);">
        <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
      </a>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--tg-theme-section-bg-color); border: none;">
            <div class="modal-header" style="border-bottom-color: var(--tg-theme-section-separator-color);">
                <h5 class="modal-title" id="deleteModalLabel" style="color: var(--tg-theme-text-color);">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body" style="color: var(--tg-theme-text-color);">
                Apakah Anda yakin ingin menghapus kategori <strong id="deleteCategoryName"></strong>? Semua transaksi terkait akan ikut terhapus.
            </div>
            <div class="modal-footer" style="border-top-color: var(--tg-theme-section-separator-color);">
                <button type="button" class="btn" style="background-color: transparent; color: var(--tg-theme-button-color); border: 1px solid var(--tg-theme-button-color);" data-bs-dismiss="modal">Batal</button>
                <form id="delete-form" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn" style="background-color: var(--tg-theme-destructive-text-color); color: white; border: none;">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table {
        --bs-table-bg: transparent;
        --bs-table-hover-bg: rgba(var(--tg-theme-button-color-rgb), 0.5);
    }
    .table th {
        font-weight: 600;
        color: var(--tg-theme-text-color);
        border-bottom-color: var(--tg-theme-section-separator-color);
    }
    .table td {
        border-bottom-color: var(--tg-theme-section-separator-color);
    }
    .btn-outline-secondary {
        border-width: 1px;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    .btn-outline-secondary:hover {
        opacity: 1;
        background-color: var(--tg-theme-button-color);
        border-color: var(--tg-theme-button-color);
        color: var(--tg-theme-button-text-color) !important;
    }
    .btn-outline-danger:hover {
        background-color: var(--tg-theme-destructive-text-color);
        border-color: var(--tg-theme-destructive-text-color);
        color: white !important;
    }
    .pagination {
        --bs-pagination-bg: var(--tg-theme-secondary-bg-color);
        --bs-pagination-color: var(--tg-theme-text-color);
        --bs-pagination-border-color: var(--tg-theme-hint-color);
        --bs-pagination-hover-bg: var(--tg-theme-button-color);
        --bs-pagination-hover-color: var(--tg-theme-button-text-color);
        --bs-pagination-hover-border-color: var(--tg-theme-button-color);
        --bs-pagination-active-bg: var(--tg-theme-button-color);
        --bs-pagination-active-border-color: var(--tg-theme-button-color);
        --bs-pagination-disabled-bg: var(--tg-theme-secondary-bg-color);
        --bs-pagination-disabled-color: var(--tg-theme-hint-color);
    }
    .modal-content {
        border-radius: 16px;
    }
</style>
@endpush

@push('scripts')
<script>
    let deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    function showDeleteModal(categoryId, categoryName) {
        document.getElementById('deleteCategoryName').textContent = categoryName;
        const deleteForm = document.getElementById('delete-form');
        deleteForm.action = `{{ url('apps/categories') }}/${categoryId}`;
        deleteModal.show();
    }
</script>
@endpush