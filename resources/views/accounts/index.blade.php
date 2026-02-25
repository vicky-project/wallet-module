@extends('core::layouts.app')

@section('title', 'Semua Akun')

@section('content')
<div class="row">
  <div class="col-12">
    <!-- Header dengan judul dan tombol tambah -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold" style="color: var(--tg-theme-text-color);">
        <i class="bi bi-wallet2 me-2" style="color: var(--tg-theme-accent-text-color);"></i>Semua Akun
      </h4>
      <a href="{{ route('apps.accounts.create') }}" class="btn btn-sm" style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none;">
        <i class="bi bi-plus-circle me-1"></i>Tambah
      </a>
    </div>

    <!-- Statistik Akun -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
          <div class="card-body p-3">
            <div class="d-flex align-items-center">
              <div class="rounded-circle p-2 me-2" style="background-color: rgba(64, 167, 227, 0.1); color: #40a7e3;">
                <i class="bi bi-wallet2"></i>
              </div>
              <div>
                <small style="color: var(--tg-theme-hint-color);">Total Akun</small>
                <h5 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">{{ $stats['total_accounts'] }}</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="background-color: var(--tg-theme-secondary-bg-color);">
          <div class="card-body p-3">
            <div class="d-flex align-items-center">
              <div class="rounded-circle p-2 me-2" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="bi bi-cash-stack"></i>
              </div>
              <div>
                <small style="color: var(--tg-theme-hint-color);">Total Saldo</small>
                <h5 class="mb-0 fw-bold" style="color: var(--tg-theme-text-color);">{{ $stats['formatted_total_balance'] }}</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Grid Daftar Akun -->
    <div class="row g-3">
      @forelse($accounts as $account)
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm h-100 position-relative" style="background-color: var(--tg-theme-secondary-bg-color); transition: transform 0.2s, box-shadow 0.2s;">
            <!-- Tombol Aksi (Edit & Hapus) -->
            <div class="position-absolute top-0 end-0 p-2 d-flex gap-1">
              <a href="{{ route('apps.accounts.edit', $account) }}" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 32px; height: 32px; background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
              <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" style="width: 32px; height: 32px; padding: 0;" onclick="showDeleteModal({{ $account->id }}, '{{ $account->name }}')" title="Hapus">
                <i class="bi bi-trash"></i>
              </button>
            </div>

            <!-- Konten Akun (dapat diklik ke detail) -->
            <a href="{{ route('apps.accounts.show', $account) }}" class="text-decoration-none d-block p-3">
              <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; background-color: {{ $account->color }}20; color: {{ $account->color }};">
                  <i class="bi {{ $account->icon }} fs-5"></i>
                </div>
                <div class="flex-grow-1" style="min-width: 0;">
                  <h6 class="mb-0 text-truncate" style="color: var(--tg-theme-text-color);">{{ $account->name }}</h6>
                  @if($account->is_default)
                    <span class="badge bg-info" style="font-size: 0.6rem;">Utama</span>
                  @endif
                </div>
              </div>
              <p class="fw-bold mb-1" style="color: var(--tg-theme-text-color); font-size: 1.1rem;">
                Rp {{ number_format($account->balance->getAmount()->toInt(), 0, ',', '.') }}
              </p>
              @if($account->account_number)
                <small class="d-block text-truncate" style="color: var(--tg-theme-hint-color);">{{ $account->account_number }}</small>
              @endif
                <small style="color: var(--tg-theme-hint-color);">{{ ucfirst(str_replace('_', ' ', $account->type->value)) }}</small>
            </a>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="text-center py-5" style="color: var(--tg-theme-hint-color);">
            <i class="bi bi-wallet2 display-1"></i>
            <p class="mt-3">Belum ada akun. <a href="{{ route('apps.accounts.create') }}" style="color: var(--tg-theme-button-color);">Tambah sekarang</a></p>
          </div>
        </div>
      @endforelse
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
                Apakah Anda yakin ingin menghapus akun <strong id="deleteAccountName"></strong>? Semua transaksi terkait akan ikut terhapus.
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
    .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
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
    .modal-content {
        border-radius: 16px;
    }
</style>
@endpush

@push('scripts')
<script>
    let deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    function showDeleteModal(accountId, accountName) {
        document.getElementById('deleteAccountName').textContent = accountName;
        const deleteForm = document.getElementById('delete-form');
        deleteForm.action = `{{ url('apps/accounts') }}/${accountId}`;
        deleteModal.show();
    }
</script>
@endpush