@extends('wallet::layouts.app')

@section('title', 'Edit Akun - ' . $account->name . ' - ' . config('app.name'))

@use('Modules\Wallet\Helpers\Helper')

@section('content')
@include('wallet::partials.fab')
<div class="d-flex justify-content-between align-items-center mb-4 text-end">
  <a href="{{ route('apps.accounts.index') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-2"></i>Kembali
  </a>
  <div>
    <h1 class="page-title mb-2">
      <i class="bi bi-pencil-square me-2"></i>Edit Akun
    </h1>
    <p class="text-muted mb-0">Ubah informasi akun "{{ $account->name }}"</p>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        @php $accountTypeMap = Helper::accountTypeMap($account->type->value) @endphp
        <div class="card-title d-flex align-items-center mb-4">
          <div class="account-icon me-3" style="width: 60px; height: 60px; background: {{ $accountTypeMap['color'] ?? '#4361ee' }}; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i class="bi {{ $accountTypeMap['icon'] }} fs-3"></i>
          </div>
          <div>
            <h5 class="mb-0">{{ $account->name }}</h5>
            <p class="text-muted mb-0">
              {{ $account->type_label }} • {{ $account->formatted_balance }}
            </p>
          </div>
        </div>
        @include('wallet::accounts._form', ['account' => $account])

        <div class="mt-4 d-flex justify-content-between">
          <div>
            <button type="submit" form="accountForm" class="btn btn-primary">
              <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
            </button>
            <a href="{{ route('apps.accounts.index') }}" class="btn btn-secondary">
              <i class="bi bi-x-circle me-2"></i>Batal
            </a>
          </div>
          <form action="{{ route('apps.accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Hapus akun ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
              <i class="bi bi-trash me-2"></i>Hapus Akun
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection