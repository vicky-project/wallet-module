@extends('wallet::layouts.app')

@section('title', 'Detail Tag: ' . $tag->name)

@use('Modules\Wallet\Enums\TransactionType')

@section('content')
@include('wallet::partials.fab')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div class="btn-group">
    <a href="{{ route('apps.tags.index') }}" class="btn btn-secondary" role="button">
      <i class="bi bi-arrow-left me-1"></i>
      Back
    </a>
    <a href="{{ route('apps.tags.edit', $tag) }}" class="btn btn-warning" role="button">
      <i class="bi bi-pencil me-1"></i> Edit
    </a>
    <button type="button" class="btn btn-warning dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
      <span class="visually-hidden">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu">
      <li>
        <button class="dropdown-item text-danger" onclick="confirmDelete({{ $tag->id }}, '{{ $tag->name }}')">
          <i class="bi bi-trash me-2"></i> Hapus
        </button>
      </li>
    </ul>
  </div>
</div>

<div class="row mb-2">
  <!-- Tag Info -->
  <div class="col-md-4 mb-4">
    <div class="card">
      <div class="card-body text-center">
        <div class="mb-4">
          <span class="badge rounded-pill" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}; border: 2px solid {{ $tag->color }}; font-size: 1.25rem; padding: 0.75rem 1.5rem;">
            @if($tag->icon)
              <i class="bi {{ $tag->icon }} me-2"></i>
            @endif
            {{ $tag->name }}
          </span>
        </div>
                
        <div class="row mb-3">
          <div class="col-6">
            <div class="stat-box">
              <h3 class="text-primary">{{ $tag->transactions_count }}</h3>
              <small class="text-muted">Total Transaksi</small>
            </div>
          </div>
          <div class="col-6">
            <div class="stat-box">
              <h3 class="text-success">
                @money($transactions->sum('amount'))
              </h3>
              <small class="text-muted">Total Nilai</small>
            </div>
          </div>
        </div>
                
        <div class="text-start">
          <div class="mb-2">
            <i class="bi bi-palette me-2 text-muted"></i>
            <strong>Warna:</strong> 
            <span class="badge" style="background-color: {{ $tag->color }};">
              {{ $tag->color }}
            </span>
          </div>
                    
          <div class="mb-2">
            <i class="bi bi-calendar me-2 text-muted"></i>
            <strong>Dibuat:</strong> 
            {{ $tag->created_at->translatedFormat('d F Y') }}
          </div>
                    
          <div class="mb-2">
            <i class="bi bi-clock me-2 text-muted"></i>
            <strong>Diperbarui:</strong> 
              {{ $tag->updated_at->translatedFormat('d F Y H:i') }}
          </div>
                    
          @if($tag->icon)
            <div class="mb-2">
              <i class="bi {{ $tag->icon }} me-2 text-muted"></i>
              <strong>Ikon:</strong> {{ $tag->icon }}
            </div>
          @endif
        </div>
                
        <div class="mt-4">
          <a href="{{ route('apps.transactions.index', ['tag' => $tag->id]) }}" class="btn btn-outline-primary w-100">
            <i class="bi bi-arrow-right-circle me-1"></i> Lihat Semua Transaksi
          </a>
        </div>
      </div>
    </div>
  </div>
    
  <!-- Transactions -->
  <div class="col-md-8 mb-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Transaksi dengan Tag Ini</h5>
        <div class="dropdown">
          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-filter"></i> Filter
          </button>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Semua</a></li>
            <li><a class="dropdown-item" href="#">Minggu ini</a></li>
            <li><a class="dropdown-item" href="#">Bulan ini</a></li>
            <li><a class="dropdown-item" href="#">Tahun ini</a></li>
          </ul>
        </div>
      </div>
      <div class="card-body">
        @if($transactions->isEmpty())
          <div class="text-center py-4">
            <i class="bi bi-receipt" style="font-size: 3rem; color: #dee2e6;"></i>
            <h5 class="mt-3">Belum ada transaksi</h5>
            <p class="text-muted">Tag ini belum digunakan pada transaksi apapun</p>
            <a href="{{ route('apps.transactions.create') }}" class="btn btn-primary">
              <i class="bi bi-plus-circle me-1"></i> Buat Transaksi
            </a>
          </div>
        @else
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Deskripsi</th>
                  <th>Kategori</th>
                  <th class="text-end">Jumlah</th>
                  <th class="text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($transactions as $transaction)
                  <tr>
                    <td>
                      <small class="text-muted">
                        {{ $transaction->transaction_date->translatedFormat('d M Y') }}
                      </small>
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-2">
                          @if($transaction->type === TransactionType::EXPENSE)
                            <span class="badge bg-danger">Pengeluaran</span>
                          @else
                            <span class="badge bg-success">Pemasukan</span>
                          @endif
                        </div>
                        <div class="flex-grow-1">
                          {{ Str::limit($transaction->description, 50) }}
                          @if($transaction->notes)
                            <small class="text-muted d-block">
                              {{ Str::limit($transaction->notes, 30) }}
                            </small>
                          @endif
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark">
                        {{ $transaction->category->name }}
                      </span>
                    </td>
                    <td class="text-end fw-bold {{ $transaction->type === TransactionType::EXPENSE ? 'text-danger' : 'text-success' }}">
                      {{ $transaction->type === TransactionType::EXPENSE ? '-' : '+' }}
                      @money($transaction->amount->getAmount()->toInt())
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <a href="{{ route('apps.transactions.show', $transaction) }}" class="btn btn-outline-primary">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('apps.transactions.edit', $transaction) }}" class="btn btn-outline-warning">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="removeTagFromTransaction({{ $transaction->id }}, {{ $tag->id }})" title="Hapus tag ini dari transaksi">
                          <i class="bi bi-x-circle"></i>
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
            <div class="d-flex justify-content-center mt-4">
              {{ $transactions->links() }}
            </div>
          @endif
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row mb-2">
  <div class="col-md-4 mb-4">
    <!-- Monthly Usage -->
    <div class="card mt-4">
      <div class="card-header">
        <h6 class="mb-0">Penggunaan Bulanan</h6>
      </div>
      <div class="card-body">
        <div class="chart-container">
          <canvas id="monthlyUsageChart"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-8 mb-4">
    <!-- Statistics -->
    <div class="row mt-4">
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Statistik Pengeluaran</h6>
            <div class="chart-container">
              <canvas id="expenseChart"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Distribusi Kategori</h6>
            <div class="chart-container">
              <canvas id="categoryChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteTagModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Hapus Tag</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin menghapus tag "<span id="tagNameToDelete"></span>"?</p>
        <div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Tag akan dihapus dari semua transaksi yang menggunakannya.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <form id="deleteTagForm" method="POST">
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
    function formatCurrency(amount) {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
      }).format(amount);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      // Monthly Usage Chart
      const monthlyCtx = document.getElementById('monthlyUsageChart');
      if (monthlyCtx) {
        const monthlyData = {
          labels: @json($monthlyUsage->pluck('month_label')),
          datasets: [{
            label: 'Jumlah Transaksi',
            data: @json($monthlyUsage->pluck('count')),
            backgroundColor: 'rgba(13, 110, 253, 0.2)',
            borderColor: 'rgba(13, 110, 253, 1)',
            borderWidth: 2,
            tension: 0.4
          }]
        };

        new Chart(monthlyCtx, {
          type: 'line',
          data: monthlyData,
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: false
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  precision: 0
                }
              }
            }
          }
        });
      }
        
      // Expense Chart
      const expenseCtx = document.getElementById('expenseChart');
      if (expenseCtx) {
        const expenseData = {
          labels: ['Pengeluaran', 'Pemasukan'],
          datasets: [{
            data: [
              {{ $transactions->where('type', TransactionType::EXPENSE)->sum('amount') }},
              {{ $transactions->where('type', TransactionType::INCOME)->sum('amount') }}
            ],
            backgroundColor: [
              'rgba(220, 53, 69, 0.8)',
              'rgba(25, 135, 84, 0.8)'
            ]
          }]
        };

        new Chart(expenseCtx, {
          type: 'pie',
          data: expenseData,
          options: {
            responsive: true,
            plugins: {
              legend: {
                position: 'bottom'
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return `${context.label}: ${formatCurrency(context.raw)}`;
                  }
                }
              }
            }
          }
        });
      }
        
      // Category Chart
      const categoryCtx = document.getElementById('categoryChart');
      if (categoryCtx) {
        const categoryData = {
          labels: @json($categoryDistribution->pluck('category_name')),
          datasets: [{
            data: @json($categoryDistribution->pluck('total')),
            backgroundColor: [
              'rgba(13, 110, 253, 0.8)',
              'rgba(111, 66, 193, 0.8)',
              'rgba(253, 126, 20, 0.8)',
              'rgba(32, 201, 151, 0.8)',
              'rgba(220, 53, 69, 0.8)'
            ]
          }]
        };

        new Chart(categoryCtx, {
          type: 'doughnut',
          data: categoryData,
          options: {
            responsive: true,
            plugins: {
              legend: {
                position: 'bottom'
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return `${context.label}: ${formatCurrency(context.raw)}`;
                  }
                }
              }
            }
          }
        });
      }
    });
    
    function confirmDelete(tagId, tagName) {
      const modal = new bootstrap.Modal(document.getElementById('deleteTagModal'));
      document.getElementById('tagNameToDelete').textContent = tagName;
      document.getElementById('deleteTagForm').action = `{{ config('app.url')}}/apps/tags/${tagId}`;
      modal.show();
    }
    
    async function removeTagFromTransaction(transactionId, tagId) {
      if (!confirm('Hapus tag ini dari transaksi?')) {
        return;
      }
        
      try {
        const response = await fetch(`{{ config('app.url') }}/apps/transactions/${transactionId}/tags/${tagId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
          }
        });
            
        if (response.ok) {
          location.reload();
        } else {
          const data = await response.json();
          alert(data.message || 'Terjadi kesalahan');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menghapus tag');
      }
    }
</script>
@endpush