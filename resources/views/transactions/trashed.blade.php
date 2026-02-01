@extends('wallet::layouts.app')

@section('title', 'Transaksi - Deleted')

@section('content')
@include('wallet::partials.fab')

<div class="card border-0 shadow-sm">
  <div class="card-header text-bg-white border-0">
    <div class="d-flex justify-content-between align-items-center gap-2">
      <h5 class="card-title mb-0">Deleted</h5>
    </div>
  </div>
  <div class="card-body p-0">
    @if($transactions->count() > 0)
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th width="50" class="text-center">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="selectAll">
                  <label class="form-check-label" for="selectAll">All</label>
                </div>
              </th>
              <th>Tanggal</th>
              <th>Keterangan</th>
              <th class="text-end">Jumlah</th>
              <th>Kategori</th>
              <th>Akun</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($transactions as $transaction)
              <tr class="transaction-{{ $transaction->type }}">
                <td class="text-center">
                  <input type="checkbox" class="form-check-input transaction-check" value="{{ $transaction->id }}">
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="transaction-icon">
                      <i class="bi {{ $transaction->type->icon() }}"></i>
                    </div>
                    <div>
                      {{ $transaction->transaction_date->format('d/m/Y') }}
                      <br>
                      <small class="text-muted">
                        {{ $transaction->transaction_date->format('H:i') }}
                      </small>
                    </div>
                  </div>
                </td>
                <td>
                  <strong>{{ $transaction->description }}</strong>
                  @if($transaction->notes)
                    <br>
                    <small class="text-muted">{{ Str::limit($transaction->notes, 50) }}</small>
                  @endif
                  @if($transaction->reference_number)
                    <br>
                    <small class="text-muted">
                      <i class="bi bi-hash"></i> {{ $transaction->reference_number }}
                    </small>
                  @endif
                </td>
                <td class="text-end">
                  <span class="transaction-amount">
                    {{ $transaction->formattedAmount }}
                  </span>
                  <br>
                  <small class="text-muted">{{ $transaction->type->label() }}</small>
                </td>
                <td>
                  <span class="badge bg-light text-dark">
                    <i class="bi {{ $transaction->category->icon }} me-1"></i>
                    {{ $transaction->category->name }}
                  </span>
                </td>
                <td>
                  @if($transaction->type == TransactionType::TRANSFER)
                    <div>
                      <small class="text-muted">Dari:</small>
                      <br>{{ $transaction->account->name }}
                    </div>
                    <div class="mt-1">
                      <small class="text-muted">Ke:</small>
                      <br>{{ $transaction->toAccount->name ?? '-' }}
                    </div>
                  @else
                    {{ $transaction->account->name }}
                  @endif
                </td>
                <td class="text-center">
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-warning btn-restore" data-transaction='@json($transaction)' title="Restore">
                      <i class="bi bi-recycle"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-delete" data-transaction='@json($transaction)' title="Hapus Permanent">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    
      <!-- Pagination -->
      @if($transactions->hasPages())
        <div class="card-footer text-bg-white border-0">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              Menampilkan {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }}
              dari {{ $transactions->total() }} transaksi
            </div>
            <div>
              {{ $transactions->links() }}
            </div>
          </div>
        </div>
      @endif
    @else
      <div class="text-center py-5">
        <div class="mb-4">
          <i class="bi bi-receipt display-1 text-muted"></i>
        </div>
        <h5 class="text-muted">Belum ada transaksi terhapus</h5>
      </div>
    @endif
  </div>
</div>
@endsection