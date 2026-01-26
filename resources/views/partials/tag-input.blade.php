<div class="tag-input-container mb-3" id="tagInputContainer">
    <label class="form-label">Tags</label>
    
    <!-- Selected Tags Display -->
    <div class="selected-tags mb-3">
        @if(isset($selectedTags) && $selectedTags->isNotEmpty())
            @foreach($selectedTags as $tag)
                <span class="tag-pill" 
                      style="background-color: {{ $tag->color }}20; 
                             color: {{ $tag->color }};
                             border: 1px solid {{ $tag->color }};">
                    @if($tag->icon)
                        <i class="bi bi-{{ $tag->icon }} me-1"></i>
                    @endif
                    {{ $tag->name }}
                    <span class="tag-pill-remove" 
                          onclick="removeTag({{ $tag->id }})">
                        <i class="bi bi-x"></i>
                    </span>
                </span>
            @endforeach
        @else
            <div class="text-muted">
                <i class="bi bi-tags me-1"></i> Tidak ada tag yang dipilih
            </div>
        @endif
    </div>
    
    <!-- Hidden input for form submission -->
    <input type="hidden" name="tag_ids" id="tagIdsInput" 
           value="{{ isset($selectedTags) ? $selectedTags->pluck('id')->join(',') : '' }}">
    
    <!-- Search and Add Interface -->
    <div class="input-group">
        <button class="btn btn-outline-secondary" type="button" 
                data-bs-toggle="dropdown" data-bs-auto-close="outside">
            <i class="bi bi-plus-lg"></i> Tambah Tag
        </button>
        <input type="text" 
               class="form-control" 
               id="tagSearchInput" 
               placeholder="Cari tag..."
               autocomplete="off">
        <button class="btn btn-outline-primary" type="button" 
                data-bs-toggle="modal" data-bs-target="#createTagModal">
            <i class="bi bi-plus-circle"></i> Baru
        </button>
    </div>
    
    <!-- Search Results Dropdown -->
    <div class="dropdown-menu dropdown-menu-tags p-2 w-100" 
         id="tagSearchResults" 
         style="display: none; max-height: 300px; overflow-y: auto;">
        <div class="list-group" id="tagResultsList"></div>
        <div class="text-center py-2" id="noTagsFound" style="display: none;">
            <small class="text-muted">Tidak ada tag ditemukan</small>
        </div>
    </div>
</div>

<!-- Create Tag Modal -->
<div class="modal fade" id="createTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Tag Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createTagForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Tag</label>
                        <input type="text" class="form-control" id="newTagName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warna</label>
                        <input type="color" class="form-control form-control-color" 
                               id="newTagColor" value="#0d6efd">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ikon (Opsional)</label>
                        <select class="form-select" id="newTagIcon">
                            <option value="">Pilih ikon</option>
                            <option value="tag">Tag</option>
                            <option value="cart">Keranjang</option>
                            <option value="car-front">Mobil</option>
                            <option value="cup">Cangkir</option>
                            <option value="house">Rumah</option>
                            <option value="heart">Hati</option>
                            <option value="bag">Tas</option>
                            <option value="film">Film</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tagSearchInput = document.getElementById('tagSearchInput');
        const tagResultsList = document.getElementById('tagResultsList');
        const tagSearchResults = document.getElementById('tagSearchResults');
        const noTagsFound = document.getElementById('noTagsFound');
        const tagIdsInput = document.getElementById('tagIdsInput');
        const tagInputContainer = document.getElementById('tagInputContainer');
        const createTagForm = document.getElementById('createTagForm');
        
        let allTags = [];
        let selectedTagIds = tagIdsInput.value ? tagIdsInput.value.split(',').map(Number) : [];
        let debounceTimer;
        
        // Fetch all tags on page load
        async function fetchAllTags() {
            try {
                const response = await fetch('{{ route("tags.index") }}?json=true');
                const data = await response.json();
                allTags = data.data || [];
            } catch (error) {
                console.error('Error fetching tags:', error);
            }
        }
        
        // Search tags
        tagSearchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(searchTags, 300);
        });
        
        tagSearchInput.addEventListener('focus', function() {
            searchTags();
            tagSearchResults.style.display = 'block';
        });
        
        function searchTags() {
            const searchTerm = tagSearchInput.value.toLowerCase().trim();
            
            if (!searchTerm) {
                tagSearchResults.style.display = 'none';
                return;
            }
            
            const filteredTags = allTags.filter(tag => 
                !selectedTagIds.includes(tag.id) &&
                tag.name.toLowerCase().includes(searchTerm)
            );
            
            displaySearchResults(filteredTags);
        }
        
        function displaySearchResults(tags) {
            tagResultsList.innerHTML = '';
            
            if (tags.length === 0) {
                noTagsFound.style.display = 'block';
                return;
            }
            
            noTagsFound.style.display = 'none';
            
            tags.forEach(tag => {
                const tagElement = document.createElement('div');
                tagElement.className = 'tag-option list-group-item list-group-item-action';
                tagElement.innerHTML = `
                    <div class="d-flex align-items-center">
                        <span class="color-dot" style="background-color: ${tag.color};"></span>
                        <div class="flex-grow-1">
                            <strong>${tag.name}</strong>
                            <div class="small text-muted">
                                <i class="bi bi-${tag.icon || 'tag'} me-1"></i>
                                ${tag.transactions_count || 0} transaksi
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="selectTag(${tag.id})">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                `;
                tagResultsList.appendChild(tagElement);
            });
            
            tagSearchResults.style.display = 'block';
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!tagInputContainer.contains(event.target)) {
                tagSearchResults.style.display = 'none';
            }
        });
        
        // Create new tag
        createTagForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const name = document.getElementById('newTagName').value;
            const color = document.getElementById('newTagColor').value;
            const icon = document.getElementById('newTagIcon').value;
            
            try {
                const response = await fetch('{{ route("tags.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name, color, icon })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    // Add the new tag to allTags and select it
                    allTags.push(data.data);
                    selectTag(data.data.id);
                    
                    // Reset form and close modal
                    createTagForm.reset();
                    document.getElementById('newTagColor').value = '#0d6efd';
                    bootstrap.Modal.getInstance(document.getElementById('createTagModal')).hide();
                    
                    // Show success message
                    showAlert('Tag berhasil dibuat!', 'success');
                } else {
                    throw new Error('Gagal membuat tag');
                }
            } catch (error) {
                console.error('Error creating tag:', error);
                showAlert('Gagal membuat tag', 'danger');
            }
        });
        
        // Initialize
        fetchAllTags();
    });
    
    // Global functions
    window.selectTag = function(tagId) {
        const selectedTagIds = document.getElementById('tagIdsInput').value 
            ? document.getElementById('tagIdsInput').value.split(',').map(Number) 
            : [];
        
        if (!selectedTagIds.includes(tagId)) {
            selectedTagIds.push(tagId);
            document.getElementById('tagIdsInput').value = selectedTagIds.join(',');
            
            // Find the tag in allTags
            const tag = window.allTags?.find(t => t.id === tagId);
            if (tag) {
                // Add tag to display
                const tagPill = document.createElement('span');
                tagPill.className = 'tag-pill';
                tagPill.style.cssText = `background-color: ${tag.color}20; 
                                         color: ${tag.color};
                                         border: 1px solid ${tag.color};`;
                tagPill.innerHTML = `
                    ${tag.icon ? `<i class="bi bi-${tag.icon} me-1"></i>` : ''}
                    ${tag.name}
                    <span class="tag-pill-remove" onclick="removeTag(${tagId})">
                        <i class="bi bi-x"></i>
                    </span>
                `;
                
                const selectedTagsDiv = document.querySelector('.selected-tags');
                const noTagsMessage = selectedTagsDiv.querySelector('.text-muted');
                if (noTagsMessage) {
                    noTagsMessage.remove();
                }
                selectedTagsDiv.appendChild(tagPill);
            }
        }
        
        // Hide search results
        document.getElementById('tagSearchResults').style.display = 'none';
        document.getElementById('tagSearchInput').value = '';
    };
    
    window.removeTag = function(tagId) {
        let selectedTagIds = document.getElementById('tagIdsInput').value 
            ? document.getElementById('tagIdsInput').value.split(',').map(Number) 
            : [];
        
        selectedTagIds = selectedTagIds.filter(id => id !== tagId);
        document.getElementById('tagIdsInput').value = selectedTagIds.join(',');
        
        // Remove tag from display
        const tagPill = document.querySelector(`.tag-pill-remove[onclick="removeTag(${tagId})"]`)?.parentElement;
        if (tagPill) {
            tagPill.remove();
        }
        
        // Show "no tags" message if empty
        const selectedTagsDiv = document.querySelector('.selected-tags');
        if (selectedTagsDiv.children.length === 0) {
            const noTagsMessage = document.createElement('div');
            noTagsMessage.className = 'text-muted';
            noTagsMessage.innerHTML = '<i class="bi bi-tags me-1"></i> Tidak ada tag yang dipilih';
            selectedTagsDiv.appendChild(noTagsMessage);
        }
    };
    
    window.showAlert = function(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);
        
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 3000);
    };
</script>
@endpush