@extends('wallet::layouts.app')

@section('title', 'Transaksi Rutin - ' . config('app.name', 'Vicky Server'))

@use('Modules\Wallet\Enums\RecurringFreq')
@use('Modules\Wallet\Enums\TransactionType')

@push('styles')
<style>
    .recurring-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    
    .recurring-card.income {
        border-left-color: #10b981;
    }
    
    .recurring-card.expense {
        border-left-color: #ef4444;
    }
    
    .recurring-card.transfer {
        border-left-color: #3b82f6;
    }
    
    .frequency-badge {
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 12px;
    }
    
    .frequency-daily {
        background-color: rgba(139, 92, 246, 0.1);
        color: #8b5cf6;
    }
    
    .frequency-weekly {
        background-color: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }
    
    .frequency-monthly {
        background-color: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }
    
    .frequency-quarterly {
        background-color: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }
    
    .frequency-yearly {
        background-color: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }
    
    .status-badge {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }
    
    .status-active {
        background-color: #10b981;
    }
    
    .status-inactive {
        background-color: #6b7280;
    }
    
    .next-occurrence {
        background-color: rgba(59, 130, 246, 0.1);
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.875rem;
    }
    
    .occurrence-timeline {
        position: relative;
        padding-left: 20px;
    }
    
    .occurrence-timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e5e7eb;
    }
    
    .occurrence-item {
        position: relative;
        margin-bottom: 15px;
    }
    
    .occurrence-item::before {
        content: '';
        position: absolute;
        left: -23px;
        top: 6px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #3b82f6;
        border: 2px solid white;
    }
    
    body[data-bs-theme="dark"] .occurrence-timeline::before {
        background-color: #374151;
    }
    
    body[data-bs-theme="dark"] .occurrence-item::before {
        border-color: #1f2937;
    }
    
    .action-dropdown {
        min-width: 200px;
    }
    
    .bulk-actions {
        background-color: #f9fafb;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: none;
    }
    
    body[data-bs-theme="dark"] .bulk-actions {
        background-color: #1f2937;
    }
    
    .bulk-actions.active {
        display: block;
    }
    
    .filter-card {
        transition: all 0.3s ease;
        overflow: hidden;
        max-height: 0;
        opacity: 0;
    }
    
    .filter-card.active {
        max-height: 500px;
        opacity: 1;
        margin-bottom: 20px;
    }
    
    .recurring-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .recurring-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@include('wallet::partials.fab')
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="page-title mb-1">Transaksi Rutin</h1>
    <p class="text-muted mb-0">Kelola transaksi berulang secara otomatis</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" id="toggleFilters">
      <i class="bi bi-funnel"></i> Filter
    </button>
    <button class="btn btn-outline-success btn-sm d-flex align-items-center gap-1" id="processDueBtn">
      <i class="bi bi-play-circle"></i> Proses
    </button>
    <a href="{{ route('apps.recurrings.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
      <i class="bi bi-plus-lg"></i> Tambah
    </a>
  </div>
</div>

<!-- Filters Card -->
<div class="card filter-card mb-3" id="filterCard">
  <div class="card-body">
    <form method="GET" action="{{ route('apps.recurrings.index') }}" id="filterForm">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label small">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Tipe</label>
          <select name="type" class="form-select form-select-sm">
            <option value="">Semua Tipe</option>
            @foreach(TransactionType::cases() as $type)
              <option value="{{ $type->value }}" @selected(request('type') == $type->value)>{{ $type->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Frekuensi</label>
          <select name="frequency" class="form-select form-select-sm">
            <option value="">Semua Frekuensi</option>
            @foreach(RecurringFreq::cases() as $freq)
              <option value="{{ $freq->value }}" @selected(request('frequency') == $freq->value)>{{ $freq->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Pencarian</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari deskripsi..." value="{{ request('search') }}">
        </div>
      </div>
      <div class="row mt-3">
        <div class="col-12">
          <div class="d-flex justify-content-end gap-2">
            <button type="reset" class="btn btn-sm btn-outline-secondary" id="resetFilters">
              Reset
            </button>
            <button type="submit" class="btn btn-sm btn-primary">
              Terapkan Filter
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Stats Overview -->
@if(isset($stats) && !empty($stats))
  <div class="recurring-stats">
    <div class="card">
      <div class="card-body py-3">
        <div class="d-flex align-items-center">
          <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
            <i class="bi bi-arrow-repeat text-primary"></i>
          </div>
          <div>
            <h5 class="mb-0">{{ $stats['total'] ?? 0 }}</h5>
            <small class="text-muted">Total Transaksi Rutin</small>
          </div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-body py-3">
        <div class="d-flex align-items-center">
          <div class="bg-success bg-opacity-10 p-2 rounded me-3">
            <i class="bi bi-check-circle text-success"></i>
          </div>
          <div>
            <h5 class="mb-0">{{ $stats['active'] ?? 0 }}</h5>
            <small class="text-muted">Aktif</small>
          </div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-body py-3">
        <div class="d-flex align-items-center">
          <div class="bg-warning bg-opacity-10 p-2 rounded me-3">
            <i class="bi bi-calendar-event text-warning"></i>
          </div>
          <div>
            <h5 class="mb-0">{{ $stats['due_soon'] ?? 0 }}</h5>
            <small class="text-muted">Akan Jatuh Tempo</small>
          </div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-body py-3">
        <div class="d-flex align-items-center">
          <div class="bg-info bg-opacity-10 p-2 rounded me-3">
            <i class="bi bi-cash-stack text-info"></i>
          </div>
          <div>
            <h5 class="mb-0">@money($stats['total_amount'] ?? 0)</h5>
            <small class="text-muted">Total Nilai</small>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif

<!-- Bulk Actions -->
<div class="bulk-actions" id="bulkActions">
  <div class="row">
    <div class="col-md-6 mb-2">
    <span class="fw-medium" id="selectedCount">0 item dipilih</span>
    </div>
    <div class="col-md-6 mb-2">
      <div class="d-flex justify-content-between align-items-center gap-2">
        <button class="btn btn-sm btn-outline-secondary" id="bulkActivate">
        <i class="bi bi-check-circle"></i> Aktifkan
      </button>
        <button class="btn btn-sm btn-outline-secondary" id="bulkDeactivate">
        <i class="bi bi-x-circle"></i> Nonaktifkan
      </button>
        <button class="btn btn-sm btn-outline-danger" id="bulkDelete">
        <i class="bi bi-trash"></i> Hapus
      </button>
        <button class="btn btn-sm btn-outline-secondary" id="cancelBulk">
        <i class="bi bi-x-lg"></i> Batal
      </button>
      </div>
    </div>
  </div>
</div>

<!-- Recurring Transactions List -->
<div class="card">
  <div class="card-header bg-transparent border-0">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Daftar Transaksi Rutin</h5>
      <div class="d-flex align-items-center gap-2">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="selectAll">
          <label class="form-check-label small" for="selectAll">Pilih Semua</label>
        </div>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-download"></i> Ekspor
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#" id="exportJSON"><i class="bi bi-filetype-json"></i> JSON</a></li>
            <li><a class="dropdown-item" href="#" id="exportCSV"><i class="bi bi-filetype-csv"></i> CSV</a></li>
            <li><a class="dropdown-item" href="#" id="exportPDF"><i class="bi bi-filetype-pdf"></i> PDF</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="card-body pt-0">
    @if($recurringTransactions->count() > 0)
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th width="50"></th>
              <th>Deskripsi</th>
              <th>Tipe</th>
              <th>Frekuensi</th>
              <th>Jumlah</th>
              <th>Status</th>
              <th>Berikutnya</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recurringTransactions as $recurring)
              <tr class="recurring-card {{ $recurring->type->value }}">
                <td>
                  <div class="form-check">
                    <input class="form-check-input recurring-checkbox" type="checkbox" value="{{ $recurring->id }}" data-recurring-id="{{ $recurring->id }}">
                  </div>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="rounded-circle p-2 me-3 bg-light">
                      <i class="bi {{ $recurring->category->icon ?? 'bi-arrow-repeat' }}"></i>
                    </div>
                    <div>
                      <h6 class="mb-0">{{ $recurring->description }}</h6>
                      <small class="text-muted">
                        {{ $recurring->account->name ?? 'N/A' }}
                        @if($recurring->type == TransactionType::TRANSFER && $recurring->toAccount)
                          → {{ $recurring->toAccount->name }}
                        @endif
                      </small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-{{ $recurring->type == TransactionType::INCOME ? 'success' : ($recurring->type == TransactionType::EXPENSE ? 'danger' : 'primary') }}-subtle text-{{ $recurring->type == TransactionType::INCOME ? 'success' : ($recurring->type == TransactionType::EXPENSE ? 'danger' : 'primary') }}">
                    {{ $recurring->type->label() }}
                  </span>
                </td>
                <td>
                  <span class="frequency-badge frequency-{{ $recurring->frequency->value }}">
                    @if($recurring->interval > 1)
                      Setiap {{ $recurring->interval }} 
                    @endif
                    {{ $recurring->frequency->label() }}
                  </span>
                </td>
                <td>
                  <div class="fw-bold currency">{{ $recurring->amount }}</div>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <span class="status-badge status-{{ $recurring->is_active ? 'active' : 'inactive' }}"></span>
                    <span>{{ $recurring->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                  </div>
                </td>
                <td>
                  @php
                    $nextDate = $recurring->next_occurrence ?? \Carbon\Carbon::parse($recurring->start_date);
                    $now = \Carbon\Carbon::now();
                    $diffDays = $now->diffInDays($nextDate, false);
                  @endphp
                  @if($recurring->is_active)
                    <div class="next-occurrence">
                      <i class="bi bi-calendar3"></i>
                      {{ $nextDate->format('d M Y') }}
                      @if($diffDays >= 0)
                        <small class="d-block text-muted">
                          @if($diffDays == 0)
                            <span class="text-success">Hari ini</span>
                          @elseif($diffDays == 1)
                            Besok
                          @else
                            {{ $diffDays }} hari lagi
                          @endif
                        </small>
                      @endif
                    </div>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end action-dropdown">
                      <li>
                        <a class="dropdown-item" href="{{ route('apps.recurrings.show', $recurring->id) }}">
                          <i class="bi bi-eye me-2"></i> Detail
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="{{ route('apps.recurrings.edit', $recurring->id) }}">
                          <i class="bi bi-pencil me-2"></i> Edit
                        </a>
                      </li>
                      <li>
                        <button class="dropdown-item toggle-status-btn" data-id="{{ $recurring->id }}" data-status="{{ $recurring->is_active ? 1 : 0 }}">
                          <i class="bi bi-power me-2"></i>
                          {{ $recurring->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                      </li>
                      <li>
                        <button class="dropdown-item text-primary preview-occurrences-btn" data-id="{{ $recurring->id }}">
                          <i class="bi bi-calendar-week me-2"></i>
                          Preview Jadwal
                        </button>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <form action="{{ route('apps.recurrings.destroy', $recurring->id) }}" method="POST" class="d-inline delete-form">
                          @csrf
                          @method('DELETE')
                          <button type="button" class="dropdown-item text-danger delete-btn">
                            <i class="bi bi-trash me-2"></i> Hapus
                          </button>
                        </form>
                      </li>
                    </ul>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="text-muted small">
          Menampilkan {{ $recurringTransactions->firstItem() }} - {{ $recurringTransactions->lastItem() }} 
          dari {{ $recurringTransactions->total() }} transaksi
        </div>
        <div>
          {{ $recurringTransactions->links() }}
        </div>
      </div>
    @else
      <div class="empty-state py-5 text-center">
        <i class="bi bi-arrow-repeat text-muted" style="font-size: 3rem;"></i>
        <h5 class="mt-3 mb-2">Belum ada transaksi rutin</h5>
        <p class="text-muted mb-4">Mulai dengan membuat transaksi rutin pertama Anda</p>
        <a href="{{ route('apps.recurrings.create') }}" class="btn btn-primary">
          <i class="bi bi-plus-lg me-2"></i> Tambah Transaksi Rutin
        </a>
      </div>
    @endif
  </div>
</div>

<!-- Upcoming Transactions Preview (Modal) -->
<div class="modal fade" id="occurrencesModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Jadwal Transaksi Berikutnya</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="occurrencesContent">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format currency
    document.querySelectorAll('.currency').forEach(element => {
        const value = element.textContent;
        if (!isNaN(value)) {
            element.textContent = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        }
    });

    // Toggle filters
    const toggleFiltersBtn = document.getElementById('toggleFilters');
    const filterCard = document.getElementById('filterCard');
    
    if (toggleFiltersBtn && filterCard) {
        toggleFiltersBtn.addEventListener('click', function() {
            filterCard.classList.toggle('active');
            const icon = this.querySelector('i');
            if (filterCard.classList.contains('active')) {
                icon.classList.remove('bi-funnel');
                icon.classList.add('bi-funnel-fill');
            } else {
                icon.classList.remove('bi-funnel-fill');
                icon.classList.add('bi-funnel');
            }
        });
    }

    // Reset filters
    const resetFiltersBtn = document.getElementById('resetFilters');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            document.getElementById('filterForm').reset();
            window.location.href = '{{ route('apps.recurrings.index') }}';
        });
    }

    // Bulk actions
    const selectAllCheckbox = document.getElementById('selectAll');
    const recurringCheckboxes = document.querySelectorAll('.recurring-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const cancelBulkBtn = document.getElementById('cancelBulk');

    let selectedIds = [];

    // Select all functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            recurringCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateSelectedIds();
        });
    }

    // Update selected IDs
    function updateSelectedIds() {
        selectedIds = [];
        recurringCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedIds.push(checkbox.dataset.recurringId);
            }
        });
        
        if (selectedIds.length > 0) {
            bulkActions.classList.add('active');
            selectedCount.textContent = `${selectedIds.length} item dipilih`;
        } else {
            bulkActions.classList.remove('active');
        }
    }

    // Checkbox change events
    recurringCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedIds);
    });

    // Cancel bulk selection
    if (cancelBulkBtn) {
        cancelBulkBtn.addEventListener('click', function() {
            recurringCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAllCheckbox.checked = false;
            updateSelectedIds();
        });
    }

    // Bulk activate
    const bulkActivateBtn = document.getElementById('bulkActivate');
    if (bulkActivateBtn) {
        bulkActivateBtn.addEventListener('click', function() {
            if (selectedIds.length === 0) {
                alert('Pilih minimal satu transaksi rutin');
                return;
            }
            
            if (confirm(`Aktifkan ${selectedIds.length} transaksi rutin?`)) {
                bulkUpdateStatus(selectedIds, true);
            }
        });
    }

    // Bulk deactivate
    const bulkDeactivateBtn = document.getElementById('bulkDeactivate');
    if (bulkDeactivateBtn) {
        bulkDeactivateBtn.addEventListener('click', function() {
            if (selectedIds.length === 0) {
                alert('Pilih minimal satu transaksi rutin');
                return;
            }
            
            if (confirm(`Nonaktifkan ${selectedIds.length} transaksi rutin?`)) {
                bulkUpdateStatus(selectedIds, false);
            }
        });
    }

    // Bulk delete
    const bulkDeleteBtn = document.getElementById('bulkDelete');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            if (selectedIds.length === 0) {
                alert('Pilih minimal satu transaksi rutin');
                return;
            }
            
            if (confirm(`Hapus ${selectedIds.length} transaksi rutin? Tindakan ini tidak dapat dibatalkan.`)) {
                bulkDeleteTransactions(selectedIds);
            }
        });
    }

    // Bulk update status
    function bulkUpdateStatus(ids, activate) {
        fetch('{{ route("apps.recurrings.bulk-update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                ids: ids,
                action: activate ? 'activate' : 'deactivate'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal memperbarui status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memperbarui status');
        });
    }

    // Bulk delete transactions
    function bulkDeleteTransactions(ids) {
        fetch('{{ route("apps.recurrings.bulk-delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menghapus transaksi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus transaksi');
        });
    }

    // Toggle individual status
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const currentStatus = this.dataset.status === '1';
            
            if (confirm(`Apakah Anda yakin ingin ${currentStatus ? 'menonaktifkan' : 'mengaktifkan'} transaksi ini?`)) {
                toggleStatus(id);
            }
        });
    });

    // Toggle status function
    function toggleStatus(id) {
        fetch('{{ secure_url(config("app.url")."/apps/recurrings/toggle-status") }}/' + id, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal mengubah status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengubah status');
        });
    }

    // Process due transactions
    const processDueBtn = document.getElementById('processDueBtn');
    if (processDueBtn) {
        processDueBtn.addEventListener('click', function() {
            if (confirm('Proses transaksi rutin yang jatuh tempo hari ini?')) {
                window.location.href = '{{ route("apps.recurrings.process-due") }}';
            }
        });
    }

    // Delete confirmation
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.delete-form');
            
            if (confirm('Apakah Anda yakin ingin menghapus transaksi rutin ini?')) {
                form.submit();
            }
        });
    });

    // Preview occurrences
    const occurrencesModal = new bootstrap.Modal(document.getElementById('occurrencesModal'));
    document.querySelectorAll('.preview-occurrences-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            loadOccurrences(id);
            occurrencesModal.show();
        });
    });

    // Load occurrences
    function loadOccurrences(id) {
        const content = document.getElementById('occurrencesContent');
        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        fetch('{{ secure_url(config("app.url") . "/apps/recurrings/preview-occurrences") }}/' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '';
                    
                    if (data.occurrences && data.occurrences.length > 0) {
                        html = `
                            <div class="occurrence-timeline">
                                ${data.occurrences.map((occurrence, index) => `
                                    <div class="occurrence-item">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-0">${occurrence.date_formatted}</h6>
                                                <small class="text-muted">${occurrence.day_name}</small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge ${occurrence.is_today ? 'bg-success' : 'bg-primary'}">
                                                    ${occurrence.status}
                                                </span>
                                            </div>
                                        </div>
                                        ${index < data.occurrences.length - 1 ? '<hr class="my-2">' : ''}
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    } else {
                        html = `
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                                <p class="mt-3">Tidak ada jadwal transaksi berikutnya</p>
                            </div>
                        `;
                    }
                    
                    content.innerHTML = html;
                } else {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Terjadi kesalahan saat memuat data
                    </div>
                `;
            });
    }

    // Export functionality
    document.getElementById('exportJSON').addEventListener('click', function(e) {
        e.preventDefault();
        exportData('json');
    });

    document.getElementById('exportCSV').addEventListener('click', function(e) {
        e.preventDefault();
        exportData('csv');
    });

    document.getElementById('exportPDF').addEventListener('click', function(e) {
        e.preventDefault();
        exportData('pdf');
    });

    function exportData(format) {
        const filters = {
            status: document.querySelector('[name="status"]').value,
            type: document.querySelector('[name="type"]').value,
            frequency: document.querySelector('[name="frequency"]').value,
            search: document.querySelector('[name="search"]').value
        };

        fetch('{{ route("apps.recurrings.export") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(filters)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create download link
                const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `recurring-transactions-${new Date().toISOString().split('T')[0]}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } else {
                alert('Gagal mengekspor data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengekspor data', error.message);
        });
    }
});
</script>
@endpush