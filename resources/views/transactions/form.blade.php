@extends('layouts.app')

@section('title', isset($transaction) ? 'Edit Transaksi' : 'Tambah Transaksi')

@use('\Modules\Wallet\Enums\TransactionType')
@use('\Modules\Wallet\Enums\RecurringFreq')

@section('content')
<div class="row justify-content-center">
  <div class="col-12">
    <div class="card border-0 shadow-sm" style="background-color: var(--tg-theme-section-bg-color);">
      <div class="card-header py-3" style="background-color: transparent; border-bottom: 1px solid var(--tg-theme-section-separator-color);">
        <h5 class="card-title mb-0 fw-bold" style="color: var(--tg-theme-section-header-text-color);">
          <i class="bi bi-cash-stack me-2" style="color: var(--tg-theme-accent-text-color);"></i>
          {{ isset($transaction) ? 'Edit Transaksi' : 'Tambah Transaksi Baru' }}
        </h5>
      </div>
      <div class="card-body p-4">
        <form method="POST" action="{{ isset($transaction) ? route('apps.transactions.update', $transaction) : route('apps.transactions.store') }}">
          @csrf
          @if(isset($transaction))
            @method('PUT')
          @endif

          <div class="row g-4">
            <!-- Tipe Transaksi -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-arrow-left-right me-2" style="color: var(--tg-theme-accent-text-color);"></i>Tipe Transaksi
              </label>
              <select name="type" id="transactionType" class="form-select @error('type') is-invalid @enderror" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                <option value="">Pilih Tipe</option>
                @foreach(TransactionType::cases() as $type)
                  <option value="{{ $type->value }}" @selected(old('type', $transaction->type ?? '') == $type->value)>
                    {{ $type->label() }}
                  </option>
                @endforeach
              </select>
              @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Akun -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-wallet2 me-2" style="color: var(--tg-theme-accent-text-color);"></i>Akun
              </label>
              <select name="account_id" class="form-select @error('account_id') is-invalid @enderror" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                <option value="">Pilih Akun</option>
                @foreach($accounts as $account)
                  <option value="{{ $account->id }}" @selected(old('account_id', $transaction->account_id ?? '') == $account->id)>
                    {{ $account->name }} (Rp {{ number_format($account->balance, 0, ',', '.') }})
                  </option>
                @endforeach
              </select>
              @error('account_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Akun Tujuan (Transfer) - disembunyikan default -->
            <div class="col-md-6" id="toAccountField" style="display: none;">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-arrow-right-circle me-2" style="color: var(--tg-theme-accent-text-color);"></i>Akun Tujuan
              </label>
              <select name="to_account_id" class="form-select @error('to_account_id') is-invalid @enderror" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                <option value="">Pilih Akun Tujuan</option>
                @foreach($accounts as $account)
                  <option value="{{ $account->id }}" @selected(old('to_account_id', $transaction->to_account_id ?? '') == $account->id)>
                    {{ $account->name }}
                  </option>
                @endforeach
              </select>
              @error('to_account_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Kategori -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-tags me-2" style="color: var(--tg-theme-accent-text-color);"></i>Kategori
              </label>
              <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                <option value="">Pilih Kategori</option>
                @foreach($categories as $category)
                  <option value="{{ $category->id }}" @selected(old('category_id', $transaction->category_id ?? '') == $category->id)>
                    {{ $category->name }}
                  </option>
                @endforeach
              </select>
              @error('category_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Jumlah -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-cash me-2" style="color: var(--tg-theme-accent-text-color);"></i>Jumlah (Rp)
              </label>
              <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount', $transaction->amount ?? '') }}" min="0" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
              @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Deskripsi -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-card-text me-2" style="color: var(--tg-theme-accent-text-color);"></i>Deskripsi
              </label>
              <input type="text" class="form-control @error('description') is-invalid @enderror" name="description" value="{{ old('description', $transaction->description ?? '') }}" maxlength="255" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Tanggal Transaksi -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-calendar me-2" style="color: var(--tg-theme-accent-text-color);"></i>Tanggal Transaksi
              </label>
              <input type="datetime-local" class="form-control @error('transaction_date') is-invalid @enderror" name="transaction_date" value="{{ old('transaction_date', isset($transaction) ? \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
              @error('transaction_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Metode Pembayaran -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-credit-card me-2" style="color: var(--tg-theme-accent-text-color);"></i>Metode Pembayaran (opsional)
              </label>
              <input type="text" class="form-control" name="payment_method" value="{{ old('payment_method', $transaction->payment_method ?? '') }}" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
            </div>

            <!-- Nomor Referensi -->
            <div class="col-md-6">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-upc-scan me-2" style="color: var(--tg-theme-accent-text-color);"></i>Nomor Referensi (opsional)
              </label>
              <input type="text" class="form-control" name="reference_number" value="{{ old('reference_number', $transaction->reference_number ?? '') }}" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
            </div>

            <!-- Catatan -->
            <div class="col-12">
              <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">
                <i class="bi bi-pencil me-2" style="color: var(--tg-theme-accent-text-color);"></i>Catatan (opsional)
              </label>
              <textarea class="form-control" name="notes" rows="2" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">{{ old('notes', $transaction->notes ?? '') }}</textarea>
            </div>

            <!-- Opsi Berulang -->
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="isRecurring" {{ old('is_recurring', $transaction->is_recurring ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="isRecurring" style="color: var(--tg-theme-text-color);">
                  Transaksi Berulang
                </label>
              </div>
            </div>

            <!-- Field Berulang (ditampilkan jika recurring dicentang) -->
            <div id="recurringFields" style="display: none; width: 100%;">
              <div class="row g-4 mt-2">
                <div class="col-md-6">
                  <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">Frekuensi</label>
                  <select name="frequency" id="frequency" class="form-select" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                    <option value="">Pilih Frekuensi</option>
                    @foreach(RecurringFreq::cases() as $freq)
                      <option value="{{ $freq->value }}" {{ old('frequency', $transaction->frequency ?? '') == $freq->value ? 'selected' : '' }}>
                        {{ ucfirst($freq->value) }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">Interval</label>
                  <input type="number" class="form-control" name="interval" value="{{ old('interval', $transaction->interval ?? 1) }}" min="1" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">Tanggal Mulai</label>
                  <input type="date" class="form-control" name="start_date" value="{{ old('start_date', isset($transaction) ? \Carbon\Carbon::parse($transaction->start_date)->format('Y-m-d') : now()->format('Y-m-d')) }}" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">Tanggal Berakhir (opsional)</label>
                  <input type="date" class="form-control" name="end_date" value="{{ old('end_date', isset($transaction) && $transaction->end_date ? \Carbon\Carbon::parse($transaction->end_date)->format('Y-m-d') : '') }}" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">Sisa Kejadian (opsional)</label>
                  <input type="number" class="form-control" name="remaining_occurrences" value="{{ old('remaining_occurrences', $transaction->remaining_occurrences ?? '') }}" min="0" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                </div>
                <!-- Field khusus frekuensi -->
                <div class="col-md-6" id="dayOfWeekField" style="display: none;">
                  <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">Hari dalam Minggu</label>
                  <select name="day_of_week" class="form-select" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                    <option value="">Pilih Hari</option>
                    <option value="0" {{ old('day_of_week', $transaction->day_of_week ?? '') == '0' ? 'selected' : '' }}>Minggu</option>
                    <option value="1" {{ old('day_of_week', $transaction->day_of_week ?? '') == '1' ? 'selected' : '' }}>Senin</option>
                    <option value="2" {{ old('day_of_week', $transaction->day_of_week ?? '') == '2' ? 'selected' : '' }}>Selasa</option>
                    <option value="3" {{ old('day_of_week', $transaction->day_of_week ?? '') == '3' ? 'selected' : '' }}>Rabu</option>
                    <option value="4" {{ old('day_of_week', $transaction->day_of_week ?? '') == '4' ? 'selected' : '' }}>Kamis</option>
                    <option value="5" {{ old('day_of_week', $transaction->day_of_week ?? '') == '5' ? 'selected' : '' }}>Jumat</option>
                    <option value="6" {{ old('day_of_week', $transaction->day_of_week ?? '') == '6' ? 'selected' : '' }}>Sabtu</option>
                  </select>
                </div>
                <div class="col-md-6" id="dayOfMonthField" style="display: none;">
                  <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">Tanggal dalam Bulan</label>
                  <input type="number" class="form-control" name="day_of_month" value="{{ old('day_of_month', $transaction->day_of_month ?? '') }}" min="1" max="31" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">
                </div>
                <div class="col-12" id="customScheduleField" style="display: none;">
                  <label class="form-label fw-medium" style="color: var(--tg-theme-text-color);">Jadwal Kustom (pisahkan dengan koma)</label>
                  <textarea class="form-control" name="custom_schedule" rows="2" style="background-color: var(--tg-theme-bg-color); border-color: var(--tg-theme-hint-color); color: var(--tg-theme-text-color);">{{ old('custom_schedule', is_array($transaction->custom_schedule ?? null) ? implode(', ', $transaction->custom_schedule) : '') }}</textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Tombol Aksi -->
          <div class="row mt-5">
            <div class="col-12 d-flex justify-content-between">
              <button type="button" class="btn px-4 py-2" style="background-color: transparent; color: var(--tg-theme-button-color); border: 1px solid var(--tg-theme-button-color);" onclick="goBack();">
                <i class="bi bi-arrow-left me-2"></i>Batal
              </button>
              <button type="submit" class="btn px-4 py-2" style="background-color: var(--tg-theme-button-color); color: var(--tg-theme-button-text-color); border: none;">
                <i class="bi bi-check-circle me-2"></i>{{ isset($transaction) ? 'Perbarui' : 'Simpan' }}
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('transactionType');
        const toAccountField = document.getElementById('toAccountField');
        const isRecurringCheck = document.getElementById('isRecurring');
        const recurringFields = document.getElementById('recurringFields');
        const frequencySelect = document.getElementById('frequency');
        const dayOfWeekField = document.getElementById('dayOfWeekField');
        const dayOfMonthField = document.getElementById('dayOfMonthField');
        const customScheduleField = document.getElementById('customScheduleField');

        // Fungsi untuk menampilkan/menyembunyikan field transfer
        function toggleTransferField() {
            if (typeSelect.value === 'transfer') {
                toAccountField.style.display = 'block';
            } else {
                toAccountField.style.display = 'none';
            }
        }

        // Fungsi untuk menampilkan/menyembunyikan field recurring
        function toggleRecurringFields() {
            if (isRecurringCheck.checked) {
                recurringFields.style.display = 'block';
            } else {
                recurringFields.style.display = 'none';
            }
        }

        // Fungsi untuk menampilkan field khusus frekuensi
        function toggleFrequencyFields() {
            const freq = frequencySelect.value;
            dayOfWeekField.style.display = 'none';
            dayOfMonthField.style.display = 'none';
            customScheduleField.style.display = 'none';

            if (freq === 'weekly') {
                dayOfWeekField.style.display = 'block';
            } else if (freq === 'monthly' || freq === 'quarterly') {
                dayOfMonthField.style.display = 'block';
            } else if (freq === 'custom') {
                customScheduleField.style.display = 'block';
            }
        }

        // Event listeners
        typeSelect.addEventListener('change', toggleTransferField);
        isRecurringCheck.addEventListener('change', toggleRecurringFields);
        frequencySelect.addEventListener('change', toggleFrequencyFields);

        // Initial state
        toggleTransferField();
        toggleRecurringFields();
        toggleFrequencyFields();

        // Jika ada old value untuk transfer, pastikan field tampil
        @if(old('type') === 'transfer' || (isset($transaction) && $transaction->type === 'transfer'))
            toAccountField.style.display = 'block';
        @endif

        // Jika ada old value untuk recurring
        @if(old('is_recurring') || (isset($transaction) && $transaction->is_recurring))
            recurringFields.style.display = 'block';
        @endif

        // Jika ada old value untuk frequency
        @if(old('frequency') || (isset($transaction) && $transaction->frequency))
            toggleFrequencyFields();
        @endif
    });
</script>
@endpush