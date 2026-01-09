@extends('wallet::layouts.app')

@section('title', 'Edit Akun: ' . $account->name)

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="row mb-4">
  <div class="col">
    <div class="d-flex justify-content-between align-items-center text-end">
      <div class="d-flex gap-2 me-auto">
        <a href="{{ route('apps.accounts.show', $account) }}" class="btn btn-outline-secondary">
          <i class="bi bi-eye me-1"></i>Lihat
        </a>
        <a href="{{ route('apps.accounts.index') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
      </div>
      <div>
        <h2 class="page-title mb-2">
          <i class="bi bi-pencil-square text-warning me-2"></i>Edit Akun
        </h2>
        <p class="text-muted mb-0">
          Perbarui informasi akun <strong>{{ $account->name }}</strong>.
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Form Card -->
<div class="row">
  <div class="col-lg-10 col-xl-8 mx-auto">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="bi bi-pencil-square me-2"></i>Edit Informasi Akun
          </h5>
          <div class="account-icon" style="background-color: {{ $account->color }}20; color: {{ $account->color }}">
            <i class="{{ $account->icon }}"></i>
          </div>
        </div>
      </div>
      <div class="card-body">
                    @include('wallet::partials.accounts.form', [
                        'action' => route('apps.accounts.update', $account),
                        'account' => $account
                    ])
                </div>
    </div>
            
    <!-- Danger Zone -->
    <div class="card border-danger mt-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Zona Bahaya
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="text-danger mb-2">Hapus Akun</h6>
                            <p class="text-muted mb-3">
                                Menghapus akun akan menghapus semua data terkait termasuk transaksi yang terkait dengan akun ini.
                                Tindakan ini tidak dapat dibatalkan.
                            </p>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <strong>Peringatan:</strong> Pastikan untuk mencadangkan data penting sebelum menghapus akun.
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="bi bi-trash me-1"></i>Hapus Akun
                            </button>
                        </div>
                    </div>
                </div>
            </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteAccountModalLabel">
          <i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Penghapusan
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <div class="account-icon mx-auto mb-3" style="background-color: {{ $account->color }}20; color: {{ $account->color }}">
            <i class="{{ $account->icon }} fs-2"></i>
          </div>
          <h5 class="mb-3">Hapus Akun "{{ $account->name }}"?</h5>
          <p class="text-muted">
            Anda akan menghapus akun <strong>{{ $account->name }}</strong> dan semua data terkait.
            Tindakan ini tidak dapat dibatalkan.
          </p>
                    
          @if($account->transactions_count > 0)
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-circle me-2"></i>
              Akun ini memiliki <strong>{{ $account->transactions_count }} transaksi</strong> yang akan terhapus.
            </div>
          @endif
                    
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" id="confirmDelete">
            <label class="form-check-label" for="confirmDelete">
              Saya mengerti bahwa tindakan ini tidak dapat dibatalkan
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <form action="{{ route('apps.accounts.destroy', $account) }}" method="POST" id="deleteForm">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger" id="deleteButton" disabled>
            <i class="bi bi-trash me-1"></i>Hapus Akun
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete confirmation toggle
        const confirmCheckbox = document.getElementById('confirmDelete');
        const deleteButton = document.getElementById('deleteButton');
        
        if (confirmCheckbox && deleteButton) {
            confirmCheckbox.addEventListener('change', function() {
                deleteButton.disabled = !this.checked;
            });
        }
    });
</script>
@endpush