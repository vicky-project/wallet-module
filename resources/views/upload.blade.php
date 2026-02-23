@extends('core::layouts.app')

@section('title', 'Upload Transaksi')

@section('content')
<div class="row justify-content-center">
  <div class="col-12">
    <div class="card border-0 shadow-sm" style="background-color: var(--tg-theme-section-bg-color);">
      <div class="card-header py-3" style="background-color: transparent; border-bottom: 1px solid var(--tg-theme-section-separator-color);">
        <h5 class="card-title mb-0 fw-bold" style="color: var(--tg-theme-section-header-text-color);">
          <i class="bi bi-upload me-2" style="color: var(--tg-theme-accent-text-color);"></i>
          Upload File Transaksi
        </h5>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="{{ route('apps.uploads.store') }}" enctype="multipart/form-data">
          @csrf

          <div class="row g-4">
            <!-- Pilih Aplikasi Sumber -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-grid me-2" style="color: var(--tg-theme-accent-text-color);"></i>
                Aplikasi Sumber
              </label>
              <select name="apps_name" class="form-select @error('apps_name') is-invalid @enderror" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                <option value="">Pilih Aplikasi</option>
                @foreach($appsOptions as $app)
                  <option value="{{ $app }}" {{ old('apps_name') == $app ? 'selected' : '' }}>
                    {{ ucfirst($app) }}
                  </option>
                @endforeach
              </select>
              @error('apps_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Pilih Akun Tujuan -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-wallet2 me-2" style="color: var(--tg-theme-accent-text-color);"></i>
                Akun Tujuan
              </label>
              <select name="account_id" class="form-select @error('account_id') is-invalid @enderror" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                <option value="">Pilih Akun</option>
                @foreach($accounts as $account)
                  <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                    {{ $account->name }}
                  </option>
                @endforeach
              </select>
              @error('account_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Upload File -->
            <div class="col-12">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-file-earmark me-2" style="color: var(--tg-theme-accent-text-color);"></i>
                File Transaksi
              </label>
              <input type="file" class="form-control @error('file') is-invalid @enderror" name="file" accept=".csv,.txt,.pdf,.xls,.xlsx" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
              <small class="text-muted" style="color: var(--tg-theme-accent-text-color);">
                Format yang didukung: CSV, TXT, PDF, Excel (.xls, .xlsx)
              </small>
              @error('file')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Password (opsional) -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                                <i class="bi bi-lock me-1" style="color: var(--tg-theme-accent-text-color);"></i>
                                Password (jika file diproteksi)
                            </label>
              <input type="password" class="form-control" name="password" value="{{ old('password') }}" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
              <small class="text-muted" style="color: var(--tg-theme-hint-color);">Kosongkan jika tidak ada password</small>
            </div>

            <!-- Opsi Otomatis -->
            <div class="col-12">
              <div class="d-flex gap-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="auto_create_categories" value="1" id="autoCreateCategories" @checked(old('auto_create_categories', true))>
                  <label class="form-check-label" for="autoCreateCategories" style="color: var(--tg-theme-text-color);">
                    Buat kategori otomatis
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="auto_create_tags" value="1" id="autoCreateTags" @checked(old('auto_create_tags'))>
                  <label class="form-check-label" for="autoCreateTags" style="color: var(--tg-theme-text-color);">
                    Buat tag otomatis
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Tombol Aksi -->
          <div class="row mt-5">
            <div class="col-12 d-flex justify-content-between">
              <button type="button" class="btn px-4 py-2" style="background-color: transparent; color: var(--tg-theme-button-color); border: 1px solid var(--tg-theme-button-color);" onclick="goBack();">
                <i class="bi bi-arrow-left me-2"></i>Kembali
              </button>
              <button type="submit" class="btn px-4 py-2" style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none;">
                <i class="bi bi-cloud-upload me-2"></i>Upload
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
    .form-control, .form-select {
        border-width: 1px;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--tg-theme-button-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--tg-theme-button-color-rgb, 64, 167, 227), 0.25);
    }
    .form-check-input:checked {
        background-color: var(--tg-theme-button-color);
        border-color: var(--tg-theme-button-color);
    }
    .form-check-input:focus {
        border-color: var(--tg-theme-button-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--tg-theme-button-color-rgb, 64, 167, 227), 0.25);
    }
</style>
@endpush