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

    <!-- Grid Daftar Akun -->
    <div class="row g-3">
      @forelse($accounts as $account)
        <div class="col-md-6 col-lg-3">
          <div class="card border-0 shadow-sm h-100 position-relative" style="background-color: var(--tg-theme-secondary-bg-color);">
            <!-- Tombol Aksi (Edit & Hapus) -->
            <div class="position-absolute top-0 end-0 p-2 d-flex gap-1">
              <a href="{{ route('apps.accounts.edit', $account) }}" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 32px; height: 32px; padding: 0; background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);" title="Edit">
                <i class="bi bi-pencil"></i>
              </a>
              <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" style="width: 32px; height: 32px; padding: 0;" onclick="confirmDelete({{ $account->id }}, '{{ $account->name }}')" title="Hapus">
                <i class="bi bi-trash"></i>
              </button>
            </div>

            <!-- Konten Akun -->
            <a href="{{ route('apps.accounts.show', $account) }}" class="text-decoration-none d-block p-3">
              <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background-color: {{ $account->color }}20; color: {{ $account->color }};">
                  <i class="bi {{ $account->icon }}"></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-0 text-truncate" style="color: var(--tg-theme-text-color);">{{ $account->name }}</h6>
                  @if($account->is_default)
                    <span class="badge bg-info" style="font-size: 0.6rem;">Utama</span>
                  @endif
                </div>
              </div>
              <p class="fw-bold mb-0" style="color: var(--tg-theme-text-color);">
                Rp {{ number_format($account->balance->getAmount()->toInt(), 0, ',', '.') }}
              </p>
              @if($account->account_number)
                <small class="text-muted">{{ $account->account_number }}</small>
              @endif
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

<!-- Form Hapus (tersembunyi) -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function confirmDelete(accountId, accountName) {
    if (confirm(`Apakah Anda yakin ingin menghapus akun "${accountName}"? Semua transaksi terkait akan ikut terhapus.`)) {
        const form = document.getElementById('delete-form');
        form.action = `{{ url('apps/accounts') }}/${accountId}`;
        form.submit();
    }
}
</script>
@endpush