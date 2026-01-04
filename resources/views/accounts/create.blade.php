@extends('wallet::layouts.app')

@section('title', 'Tambah Akun Baru - ' . config('app.name'))

@section('content')
@include('wallet::partials.fab')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4 text-end">
    <a href="{{ route('apps.accounts.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
    <div>
      <h1 class="page-title mb-2">
        <i class="bi bi-plus-circle me-2"></i>Tambah Akun Baru
      </h1>
      <p class="text-muted mb-0">Tambahkan akun bank atau dompet digital baru</p>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          @if($errors->any())
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle"></i>
              Terdapat kesalahan dalam pengisian form.
            </div>
          @endif

          @include('wallet::accounts._form')

          <div class="mt-4">
            <button type="submit" form="accountForm" class="btn btn-primary">
              <i class="bi bi-check-circle me-2"></i>Simpan Akun
            </button>
            <a href="{{ route('apps.accounts.index') }}" class="btn btn-secondary">
              <i class="bi bi-x-circle me-2"></i>Batal
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection