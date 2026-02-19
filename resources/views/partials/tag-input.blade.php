<div class="tag-input-component" id="tagInputComponent" data-transaction-id="{{ $transaction->id ?? null }}">
    <!-- Hidden input untuk menyimpan tag IDs -->
  <input type="hidden" name="tag_ids" id="tagIdsInput" value="{{ $selectedTags->pluck('id')->join(',') }}">
    
  <!-- Bagian 1: Tag yang Sudah Dipilih -->
  <div class="mb-4">
    <label class="form-label fw-semibold mb-3">
      <i class="bi bi-tags-fill me-2"></i>Tag untuk Transaksi Ini
      <span class="badge bg-primary ms-2" id="selectedTagCount">
        {{ $selectedTags->count() }}
      </span>
    </label>
        
    <div id="selectedTagsContainer" class="selected-tags-container border rounded p-3">
      @if($selectedTags->isNotEmpty())
        <div class="d-flex flex-wrap gap-2" id="selectedTagsList">
          @foreach($selectedTags as $tag)
            <span class="selected-tag badge d-flex align-items-center py-2 px-3" data-tag-id="{{ $tag->id }}" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}; cursor: pointer;">
              @if($tag->icon)
                <i class="bi bi-{{ $tag->icon }} me-2"></i>
              @endif
              {{ $tag->name }}
              <i class="bi bi-x ms-2"></i>
            </span>
          @endforeach
        </div>
      @else
        <div class="text-center py-2" id="noSelectedTagsMessage">
          <i class="bi bi-tag text-muted me-2"></i>
          <span class="text-muted">Belum ada tag yang dipilih.</span>
          <br>
          <small class="text-muted">Klik tag di bawah untuk menambahkannya</small>
        </div>
      @endif
    </div>
  </div>
    
  <!-- Bagian 2: Tag yang Tersedia -->
  <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <label class="form-label fw-semibold mb-0">
        <i class="bi bi-tag me-2"></i>Tag yang Tersedia
      </label>
      <div class="d-flex gap-2">
        <input type="text" class="form-control form-control-sm" id="tagSearchInput" placeholder="Cari tag..." style="width: 150px;">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSearchBtn">
          <i class="bi bi-x"></i>
        </button>
      </div>
    </div>
        
    <div id="availableTagsContainer" class="available-tags-container">
      <!-- Tag akan diload via JavaScript -->
      <div class="text-center py-3">
        <div class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <span class="ms-2 text-muted">Memuat tag...</span>
      </div>
    </div>
  </div>
    
  <!-- Bagian 3: Link untuk Membuat Tag Baru (jika tidak ada tag sama sekali) -->
  <div id="noTagsMessage" class="alert alert-info" style="display: none;">
    <div class="d-flex align-items-center">
      <i class="bi bi-info-circle-fill me-3 fs-4"></i>
      <div>
        <h6 class="alert-heading mb-1">Belum ada tag yang tersedia</h6>
        <p class="mb-0">
          Buat tag terlebih dahulu untuk mengkategorikan transaksi Anda.
        </p>
      </div>
    </div>
    <div class="mt-3">
      <a href="{{ route('apps.tags.create') }}" class="btn btn-sm btn-info" target="_blank">
        <i class="bi bi-plus-circle me-1"></i> Buat Tag Baru
      </a>
    </div>
  </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elemen DOM
        const tagInputComponent = document.getElementById('tagInputComponent');
        const selectedTagsContainer = document.getElementById('selectedTagsContainer');
        const selectedTagsList = document.getElementById('selectedTagsList');
        const noSelectedTagsMessage = document.getElementById('noSelectedTagsMessage');
        const availableTagsContainer = document.getElementById('availableTagsContainer');
        const tagSearchInput = document.getElementById('tagSearchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const tagIdsInput = document.getElementById('tagIdsInput');
        const selectedTagCount = document.getElementById('selectedTagCount');
        const noTagsMessage = document.getElementById('noTagsMessage');
        
        // State
        let allTags = [];
        let selectedTagIds = tagIdsInput.value ? tagIdsInput.value.split(',').map(id => parseInt(id)) : Array.from([]);
        let transactionId = tagInputComponent.dataset.transactionId;
        let isLoading = false;
        let currentSearch = '';
        
        // Fetch semua tag dari server
        async function fetchAllTags() {
            try {
                isLoading = true;
                const response = await fetch('{{ secure_url(config("app.url") . "/apps/tags") }}?json=true', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    allTags = data.data.data || [];
                    renderAvailableTags();
                    checkIfNoTags();
                }
            } catch (error) {
                console.error('Error fetching tags:', error);
                showAlert('Gagal memuat tag', 'danger');
            } finally {
                isLoading = false;
            }
        }
        
        // Render tag yang tersedia
        function renderAvailableTags() {
            // Filter tag yang belum dipilih
            let filteredTags = Array.from(allTags).filter(tag => !selectedTagIds.includes(tag.id));
            
            // Filter berdasarkan search
            if (currentSearch) {
                const searchTerm = currentSearch.toLowerCase();
                filteredTags = filteredTags.filter(tag => 
                    tag.name.toLowerCase().includes(searchTerm)
                );
            }
            
            if (filteredTags.length === 0) {
                if (currentSearch) {
                    availableTagsContainer.innerHTML = `
                        <div class="text-center py-4">
                            <i class="bi bi-search text-muted" style="font-size: 2rem;"></i>
                            <p class="mt-2 text-muted">Tidak ada tag yang cocok dengan pencarian</p>
                        </div>
                    `;
                } else {
                    availableTagsContainer.innerHTML = `
                        <div class="text-center py-4">
                            <i class="bi bi-tags text-muted" style="font-size: 2rem;"></i>
                            <p class="mt-2 text-muted">Semua tag sudah dipilih</p>
                        </div>
                    `;
                }
                return;
            }
            
            // Grup tag berdasarkan huruf pertama
            const groupedTags = {};
            filteredTags.forEach(tag => {
                const firstLetter = tag.name.charAt(0).toUpperCase();
                if (!groupedTags[firstLetter]) {
                    groupedTags[firstLetter] = [];
                }
                groupedTags[firstLetter].push(tag);
            });
            
            // Sort letters alphabetically
            const sortedLetters = Object.keys(groupedTags).sort();
            
            let html = '<div class="available-tags-grid">';
            
            sortedLetters.forEach(letter => {
                html += `
                    <div class="mb-3">
                        <div class="letter-header mb-2">
                            <span class="badge bg-secondary">${letter}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                `;
                
                groupedTags[letter].forEach(tag => {
                    const usageCount = tag.usage_count || tag.transactions_count || 0;
                    html += `
                        <span class="available-tag badge d-flex align-items-center py-2 px-3" 
                              data-tag-id="${tag.id}"
                              style="background-color: ${tag.color}15; 
                                     color: ${tag.color};
                                     border: 1px dashed ${tag.color};
                                     cursor: pointer;"
                              title="Klik untuk menambahkan tag">
                            @if(isset($tag->icon))
                                <i class="bi bi-${tag.icon} me-2"></i>
                            @endif
                            ${tag.name}
                            <span class="badge bg-light text-dark ms-2">
                                ${usageCount}
                            </span>
                        </span>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            availableTagsContainer.innerHTML = html;
            
            // Tambahkan event listener untuk tag yang tersedia
            document.querySelectorAll('.available-tag').forEach(tagElement => {
                tagElement.addEventListener('click', () => addTag(tagElement.dataset.tagId));
            });
        }
        
        // Tambah tag ke transaksi
        function addTag(tagId) {
            if (selectedTagIds.includes(parseInt(tagId))) {
                return; // Tag sudah dipilih
            }
            
            // Tambahkan ke selectedTagIds
            selectedTagIds.push(parseInt(tagId));
            updateTagIdsInput();
            updateSelectedTagsDisplay();
            renderAvailableTags();
            
            // Jika ada transactionId, simpan ke server
            //if (transactionId) {
            //    saveTagToTransaction(tagId);
            //}
        }
        
        // Hapus tag dari transaksi
        function removeTag(tagId) {
            selectedTagIds = selectedTagIds.filter(id => id !== parseInt(tagId));
            updateTagIdsInput();
            updateSelectedTagsDisplay();
            renderAvailableTags();
            
            // Jika ada transactionId, hapus dari server
            //if (transactionId) {
            //    removeTagFromTransaction(tagId);
            //}
        }
        
        // Update tampilan tag yang dipilih
        function updateSelectedTagsDisplay() {
            // Update counter
            selectedTagCount.textContent = selectedTagIds.length;
            
            if (selectedTagIds.length === 0) {
                if (!noSelectedTagsMessage) {
                    selectedTagsContainer.innerHTML = `
                        <div class="text-center py-2" id="noSelectedTagsMessage">
                            <i class="bi bi-tag text-muted me-2"></i>
                            <span class="text-muted">Belum ada tag yang dipilih.</span>
                            <br>
                            <small class="text-muted">Klik tag di bawah untuk menambahkannya</small>
                        </div>
                    `;
                } else {
                    noSelectedTagsMessage.style.display = 'block';
                }
                return;
            }
            
            // Sembunyikan pesan "no tags"
            if (noSelectedTagsMessage) {
                noSelectedTagsMessage.style.display = 'none';
            }
            
            // Buat list tag yang dipilih
            let html = '<div class="d-flex flex-wrap gap-2" id="selectedTagsList">';
            
            selectedTagIds.forEach(tagId => {
                const tag = allTags.find(t => t.id === tagId);
                if (tag) {
                    html += `
                        <span class="selected-tag badge d-flex align-items-center py-2 px-3" 
                              data-tag-id="${tag.id}"
                              style="background-color: ${tag.color}20; 
                                     color: ${tag.color};
                                     border: 1px solid ${tag.color};
                                     cursor: pointer;"
                              title="Klik untuk menghapus tag">
                            @if(isset($tag->icon))
                                <i class="bi bi-${tag.icon} me-2"></i>
                            @endif
                            ${tag.name}
                            <i class="bi bi-x ms-2"></i>
                        </span>
                    `;
                }
            });
            
            html += '</div>';
            selectedTagsContainer.innerHTML = html;
            
            // Tambahkan event listener untuk tag yang dipilih
            document.querySelectorAll('.selected-tag').forEach(tagElement => {
                tagElement.addEventListener('click', () => removeTag(tagElement.dataset.tagId));
            });
        }
        
        // Update input hidden
        function updateTagIdsInput() {
            tagIdsInput.value = selectedTagIds.join(',');
        }
        
        // Simpan tag ke transaksi (untuk edit form)
        async function saveTagToTransaction(tagId) {
            try {
                const response = await fetch(`/api/transactions/${transactionId}/tags`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ tag_id: tagId })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to save tag');
                }
            } catch (error) {
                console.error('Error saving tag:', error);
            }
        }
        
        // Hapus tag dari transaksi (untuk edit form)
        async function removeTagFromTransaction(tagId) {
            try {
                const response = await fetch(`/api/transactions/${transactionId}/tags/${tagId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to remove tag');
                }
            } catch (error) {
                console.error('Error removing tag:', error);
            }
        }
        
        // Cek jika tidak ada tag sama sekali
        function checkIfNoTags() {
            if (allTags.length === 0 && selectedTagIds.length === 0) {
                noTagsMessage.style.display = 'block';
                availableTagsContainer.style.display = 'none';
            } else {
                noTagsMessage.style.display = 'none';
                availableTagsContainer.style.display = 'block';
            }
        }
        
        // Show alert message
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
            alertDiv.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            tagInputComponent.insertBefore(alertDiv, tagInputComponent.firstChild);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }
        
        // Event listeners untuk pencarian
        tagSearchInput.addEventListener('input', function() {
            currentSearch = this.value.trim();
            renderAvailableTags();
        });
        
        clearSearchBtn.addEventListener('click', function() {
            tagSearchInput.value = '';
            currentSearch = '';
            renderAvailableTags();
        });
        
        // Inisialisasi
        fetchAllTags();
        
        // Jika ada selected tags dari server, update display
        if (selectedTagIds.length > 0) {
            // Kita perlu menunggu allTags diload dulu
            const checkTagsLoaded = setInterval(() => {
                if (allTags.length > 0 || !isLoading) {
                    clearInterval(checkTagsLoaded);
                    updateSelectedTagsDisplay();
                }
            }, 100);
        }
    });
</script>

<style>
    .tag-input-component {
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1.5rem;
    }
    
    .selected-tags-container {
        min-height: 80px;
        transition: all 0.3s ease;
    }
    
    .selected-tag {
        transition: all 0.2s ease;
        user-select: none;
    }
    
    .selected-tag:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        opacity: 0.9;
    }
    
    .selected-tag .bi-x {
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    
    .selected-tag:hover .bi-x {
        opacity: 1;
    }
    
    .available-tag {
        transition: all 0.2s ease;
        user-select: none;
    }
    
    .available-tag:hover {
        transform: translateY(-1px);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        background-color: inherit !important;
        border-style: solid !important;
    }
    
    .letter-header {
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 0.25rem;
    }
    
    .available-tags-grid {
        max-height: 300px;
        overflow-y: auto;
        padding: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .tag-input-component {
            padding: 1rem;
        }
        
        .selected-tag, .available-tag {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem !important;
        }
    }
</style>
@endpush