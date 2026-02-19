@extends('wallet::layouts.app')

@section('title', 'Transaksi - Deleted')

@use('Modules\Wallet\Enums\TransactionType')
@use('Modules\Wallet\Enums\PaymentMethod')


@section('content')
@include('wallet::partials.fab')

<div class="card border-0 shadow-sm">
  <div class="card-header text-bg-white border-0">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Deleted</h5>
      <div>
        <button type="button" class="btn btn-sm btn-outline-warning bulk-action" data-action="restore" title="Restore Selected">
          <i class="bi bi-recycle"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger bulk-action" data-action="delete" title="Force Delete Selected">
          <i class="bi bi-trash"></i>
        </button>
      </div>
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Konfirmasi Hapus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin menghapus transaksi ini?</p>
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <strong>Perhatian:</strong> Saldo akun akan disesuaikan otomatis.
        </div>
        <div class="card">
          <div class="card-body">
            <strong id="transaction-description"></strong>
            <br>
            <small class="text-muted" id="transaction-date"></small>
            <div class="mt-2">
              <span class="badge" id="transaction-type">
              </span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <form method="POST" style="display: inline;" id="form-delete">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash me-2"></i> Hapus
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function forceDeleteItem(transactionItem) {
    const transaction = JSON.parse(transactionItem);
    alert(transaction.id);
  }
  
  function restoreDeleteItem(transactionItem) {
    const transaction = JSON.parse(transactionItem);
    alert(transaction.id);
  }
  
  // Bulk Delete
  function bulkDelete(ids) {
    fetch("{{ secure_url(config('app.url') . '/apps/transactions/bulk-delete-trashed') }}", {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ '_token': '{{ csrf_token() }}', ids: ids })
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(`Berhasil menghapus ${data.deleted} transaksi.`);
          location.reload();
        } else {
          alert('Gagal menghapus transaksi: ' + data.message);
        }
      })
      .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
      });
  }
        
  function bulkRestore(ids) {
    fetch("{{ secure_url(config('app.url') . '/apps/transactions/bulk-restore') }}", {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ '_token': '{{ csrf_token() }}', ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
          alert(`Berhasil memulihkan ${data.restored} transaksi.`);
          location.reload();
        } else {
          alert('Gagal menghapus transaksi: ' + data.message);
        }
    })
    .catch(error => {
      alert('Terjadi kesalahan: ' + error.message);
    });
  }
  
  document.addEventListener('DOMContentLoaded', function() {
            // Select All functionality
        const selectAll = document.getElementById('selectAll');
        const transactionChecks = document.querySelectorAll('.transaction-check');
        const btnDelete = document.querySelectorAll('.btn-delete');
        const btnRestore = document.querySelectorAll('.btn-restore');
        
        if(btnDelete) {
            btnDelete.forEach(btn => btn.addEventListener('click', function() {
                const data = this.dataset.transaction;
                forceDeleteItem(data);
            }));
        }
        
        if(btnRestore) {
          btnRestore.forEach(btn => btn.addEventListener('click', function() {
              const data = this.dataset.transaction;
              restoreDeleteItem(data);
          }));
        }
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                transactionChecks.forEach(check => {
                    check.checked = isChecked;
                });
            });
        }
        
        // Bulk Actions
        const bulkActions = document.querySelectorAll('.bulk-action');
        bulkActions.forEach(action => {
            action.addEventListener('click', function() {
                const selectedTransactions = Array.from(transactionChecks)
                    .filter(check => check.checked)
                    .map(check => check.value);
                
                if (selectedTransactions.length === 0) {
                    alert('Pilih setidaknya satu transaksi terlebih dahulu.');
                    return;
                }
                
                const actionType = this.dataset.action;
                
                switch(actionType) {
                    case 'delete':
                        if (confirm(`Apakah Anda yakin ingin menghapus ${selectedTransactions.length} transaksi terpilih?`)) {
                            bulkDelete(selectedTransactions);
                        }
                        break;
                        
                    case 'restore':
                        bulkRestore(selectedTransactions);
                        break;
                }
            });
        });
  });
</script>
@endpush

@push('styles')
<style>
    .transaction-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 1.2rem;
    }
    
    .transaction-income .transaction-icon {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }
    
    .transaction-expense .transaction-icon {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .transaction-transfer .transaction-icon {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    
    .transaction-amount {
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .transaction-income .transaction-amount {
        color: #198754;
    }
    
    .transaction-expense .transaction-amount {
        color: #dc3545;
    }
    
    .transaction-transfer .transaction-amount {
        color: #0d6efd;
    }
</style>
@endpush