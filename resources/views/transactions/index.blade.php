@extends('wallet::layouts.app')

@section('title', 'Transactions')

@use('Modules\Wallet\Enums\TransactionType')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="page-title">Daftar Transaksi</h1>
  <div>
    <a class="btn btn-primary"></a>
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

<div class="row">
  @forelse($transactions as $date => $transaction)
  <div class="col-md-6 col-lg-6 mb-2">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title" onclick="window.location='{{ route('apps.transactions.dates', ['date' => $date]) }}'">{{ $date }}</h5>
      </div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between">
            <strong>Total</strong>
            <span class="text-muted">{{ $transaction['total'] }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <strong>Deposit</strong>
            <span class="text-success">{{ $transaction['deposit'] }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <strong>Withdraw</strong>
            <span class="text-danger">{{ $transaction['withdraw'] }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
  @empty
  <div class="alert alert-warning" role="alert">
    <p class="text-muted text-center">No transactions recorded.</p>
  </div>
  @endforelse
</div>

<div class="modal fade" id="changeDefaultWalletModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Wallet default</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="{{ route('apps.wallets.default') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label for="wallet-id" class="form-label">Set Default</label>
            <select name="wallet_id" class="form-select" id="wallet-id">
              @foreach($wallets as $wallet)
              <option value="{{ $wallet->id }}" @selected($wallet->is_default)>{{ $wallet->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"  data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
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
    form.action = '{{ route("apps.transactions.destroy") }}/'+ id;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
  }
</script>
@endpush