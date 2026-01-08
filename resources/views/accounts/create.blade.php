@extends('wallet::layouts.app')

@section('title', 'Tambah Akun Baru')

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="row mb-4">
  <div class="col">
    <div class="d-flex justify-content-between align-items-center text-end">
      <div class="me-auto">
        <a href="{{ route('apps.accounts.index') }}" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left"></i>
        </a>
      </div>
      <div>
        <h2 class="page-title mb-2">
          <i class="bi bi-plus-circle text-primary me-2"></i>Tambah Akun Baru
        </h2>
        <p class="text-muted mb-0">
          Tambahkan akun keuangan baru untuk mulai melacak saldo dan transaksi.
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
        <h5 class="mb-0">
          <i class="bi bi-info-circle me-2"></i>Informasi Akun Baru
        </h5>
      </div>
      <div class="card-body">
        @include('wallet::partials.accounts.form', [
          'action' => route('apps.accounts.store'),
          'account' => null
        ])
      </div>
    </div>
            
    <!-- Quick Tips -->
    <div class="card mt-4">
      <div class="card-header">
        <h6 class="mb-0 text-bg-warning">
          <i class="bi bi-lightbulb text-warning me-2"></i>Tips Penggunaan
        </h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <div class="d-flex">
              <div class="flex-shrink-0">
                <i class="bi bi-check-circle text-success fs-5"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-1">Akun Default</h6>
                <p class="small text-muted mb-0">
                  Setel satu akun sebagai default untuk kemudahan dalam transaksi rutin.
                </p>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="d-flex">
              <div class="flex-shrink-0">
                <i class="bi bi-check-circle text-success fs-5"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-1">Warna & Ikon</h6>
                <p class="small text-muted mb-0">
                  Gunakan warna dan ikon yang berbeda untuk membedakan jenis akun dengan mudah.
                </p>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="d-flex">
              <div class="flex-shrink-0">
                <i class="bi bi-check-circle text-success fs-5"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-1">Saldo Awal</h6>
                <p class="small text-muted mb-0">
                  Masukkan saldo awal yang akurat untuk memulai pelacakan keuangan yang tepat.
                </p>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="d-flex">
              <div class="flex-shrink-0">
                <i class="bi bi-check-circle text-success fs-5"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="mb-1">Akun Tidak Aktif</h6>
                <p class="small text-muted mb-0">
                  Nonaktifkan akun yang sudah tidak digunakan tanpa menghapus data transaksi.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection