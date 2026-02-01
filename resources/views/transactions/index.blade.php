@extends('wallet::layouts.app')

@section('title', 'Transaksi - Aplikasi Keuangan')

@use('Modules\Wallet\Enums\TransactionType')
@use('Modules\Wallet\Enums\PaymentMethod')

@push('styles')
<style>
    /* Custom styling for accounts page */
    .filter-card {
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .filter-card.collapsed {
        height: 60px;
        overflow: hidden;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    
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
    
    .select2-container .select2-selection--single {
        height: 38px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<div class="row mb-4">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h2 class="page-title">Transaksi</h2>
        <p class="text-muted mb-0">Kelola semua transaksi keuangan Anda</p>
      </div>
      <div class="btn-group">
        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-plus-circle me-2"></i> Transaksi Baru
        </button>
        <ul class="dropdown-menu">
          <li>
            <a class="dropdown-item" href="{{ route('apps.transactions.create', ['type' => 'income']) }}">
              <i class="bi bi-arrow-down-left text-success me-2"></i> Pemasukan
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('apps.transactions.create', ['type' => 'expense']) }}">
              <i class="bi bi-arrow-up-right text-danger me-2"></i> Pengeluaran
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('apps.transactions.create', ['type' => 'transfer']) }}">
              <i class="bi bi-arrow-left-right text-primary me-2"></i> Transfer
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
  <div class="col-md-4 mb-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Total Pemasukan</h6>
            <h4 class="text-success mb-0">
              @money($totals['income'])
            </h4>
          </div>
          <div class="stat-icon bg-success bg-opacity-10 text-success">
            <i class="bi bi-arrow-down-left"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
    
  <div class="col-md-4 mb-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Total Pengeluaran</h6>
            <h4 class="text-danger mb-0">
              @money($totals['expense'])
            </h4>
          </div>
          <div class="stat-icon bg-danger bg-opacity-10 text-danger">
            <i class="bi bi-arrow-up-right"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
    
  <div class="col-md-4 mb-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="text-muted mb-1">Total Transfer</h6>
            <h4 class="text-primary mb-0">
              @money($totals['transfer'])
            </h4>
          </div>
          <div class="stat-icon bg-primary bg-opacity-10 text-primary">
            <i class="bi bi-arrow-left-right"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Transactions List -->
<div class="card border-0 shadow-sm">
  <div class="card-header text-bg-white border-0">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Daftar Transaksi</h5>
      <div class="d-flex justify-content-between align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#filterModal">
          <i class="bi bi-funnel-fill"></i>
        </button>
        <div class="dropdown">
          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu">
            <li>
              <a class="dropdown-item bulk-action" href="#" data-action="delete">
                <i class="bi bi-trash text-danger me-2"></i> Hapus Terpilih
              </a>
            </li>
            <li>
              <a class="dropdown-item bulk-action" href="#" data-action="export">
                <i class="bi bi-download me-2"></i> Export Terpilih
              </a>
            </li>
          </ul>
        </div>
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
                    <a href="{{ route('apps.transactions.show', $transaction) }}" class="btn btn-outline-success p-2" title="View"><i class="bi bi-eye"></i></i>
                    <a href="{{ route('apps.transactions.edit', $transaction->uuid) }}" class="btn btn-outline-primary p-2" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger" onclick="deleteItem(@json($transaction))" title="Hapus">
                      <i class="bi bi-trash"></i>
                    </button>
                    <div class="dropdown">
                      <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots"></i>
                      </button>
                      <ul class="dropdown-menu">
                        <li>
                          <a class="dropdown-item" href="#" onclick="duplicateTransaction('{{ $transaction->uuid }}')">
                            <i class="bi bi-copy me-2"></i> Duplikat
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item" href="{{ route('apps.transactions.show', $transaction) }}">
                            <i class="bi bi-eye me-2"></i> Detail
                          </a>
                        </li>
                      </ul>
                    </div>
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
        <h5 class="text-muted">Belum ada transaksi</h5>
        <p class="text-muted">Mulai dengan menambahkan transaksi pertama Anda</p>
        <div class="mt-4">
          <a href="{{ route('apps.transactions.create', ['type' => 'income']) }}" class="btn btn-success me-2 mb-2">
            <i class="bi bi-plus-circle me-2"></i> Pemasukan
          </a>
          <a href="{{ route('apps.transactions.create', ['type' => 'expense']) }}" class="btn btn-danger mb-2">
            <i class="bi bi-plus-circle me-2"></i> Pengeluaran
          </a>
        </div>
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

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Filter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('apps.transactions.index') }}" method="GET">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-3">
            <label for="type" class="form-label">Jenis</label>
            <select name="type" id="type" class="form-select">
              <option value="">Semua Jenis</option>
              @foreach(TransactionType::cases() as $type)
                <option value="{{ $type->value }}" @selected(request('type') == $type->value)>{{ $type->label() }}</option>
              @endforeach
            </select>
          </div>
                
            <div class="col-md-3">
            <label for="account_id" class="form-label">Akun</label>
            <select name="account_id" id="account_id" class="form-select select2">
              <option value="">Semua Akun</option>
              @foreach($accounts as $account)
                <option value="{{ $account->id }}" @selected(request('account_id') == $account->id)>
                  {{ $account->name }}
                </option>
              @endforeach
            </select>
          </div>
                
            <div class="col-md-3">
            <label for="category_id" class="form-label">Kategori</label>
            <select name="category_id" id="category_id" class="form-select select2">
              <option value="">Semua Kategori</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                  {{ $category->name }}
                </option>
              @endforeach
            </select>
          </div>
                
            <div class="col-md-3">
            <label for="payment_method" class="form-label">Metode Pembayaran</label>
            <select name="payment_method" id="payment_method" class="form-select">
              <option value="">Semua Metode</option>
              @foreach(PaymentMethod::cases() as $payment)
                <option value="{{ $payment->value }}" @selected(request('payment_method') == $payment->value)>{{ $payment->label() }}</option>
              @endforeach
            </select>
           </div>
                
            <div class="col-md-6">
            <label for="description" class="form-label">Keterangan / Catatan</label>
            <input type="text" name="description" id="description" class="form-control" placeholder="Cari dalam keterangan atau catatan..." value="{{ request('description') }}">
          </div>
                
            <div class="col-md-3">
            <label for="start_date" class="form-label">Dari Tanggal</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
          </div>
                
            <div class="col-md-3">
            <label for="end_date" class="form-label">Sampai Tanggal</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
          </div>
                
            <div class="col-md-12">
          </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">
            <i class="bi bi-funnel-fill me-2"></i> Apply Filter
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    function deleteItem(transaction) {
      console.log(transaction);
      const deleteModal = document.getElementById('deleteModal');
      const description = document.getElementById('transaction-description');
      const date = document.getElementById('transaction-date');
      const type = document.getElementById('transaction-type');
      const formModal = document.getElementById('form-delete');
      
      description.textContent = transaction.description;
      date.textContent = transaction.transaction_date;
      type.classList.add('bg'+ transaction.typeColor);
      typeColor.textContent = `${transaction.typeLabel}: ${transaction.formattedAmount}`;
      formModal.action = `{{ config('app.url') }}/apps/transactions/${transaction.uuid}/destroy`;
      
      const modal = new bootstrap.Modal(deleteModal);
      modal.show();
      
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Select All functionality
        const selectAll = document.getElementById('selectAll');
        const transactionChecks = document.querySelectorAll('.transaction-check');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                transactionChecks.forEach(check => {
                    check.checked = isChecked;
                });
            });
        }
        
        // Filter collapse functionality
        const filterCollapse = document.getElementById('filterCollapse');
        const filterCard = document.getElementById('filterCard');
        
        if (filterCollapse) {
            filterCollapse.addEventListener('show.bs.collapse', function () {
                filterCard.classList.remove('collapsed');
            });
            
            filterCollapse.addEventListener('hide.bs.collapse', function () {
                filterCard.classList.add('collapsed');
            });
        }
        
        // Bulk Actions
        const bulkActions = document.querySelectorAll('.bulk-action');
        bulkActions.forEach(action => {
            action.addEventListener('click', function(e) {
                e.preventDefault();
                
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
                        
                    case 'export':
                        bulkExport(selectedTransactions);
                        break;
                }
            });
        });
        
        // Bulk Delete
        function bulkDelete(ids) {
            fetch("{{ secure_url(config('app.url') . '/apps/transactions/bulk-delete') }}", {
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
        
        // Bulk Export
        function bulkExport(ids) {
            fetch("{{ secure_url(config('app.url') . '/apps/transactions/export') }}", {
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
                    if(data.download_url){
                      const a = document.createElement('a');
                      a.href = data.download_url;
                      a.download = data.filename;
                      document.body.appendChild(a);
                      a.click();
                      document.body.removeChild(a);
          
                      alert('File berhasil dibuat.')
                    }
                    
                    Array.from(transactionChecks).map(check => {
                      check.checked = !check.checked;
                    });
                    selectAll.checked = !selectAll.checked;
                } else {
                    alert('Gagal mengekspor data transaksi: ' + data.message);
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan: ' + error.message);
            });
        }
        
        // Duplicate Transaction
        window.duplicateTransaction = function(uuid) {
            if (confirm('Apakah Anda ingin menduplikasi transaksi ini?')) {
                fetch("{{ secure_url(config('app.url').'/apps/transactions/') }}" + uuid + '{{ "/duplicate" }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Transaksi berhasil diduplikasi!');
                        location.reload();
                    } else {
                        alert('Gagal menduplikasi: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Terjadi kesalahan: ' + error.message);
                });
            }
        };
        
        // Auto-submit filter on date change
        const dateInputs = ['start_date', 'end_date'];
        dateInputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('change', function() {
                    if (this.value) {
                        document.getElementById('filterForm').submit();
                    }
                });
            }
        });
    });
</script>
@endpush