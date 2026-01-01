@extends('wallet::layouts.app')

@section('title', 'Transactions')

@use('Modules\Wallet\Enums\TransactionType')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="page-title">Daftar Transaksi</h1>
  <div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-4 mb-3">
    <div class="card summary-card summary-income">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="card-icon bg-income">
            <i class="bi bi-arrow-up-circle text-income"></i>
          </div>
          <div class="ms-3">
            <h6 class="card-subtitle mb-1">Total Pemasukan</h6>
            <h4 class="card-title mb-0 text-income">{{ $summary['income']->formatTo('id_ID') }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-3">
    <div class="card summary-card summary-expense">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="card-icon bg-expense">
            <i class="bi bi-arrow-down-circle text-expense"></i>
          </div>
          <div class="ms-3">
            <h6 class="card-subtitle mb-1">Total Pengeluaran</h6>
            <h4 class="card-title mb-0 text-expense">
              {{ $summary["expense"]->formatTo('id_ID') }}
            </h4>
          </div>
        </div>
      </div>
    </div>
  </div>
   <div class="col-md-4 mb-3">
     <div class="card summary-card summary-net">
       <div class="card-body">
         <div class="d-flex align-items-center">
           <div class="card-icon" style="background-color: rgba(59, 130, 246, 0.1);">
             <i class="bi bi-graph-up" style="color: #3b82f6"></i>
           </div>
           <div class="ms-3">
             <h6 class="card-subtitle mb-1">Saldo Bersih</h6>
             <h4 class="card-title mb-0">
               {{  $summary["net_balance"]->formatTo('id_ID') }}
             </h4>
           </div>
         </div>
       </div>
     </div>
   </div>
</div>

<div class="card filter-card mb-4">
  <div class="card-body"></div>
</div>

<div class="card">
  <div class="card-body p-0">
    @if($transactions->count() > 0)
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th width="50">#</th>
            <th>Tanggal</th>
            <th>Keterangan</th>
            <th>Kategori</th>
            <th>Akun</th>
            <th class="text-end">Jumlah</th>
            <th class="text-center">Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($transactions as $transaction)
          <tr class="transaction-row" onclick="window.location='{{ route('apps.transactions.show', $transaction) }}'">
            <td>
              <div class="transaction-icon {{ $transaction->type === TransactionType::INCOME ? 'bg-income' : 'bg-expense' }}" style="width: 32px; height: 32px; margin: 0 auto;">
                <i class="bi {{ $transaction->type === TransactionType::INCOME ? 'bi-arrow-up-circle text-income' : 'bi-arrow-down-circle text-expense'}}"></i>
              </div>
            </td>
            <td>
              <div class="fw-medium">{{ $transaction->transaction_date->format('d M Y') }}</div>
              <small class="text-muted">{{ $transaction->transaction_date->format('H:i') }}</small>
            </td>
            <td>
              <div class="fw-medium">{{ $transaction->title }}</div>
              @if($transaction->description)
              <small class="text-muted">{{ str($transaction->description)->limit(50) }}</small>
              @endif
            </td>
            <td>
              <span class="badge rounded-pill">
                <i class="bi {{ $transaction->category->icon}} me-1"></i>
                {{ $transaction->category->name }}
              </span>
            </td>
            <td>
              <small class="text-muted">
                {{ $transaction->account->name }}
              </small>
            </td>
            <td class="text-end">
              <div class="fw-bold {{ $transaction->type === TransactionType::INCOME ? 'text-income' : 'text-expense'}}">
                {{ $transaction->type === TransactionType::INCOME ? '+' : '-'}}{{ $transaction->formatted_amount }}
              </div>
              <small class="text-muted">
                <span class="badge payment-method-badge bg-secondary">
                  {{ $transaction->payment_method }}
                </span>
              </small>
            </td>
            <td class="text-center">
              @if($transaction->is_recurring)
              <span class="badge transaction-type-badge bg-info" title="Transaksi Rutin">
                <i class="bi bi-repeat me-1"></i>Rutin
              </span>
              @endif
              @if($transaction->is_verified === false)
              <span class="badge transaction-type-badge bg-warning" title="Belum Diverifikasi">
                <i class="bi bi-clock me-1"></i>Pending
              </span>
              @endif
            </td>
            <td class="text-end action-buttons">
              <div class="btn-group" onclick="event.stopPropagation();">
                <a href="{{ route('apps.transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary" title="Lihat">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="{{ route('apps.transactions.edit', $transaction) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="confirmDelete({{ $transaction->id }})">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <div class="empty-state">
      <div class="empty-state-icon"><i class="bi bi-receipt"></i></div>
      <h5 class="text-muted">Belum ada transaksi</h5>
      <p class="text-muted mb-4">
        @if(request()->hasAny(['type', 'category_id','account_id', 'search']))
        Tidak ditemukan transaksi dengan item yang dipilih.
        @else
        Mulai tambahkan transaksi baru.
        @endif
      </p>
      <a href="{{ route('apps.transactions.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2">Tambahkan transaksi</i>
      </a>
    </div>
    @endif
  </div>
</div>

<!-- Delete Conformation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Confirmation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Apakah anda yakin ingin menghapus transaksi ini?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteForm" method="POST" style="display: inline;">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Hapus</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function confirmDelete(id){
    event.stopPropagation();
    const form = document.getElementById('deleteForm');
    form.action = '{{ secure_url(config("app.url")) }}/apps/transactions/'+ id;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
  }
</script>
@endpush

@push('styles')
<style>
  .filter-card {
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, .125);
    background-color: #f8f9fa;
  }
  
  .transaction-type-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
  
  .payment-method-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
  }
  
  .transaction-row:hover {
    background-color: rgba(0, 0, 0, 0.02);
    cursor: pointer;
  }
  
  body[data-bs-theme="dark"] .transaction-row:hover {
    background-color: rgba(255, 255, 255, 0.02);
  }
  
  .action-buttons .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }
  
  .summary-card {
    border-left: 4px solid;
  }
  
  .summary-income {
    border-left-color: #10b981;
  }
  
  .summary-expense {
    border-left-color: #ef4444;
  }
  
  .summary-net {
    border-left-color: #3b82f6;
  }
  
  .empty-state {
    padding: 3rem 1rem;
    text-align: center;
  }
  
  .empty-state-icon {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 1rem;
  }
</style>
@endpush