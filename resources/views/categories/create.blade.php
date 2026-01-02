@extends('wallet::layouts.app')

@section('title', 'Tambah Kategori Baru - ' . config('app.name', 'VickyServer'))

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <a href="{{ route('apps.categories.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
  </div>
  <div class="ms-auto">
    <h1 class="page-title mb-2">
      <i class="bi bi-plus-circle me-2"></i>Tambah Kategori
    </h1>
    <p class="small text-muted mb-0">Tambahkan kategori baru untuk mengelola keuangan Anda</p>
  </div>
</div>

<!-- Form Card -->
<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <div class="card-title">
          <h5>Informasi Kategori</h5>
          <p class="text-muted">Isi form berikut untuk membuat kategori baru</p>
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

        @include('wallet::categories._form')

        <div class="mt-4">
          <button type="submit" form="categoryForm" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Simpan Kategori
          </button>
          <a href="{{ route('apps.categories.index') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle me-2"></i>Batal
          </a>
        </div>
      </div>
    </div>

    <!-- Quick Tips -->
    <div class="card mt-4">
      <div class="card-header">
        <h6 class="mb-0">
          <i class="bi bi-lightbulb me-2"></i>Tips Membuat Kategori
        </h6>
      </div>
      <div class="card-body">
        <ul class="mb-0">
          <li>Gunakan nama yang deskriptif dan mudah diingat</li>
          <li>Pisahkan antara kategori <strong>Pemasukan</strong> dan <strong>Pengeluaran</strong></li>
          <li>Atur batas anggaran untuk kategori pengeluaran penting</li>
          <li>Gunakan icon yang sesuai untuk memudahkan identifikasi</li>
          <li>Nonaktifkan kategori yang sudah tidak digunakan</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection

