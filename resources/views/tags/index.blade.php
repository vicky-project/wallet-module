@extends('wallet::layouts.app')

@section('title', 'Tags')

@section('content')
@include('wallet::partials.fab')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
  <div class="btn-toolbar mb-2 mb-md-0">
    <div class="btn-group">
      <a href="{{ route('apps.tags.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Tag
      </a>
      <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
        <span class="visually-hidden">Toggle Dropdown</span>
      </button>
      <ul class="dropdown-menu">
        <li>
          <a class="dropdown-item" href="#tagCloudModal" data-bs-toggle="modal">
            <i class="bi bi-cloud-fill me-2"></i> Tag Cloud
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="#mergeTagsModal" data-bs-toggle="modal">
            <i class="bi bi-intersect me-2"></i> Gabungkan Tag
          </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <a class="dropdown-item" href="{{ route('apps.reports.tags') }}">
            <i class="bi bi-graph-up me-2"></i> Laporan Tag
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>

<div class="row">
  <!-- Statistics Cards -->
  <div class="col-12 mb-4">
    <div class="row">
      <div class="col-md-3 mb-3">
        <div class="card stat-card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-2">Total Tag</h6>
                <h3 class="mb-0">{{ $stats['total_tags'] }}</h3>
              </div>
              <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-tags"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
            
      <div class="col-md-3 mb-3">
        <div class="card stat-card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-2">Tag Terpopuler</h6>
                <h5 class="mb-0">
                  @if($stats['most_used_tag'])
                    <span class="badge" style="background-color: {{ $stats['most_used_tag']->color }}20; color: {{ $stats['most_used_tag']->color }};">
                      {{ $stats['most_used_tag']->name }}
                    </span>
                  @else
                    -
                  @endif
                </h5>
                <small class="text-muted">
                  {{ $stats['most_used_tag']->transactions_count ?? 0 }} transaksi
                </small>
              </div>
              <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-star-fill"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
            
      <div class="col-md-3 mb-3">
        <div class="card stat-card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-2">Tag Baru (30 hari)</h6>
                <h3 class="mb-0">{{ $stats['new_tags_last_30_days'] ?? 0 }}</h3>
              </div>
              <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-clock-history"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
            
      <div class="col-md-3 mb-3">
        <div class="card stat-card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="text-muted mb-2">Tag Tidak Terpakai</h6>
                <h3 class="mb-0">{{ $stats['unused_tags'] ?? 0 }}</h3>
              </div>
              <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-exclamation-triangle"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
    
  <!-- Search and Filters -->
  <div class="col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-search"></i>
              </span>
              <input type="text" class="form-control" id="searchTags" placeholder="Cari tag...">
              <button class="btn btn-outline-secondary" type="button">
                <i class="bi bi-funnel"></i> Filter
              </button>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-end">
              <div class="btn-group me-2">
                <button class="btn btn-outline-secondary active" data-filter="all">
                  All
                </button>
                <button class="btn btn-outline-secondary" data-filter="used">
                  Used
                </button>
                <button class="btn btn-outline-secondary" data-filter="unused">
                  Unused
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
    
  <!-- Tags List -->
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Daftar Tags</h5>
        <div class="dropdown ms-auto">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                  <i class="bi bi-sort-down"></i> Urutkan
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item sort-option" href="#" data-sort="name">Nama A-Z</a></li>
                  <li><a class="dropdown-item sort-option" href="#" data-sort="name_desc">Nama Z-A</a></li>
                  <li><a class="dropdown-item sort-option" href="#" data-sort="usage">Penggunaan Terbanyak</a></li>
                  <li><a class="dropdown-item sort-option" href="#" data-sort="usage_desc">Penggunaan Tersedikit</a></li>
                  <li><a class="dropdown-item sort-option" href="#" data-sort="recent">Terbaru</a></li>
                  <li><a class="dropdown-item sort-option" href="#" data-sort="oldest">Terlama</a></li>
                </ul>
              </div>
      </div>
      <div class="card-body">
        @if($tags->isEmpty())
          <div class="text-center py-5">
            <i class="bi bi-tags" style="font-size: 4rem; color: #dee2e6;"></i>
            <h4 class="mt-3">Belum ada tag</h4>
            <p class="text-muted">Mulai dengan membuat tag pertama Anda</p>
            <a href="{{ route('apps.tags.create') }}" class="btn btn-primary">
              <i class="bi bi-plus-circle me-1"></i> Buat Tag Pertama
            </a>
          </div>
        @else
          <div class="row" id="tagsContainer">
            @foreach($tags as $tag)
              <div class="col-md-4 col-lg-3 mb-4 tag-item" data-usage="{{ $tag->usage_count }}" data-name="{{ strtolower($tag->name) }}" data-created="{{ $tag->created_at->timestamp }}">
                <div class="card h-100 tag-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <div class="d-flex align-items-center">
                        <span class="color-dot me-2" style="background-color: {{ $tag->color }};"></span>
                        <h5 class="card-title mb-0">{{ $tag->name }}</h5>
                      </div>
                      <div class="dropdown">
                        <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                          <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                          <li>
                            <a class="dropdown-item" href="{{ route('apps.tags.show', $tag) }}">
                              <i class="bi bi-eye me-2"></i> Lihat Detail
                            </a>
                          </li>
                          <li>
                            <a class="dropdown-item" href="{{ route('apps.tags.edit', $tag) }}">
                              <i class="bi bi-pencil me-2"></i> Edit
                            </a>
                          </li>
                          <li>
                            <button class="dropdown-item text-danger" onclick="confirmDelete({{ $tag->id }}, '{{ $tag->name }}')">
                              <i class="bi bi-trash me-2"></i> Hapus
                            </button>
                          </li>
                        </ul>
                      </div>
                    </div>
                                        
                    <div class="mb-3">
                      <span class="badge rounded-pill" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">
                        <i class="bi bi-tag-fill me-1"></i>
                        {{ $tag->usage_count }} transaksi
                      </span>
                      @if($tag->icon)
                        <span class="badge bg-light text-dark ms-1">
                          <i class="bi {{ $tag->icon }}"></i>
                        </span>
                      @endif
                    </div>
                                        
                    <div class="small text-muted mb-2">
                      <i class="bi bi-calendar3 me-1"></i>
                      Dibuat: {{ $tag->created_at->translatedFormat('d M Y') }}
                    </div>
                                        
                    <div class="tag-transactions-preview">
                      <small class="text-muted">Transaksi terbaru:</small>
                      <div class="mt-2">
                        @php
                        $recentTransactions = $tag->transactions()->latest()->limit(3)->get();
                        @endphp
                        
                        @if($recentTransactions->isEmpty())
                          <div class="text-muted small">Belum ada transaksi</div>
                        @else
                          @foreach($recentTransactions as $transaction)
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                              <div class="text-truncate" style="max-width: 70%;">
                                {{ $transaction->description }}
                              </div>
                              <div class="text-end">
                                <small class="fw-bold {{ $transaction->type === 'expense' ? 'text-danger' : 'text-success' }}">
                                  {{ $transaction->type === 'expense' ? '-' : '+' }}{{ formatCurrency($transaction->amount) }}
                                </small>
                              </div>
                            </div>
                          @endforeach
                        @endif
                      </div>
                    </div>
                  </div>
                  <div class="card-footer bg-transparent border-top-0 pt-0">
                    <a href="{{ route('apps.tags.show', $tag) }}" class="btn btn-sm btn-outline-primary w-100">
                      <i class="bi bi-arrow-right me-1"></i> Lihat Semua
                    </a>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
                    
          <!-- Pagination -->
          @if($tags->hasPages())
            <div class="d-flex justify-content-center mt-4">
              <nav aria-label="Tags pagination">
                {{ $tags->links() }}
              </nav>
            </div>
          @endif
        @endif
      </div>
    </div>
  </div>
    
  <!-- Recently Used Tags -->
  <div class="col-12 mt-4">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="bi bi-clock-history me-2"></i> Tag Baru-baru Ini Digunakan
        </h5>
      </div>
      <div class="card-body">
        @if($recentlyUsedTags->isEmpty())
          <div class="text-center py-3">
            <p class="text-muted mb-0">Belum ada tag yang digunakan baru-baru ini</p>
          </div>
        @else
          <div class="tag-cloud">
            @foreach($recentlyUsedTags as $tag)
              <a href="{{ route('apps.tags.show', $tag) }}" class="badge-tag d-inline-flex align-items-center" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }};">
                <span class="color-dot me-1" style="background-color: {{ $tag->color }};"></span>
                {{ $tag->name }}
                <span class="badge bg-light text-dark ms-2">
                  {{ $tag->transactions_count }}
                </span>
              </a>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Tag Cloud Modal -->
<div class="modal fade" id="tagCloudModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tag Cloud</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center">
          <canvas id="tagCloudChart" class="chart-container"></canvas>
        </div>
        <div class="tag-cloud text-center mt-4" id="tagCloudContainer">
          @foreach($popularTags as $tag)
            @php
              $size = 14 + ($tag->transactions_count * 2);
              $size = min($size, 48);
            @endphp
            <a href="{{ route('apps.tags.show', $tag) }}" class="badge-tag d-inline-block m-1" style="font-size: {{ $size }}px; background-color: {{ $tag->color }}20; color: {{ $tag->color }}; padding: {{ $size/8 }}px {{ $size/4 }}px;">
              {{ $tag->name }}
            </a>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Merge Tags Modal -->
<div class="modal fade" id="mergeTagsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Gabungkan Tag</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ route('apps.tags.merge') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Semua transaksi dari tag sumber akan dipindahkan ke tag target, dan tag sumber akan dihapus.
          </div>
                    
          <div class="mb-3">
            <label class="form-label">Tag Sumber</label>
            <select class="form-select" name="source_tag_id" required>
              <option value="">Pilih tag sumber</option>
              @foreach($tags as $tag)
                <option value="{{ $tag->id }}">
                  {{ $tag->name }} ({{ $tag->usage_count }} transaksi)
                </option>
              @endforeach
            </select>
          </div>
                    
          <div class="mb-3">
            <label class="form-label">Tag Target</label>
            <select class="form-select" name="target_tag_id" required>
              <option value="">Pilih tag target</option>
              @foreach($tags as $tag)
                <option value="{{ $tag->id }}">
                  {{ $tag->name }} ({{ $tag->usage_count }} transaksi)
                </option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Gabungkan Tag</button>
        </div>
      </form>
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
  const formatCurrency = function(amount) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };
    
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.getElementById('searchTags');
        const tagItems = document.querySelectorAll('.tag-item');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            tagItems.forEach(item => {
                const tagName = item.dataset.name;
                if (tagName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Filter functionality
        const filterButtons = document.querySelectorAll('[data-filter]');
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                
                tagItems.forEach(item => {
                    const usage = parseInt(item.dataset.usage);
                    
                    if (filter === 'all') {
                        item.style.display = 'block';
                    } else if (filter === 'used' && usage > 0) {
                        item.style.display = 'block';
                    } else if (filter === 'unused' && usage === 0) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
        
        // Sort functionality
        const sortOptions = document.querySelectorAll('.sort-option');
        const tagsContainer = document.getElementById('tagsContainer');
        
        sortOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                const sortBy = this.dataset.sort;
                const tagItemsArray = Array.from(tagItems);
                
                tagItemsArray.sort((a, b) => {
                    if (sortBy === 'name') {
                        return a.dataset.name.localeCompare(b.dataset.name);
                    } else if (sortBy === 'name_desc') {
                        return b.dataset.name.localeCompare(a.dataset.name);
                    } else if (sortBy === 'usage') {
                        return parseInt(b.dataset.usage) - parseInt(a.dataset.usage);
                    } else if (sortBy === 'usage_desc') {
                        return parseInt(a.dataset.usage) - parseInt(b.dataset.usage);
                    } else if (sortBy === 'recent') {
                        return parseInt(b.dataset.created) - parseInt(a.dataset.created);
                    } else if (sortBy === 'oldest') {
                        return parseInt(a.dataset.created) - parseInt(b.dataset.created);
                    }
                    return 0;
                });
                
                // Reorder items in container
                tagItemsArray.forEach(item => {
                    tagsContainer.appendChild(item);
                });
            });
        });
        
        // Initialize tag cloud chart
        const tagCloudCtx = document.getElementById('tagCloudChart');
        if (tagCloudCtx) {
            const tagCloudData = {
                labels: @json($popularTags->pluck('name')),
                datasets: [{
                    data: @json($popularTags->pluck('transactions_count')),
                    backgroundColor: @json($popularTags->pluck('color')),
                    borderWidth: 1
                }]
            };
            
            new Chart(tagCloudCtx, {
                type: 'doughnut',
                data: tagCloudData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw} transaksi`;
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
        document.getElementById('deleteTagForm').action = `{{ config('app.url') }}/apps/tags/${tagId}`;
        modal.show();
    }
</script>
@endpush

@push('styles')
<style>
  .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
</style>
@endpush