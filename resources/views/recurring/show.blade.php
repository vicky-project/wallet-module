@extends('wallet::layouts.app')

@section('title', 'Detail Transaksi Rutin - ' . config('app.name', 'Vicky Server'))

@section('content')
@include('wallet::partials.fab')
    <!-- Detail Header -->
    <div class="detail-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">{{ $recurringTransaction->description }}</h1>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-{{ $recurringTransaction->type == 'income' ? 'success' : ($recurringTransaction->type == 'expense' ? 'danger' : 'primary') }} fs-6">
                        {{ $recurringTransaction->type == 'income' ? 'Pemasukan' : ($recurringTransaction->type == 'expense' ? 'Pengeluaran' : 'Transfer') }}
                    </span>
                    <span class="badge bg-light text-dark fs-6">
                        <i class="bi {{ $recurringTransaction->category->icon ?? 'bi-arrow-repeat' }} me-1"></i>
                        {{ $recurringTransaction->category->name ?? 'N/A' }}
                    </span>
                    <span class="badge {{ $recurringTransaction->is_active ? 'bg-success' : 'bg-secondary' }} fs-6">
                        <i class="bi {{ $recurringTransaction->is_active ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>
                        {{ $recurringTransaction->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <h2 class="fw-bold mb-0 currency">{{ $recurringTransaction->amount }}</h2>
                <p class="mb-0 opacity-75">Per transaksi</p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <div class="btn-group">
                <a href="{{ route('wallet.recurring.edit', $recurringTransaction->id) }}" class="btn btn-light btn-sm">
                    <i class="bi bi-pencil"></i>
                </a>
                <button class="btn btn-light btn-sm toggle-status-btn"
                        data-id="{{ $recurringTransaction->id }}"
                        data-status="{{ $recurringTransaction->is_active ? 1 : 0 }}">
                    <i class="bi bi-power"></i>
                </button>
                <button class="btn btn-light btn-sm" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#" id="previewScheduleBtn">
                            <i class="bi bi-calendar-week me-2"></i> Lihat Jadwal
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" id="processNowBtn">
                            <i class="bi bi-play-circle me-2"></i> Proses Sekarang
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('wallet.recurring.destroy', $recurringTransaction->id) }}" 
                              method="POST" 
                              class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="dropdown-item text-danger delete-btn">
                                <i class="bi bi-trash me-2"></i> Hapus
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="detail-stats">
        <div class="stat-card">
            <div class="text-primary mb-2">
                <i class="bi bi-arrow-repeat fs-1"></i>
            </div>
            <h3 class="mb-1">{{ $recurringTransaction->frequency }}</h3>
            <p class="text-muted mb-0">Frekuensi</p>
        </div>
        
        <div class="stat-card">
            <div class="text-success mb-2">
                <i class="bi bi-check-circle fs-1"></i>
            </div>
            <h3 class="mb-1">{{ $recurringTransaction->transactions_count ?? 0 }}</h3>
            <p class="text-muted mb-0">Total Diproses</p>
        </div>
        
        <div class="stat-card">
            <div class="text-warning mb-2">
                <i class="bi bi-calendar-event fs-1"></i>
            </div>
            <h3 class="mb-1">
                @if($recurringTransaction->remaining_occurrences)
                    {{ $recurringTransaction->remaining_occurrences }}
                @else
                    ∞
                @endif
            </h3>
            <p class="text-muted mb-0">Sisa Pengulangan</p>
        </div>
        
        <div class="stat-card">
            <div class="text-info mb-2">
                <i class="bi bi-calendar-date fs-1"></i>
            </div>
            <h3 class="mb-1">
                @php
                    $nextDate = $recurringTransaction->next_occurrence ?? \Carbon\Carbon::parse($recurringTransaction->start_date);
                    echo $nextDate->format('d M');
                @endphp
            </h3>
            <p class="text-muted mb-0">Berikutnya</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Information -->
        <div class="col-lg-8">
            <div class="row">
                <!-- Transaction Information -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle me-2"></i> Informasi Transaksi
                            </h5>
                        </div>
                        <div class="card-body pt-0">
                            <div class="info-row">
                                <span class="text-muted">Akun</span>
                                <span class="fw-medium">
                                    <i class="bi bi-wallet2 me-1"></i>
                                    {{ $recurringTransaction->account->name ?? 'N/A' }}
                                </span>
                            </div>
                            
                            @if($recurringTransaction->type == 'transfer' && $recurringTransaction->toAccount)
                                <div class="info-row">
                                    <span class="text-muted">Akun Tujuan</span>
                                    <span class="fw-medium">
                                        <i class="bi bi-arrow-right me-1"></i>
                                        {{ $recurringTransaction->toAccount->name }}
                                    </span>
                                </div>
                            @endif
                            
                            <div class="info-row">
                                <span class="text-muted">Kategori</span>
                                <span class="fw-medium">
                                    <i class="bi {{ $recurringTransaction->category->icon ?? 'bi-tag' }} me-1"></i>
                                    {{ $recurringTransaction->category->name ?? 'N/A' }}
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-muted">Jumlah</span>
                                <span class="fw-bold currency">{{ $recurringTransaction->amount }}</span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-muted">Deskripsi</span>
                                <span class="fw-medium">{{ $recurringTransaction->description }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recurrence Information -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-calendar3 me-2"></i> Informasi Pengulangan
                            </h5>
                        </div>
                        <div class="card-body pt-0">
                            <div class="info-row">
                                <span class="text-muted">Frekuensi</span>
                                <span class="fw-medium">
                                    <span class="recurring-badge bg-primary text-white">
                                        {{ $recurringTransaction->frequency }}
                                    </span>
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-muted">Interval</span>
                                <span class="fw-medium">
                                    Setiap {{ $recurringTransaction->interval }} 
                                    {{ $recurringTransaction->frequency == 'daily' ? 'hari' : 
                                       ($recurringTransaction->frequency == 'weekly' ? 'minggu' : 
                                       ($recurringTransaction->frequency == 'monthly' ? 'bulan' : 
                                       ($recurringTransaction->frequency == 'quarterly' ? 'triwulan' : 'tahun'))) }}
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-muted">Dimulai</span>
                                <span class="fw-medium">
                                    {{ \Carbon\Carbon::parse($recurringTransaction->start_date)->format('d M Y') }}
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-muted">Berakhir</span>
                                <span class="fw-medium">
                                    @if($recurringTransaction->end_date)
                                        {{ \Carbon\Carbon::parse($recurringTransaction->end_date)->format('d M Y') }}
                                    @else
                                        <span class="text-success">Tidak Ada</span>
                                    @endif
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="text-muted">Dibuat</span>
                                <span class="fw-medium">
                                    {{ $recurringTransaction->created_at->format('d M Y H:i') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="card mt-4">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history me-2"></i> Riwayat Transaksi
                        </h5>
                        <span class="badge bg-primary">
                            {{ $relatedTransactions->total() }} transaksi
                        </span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @if($relatedTransactions->count() > 0)
                        <div class="transaction-history">
                            <div class="timeline">
                                @foreach($relatedTransactions as $transaction)
                                    <div class="timeline-item">
                                        <div class="card mb-2">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-0">{{ $transaction->description }}</h6>
                                                        <small class="text-muted">
                                                            {{ $transaction->transaction_date->format('d M Y H:i') }}
                                                        </small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold {{ $transaction->type == 'income' ? 'text-success' : 'text-danger' }} currency">
                                                            {{ $transaction->amount }}
                                                        </span>
                                                        <br>
                                                        <small class="text-muted">{{ $transaction->account->name ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3">
                            {{ $relatedTransactions->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-receipt text-muted" style="font-size: 2rem;"></i>
                            <p class="mt-3 mb-2">Belum ada transaksi yang diproses</p>
                            <small class="text-muted">Transaksi akan dibuat sesuai jadwal pengulangan</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Next Occurrences -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-calendar-week me-2"></i> Jadwal Berikutnya
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <div id="nextOccurrences">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-lightning me-2"></i> Aksi Cepat
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <div class="d-grid gap-2">
                        <a href="{{ route('wallet.transactions.create') }}?recurring_id={{ $recurringTransaction->id }}" 
                           class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-2"></i> Buat Transaksi Manual
                        </a>
                        
                        @if($recurringTransaction->is_active)
                            <button class="btn btn-outline-warning toggle-status-btn"
                                    data-id="{{ $recurringTransaction->id }}"
                                    data-status="1">
                                <i class="bi bi-power me-2"></i> Nonaktifkan
                            </button>
                        @else
                            <button class="btn btn-outline-success toggle-status-btn"
                                    data-id="{{ $recurringTransaction->id }}"
                                    data-status="0">
                                <i class="bi bi-power me-2"></i> Aktifkan
                            </button>
                        @endif
                        
                        <a href="{{ route('wallet.recurring.edit', $recurringTransaction->id) }}" 
                           class="btn btn-outline-info">
                            <i class="bi bi-pencil me-2"></i> Edit Transaksi
                        </a>
                        
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash me-2"></i> Hapus Transaksi
                        </button>
                    </div>
                </div>
            </div>
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
                <p>Apakah Anda yakin ingin menghapus transaksi rutin ini?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Peringatan:</strong> Transaksi yang sudah diproses tidak akan terpengaruh, 
                    tetapi transaksi mendatang tidak akan dibuat lagi.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('wallet.recurring.destroy', $recurringTransaction->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Preview Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Jadwal Pengulangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="scheduleContent">
                    <!-- Content will be loaded via AJAX -->
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

    // Load next occurrences
    function loadNextOccurrences() {
        const occurrencesContainer = document.getElementById('nextOccurrences');
        const recurringId = '{{ $recurringTransaction->id }}';
        
        fetch('{{ route("apps.recurring.preview-occurrences", "") }}/' + recurringId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.occurrences) {
                    let html = '';
                    
                    if (data.occurrences.length > 0) {
                        html = '<div class="timeline">';
                        
                        data.occurrences.forEach((occurrence, index) => {
                            const isToday = occurrence.is_today;
                            const isUpcoming = !isToday && index === 0;
                            
                            html += `
                                <div class="timeline-item">
                                    <div class="card mb-2 ${isToday ? 'border-success' : isUpcoming ? 'border-primary' : ''}">
                                        <div class="card-body py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0 ${isToday ? 'text-success' : ''}">
                                                        ${occurrence.date_formatted}
                                                    </h6>
                                                    <small class="text-muted">${occurrence.day_name}</small>
                                                </div>
                                                <div>
                                                    ${isToday ? 
                                                        '<span class="badge bg-success">Hari Ini</span>' : 
                                                        isUpcoming ? 
                                                        '<span class="badge bg-primary">Berikutnya</span>' : 
                                                        `<span class="badge bg-light text-dark">${occurrence.days_until} hari</span>`
                                                    }
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                    } else {
                        html = `
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 2rem;"></i>
                                <p class="mt-3 mb-2">Tidak ada jadwal transaksi berikutnya</p>
                                <small class="text-muted">Transaksi telah berakhir atau nonaktif</small>
                            </div>
                        `;
                    }
                    
                    occurrencesContainer.innerHTML = html;
                } else {
                    occurrencesContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            ${data.message || 'Gagal memuat data'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                occurrencesContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Terjadi kesalahan saat memuat data
                    </div>
                `;
            });
    }
    
    // Initial load
    loadNextOccurrences();

    // Toggle status
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const currentStatus = this.dataset.status === '1';
            
            const message = currentStatus 
                ? 'Apakah Anda yakin ingin menonaktifkan transaksi ini?' 
                : 'Apakah Anda yakin ingin mengaktifkan transaksi ini?';
            
            if (confirm(message)) {
                fetch('{{ route("apps.recurring.toggle-status", "") }}/' + id, {
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
        });
    });

    // Preview schedule
    const previewScheduleBtn = document.getElementById('previewScheduleBtn');
    const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    
    if (previewScheduleBtn) {
        previewScheduleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loadSchedulePreview();
            scheduleModal.show();
        });
    }

    function loadSchedulePreview() {
        const scheduleContent = document.getElementById('scheduleContent');
        const recurringId = '{{ $recurringTransaction->id }}';
        
        scheduleContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        fetch('{{ route("apps.recurring.preview-occurrences", "") }}/' + recurringId + '?count=20')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.occurrences) {
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Hari</th>
                                        <th>Status</th>
                                        <th>Hari Lagi</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    data.occurrences.forEach((occurrence, index) => {
                        html += `
                            <tr class="${occurrence.is_today ? 'table-success' : ''}">
                                <td>${index + 1}</td>
                                <td>${occurrence.date_formatted}</td>
                                <td>${occurrence.day_name}</td>
                                <td>
                                    <span class="badge ${occurrence.is_today ? 'bg-success' : 'bg-primary'}">
                                        ${occurrence.status}
                                    </span>
                                </td>
                                <td>${occurrence.days_until}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    
                    scheduleContent.innerHTML = html;
                } else {
                    scheduleContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            ${data.message || 'Gagal memuat data'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                scheduleContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Terjadi kesalahan saat memuat data
                    </div>
                `;
            });
    }

    // Process now button
    const processNowBtn = document.getElementById('processNowBtn');
    if (processNowBtn) {
        processNowBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Proses transaksi ini sekarang?')) {
                // This would be an AJAX call in production
                alert('Fitur proses sekarang akan diimplementasikan nanti');
            }
        });
    }

    // Delete confirmation
    const deleteBtns = document.querySelectorAll('.delete-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Apakah Anda yakin ingin menghapus transaksi rutin ini?')) {
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    });
});
</script>
@endpush

@push('styles')
<style>
    .detail-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }
    
    .detail-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.1);
    }
    
    .detail-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    body[data-bs-theme="dark"] .stat-card {
        background-color: #1e1e1e;
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #3b82f6;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 25px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -34px;
        top: 5px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #3b82f6;
        border: 3px solid white;
        z-index: 1;
    }
    
    body[data-bs-theme="dark"] .timeline-item::before {
        border-color: #1e1e1e;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    body[data-bs-theme="dark"] .info-row {
        border-bottom-color: rgba(255, 255, 255, 0.05);
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .action-buttons {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 1;
    }
    
    .recurring-badge {
        font-size: 0.8rem;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
        margin-right: 5px;
    }
    
    .transaction-history {
        max-height: 400px;
        overflow-y: auto;
    }
</style>
@endpush