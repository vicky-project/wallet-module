@extends('wallet::layouts.app')

@section('title', isset($tag) ? 'Edit Tag' : 'Tambah Tag Baru')

@section('content')
@include('wallet::partials.fab')
<div class="d-flex justify-content-between align-items-center mb-4">
  <a href="{{ route('apps.tags.index') }}" class="btn btn-secondary">
    <i class="bi bi-arrow-left me-1"></i> Kembali
  </a>
</div>

<div class="row justify-content-center">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="bi bi-tag-fill me-2"></i>
          {{ isset($tag) ? 'Edit Tag' : 'Tambah Tag Baru' }}
        </h5>
      </div>
      <div class="card-body">
        <form action="{{ isset($tag) ? route('apps.tags.update', $tag) : route('apps.tags.store') }}" method="POST" id="tagForm">
          @csrf
          @if(isset($tag))
            @method('PUT')
          @endif
                    
          <div class="mb-3">
            <label for="name" class="form-label">Nama Tag <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $tag->name ?? '') }}" required placeholder="Contoh: Makanan, Transportasi, Hiburan">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Nama harus unik dan deskriptif</small>
          </div>
                    
          <div class="mb-3">
            <label for="color" class="form-label">Warna Tag <span class="text-danger">*</span></label>
            <div class="row g-3">
              <div class="col-md-6">
                <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', $tag->color ?? '#0d6efd') }}" title="Pilih warna">
                @error('color')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <div class="color-presets">
                  <p class="mb-2 small text-muted">Preset warna:</p>
                  <div class="d-flex flex-wrap gap-2">
                    @foreach([
                      '#0d6efd' => 'Biru',
                      '#dc3545' => 'Merah',
                      '#198754' => 'Hijau',
                      '#ffc107' => 'Kuning',
                      '#6f42c1' => 'Ungu',
                      '#fd7e14' => 'Oranye',
                      '#20c997' => 'Teal',
                      '#6c757d' => 'Abu-abu',
                      '#0dcaf0' => 'Cyan',
                      '#6610f2' => 'Indigo'
                    ] as $color => $label)
                      <button type="button" class="btn btn-md color-preset-btn" style="background-color: {{ $color }}; border-color: {{ $color }};" data-color="{{ $color }}" title="{{ $label }}">
                      </button>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
          </div>
                    
          <div class="mb-3">
            <label for="icon" class="form-label">Ikon (Opsional)</label>
            <div class="input-group">
              <span class="input-group-text">
                <i id="iconPreview" class="bi bi-tag"></i>
              </span>
              <select class="form-select @error('icon') is-invalid @enderror" id="icon" name="icon">
                <option value="">Pilih ikon</option>
                @foreach([
                  'tag' => 'Tag',
                  'tag-fill' => 'Tag (fill)',
                  'cart' => 'Keranjang',
                  'cart-fill' => 'Keranjang (fill)',
                  'car-front' => 'Mobil',
                  'car-front-fill' => 'Mobil (fill)',
                  'cup' => 'Cangkir',
                  'cup-fill' => 'Cangkir (fill)',
                  'house' => 'Rumah',
                  'house-fill' => 'Rumah (fill)',
                  'heart' => 'Hati',
                  'heart-fill' => 'Hati (fill)',
                  'bag' => 'Tas',
                  'bag-fill' => 'Tas (fill)',
                  'film' => 'Film',
                  'film-fill' => 'Film (fill)',
                  'book' => 'Buku',
                  'book-fill' => 'Buku (fill)',
                  'gift' => 'Hadiah',
                  'gift-fill' => 'Hadiah (fill)',
                  'airplane' => 'Pesawat',
                  'airplane-fill' => 'Pesawat (fill)',
                  'telephone' => 'Telepon',
                  'telephone-fill' => 'Telepon (fill)',.'wifi' => 'WiFi',
                  'wifi-fill' => 'WiFi (fill)',
                  'lightning' => 'Petir',
                  'lightning-fill' => 'Petir (fill)',
                  'droplet' => 'Tetes',
                  'droplet-fill' => 'Tetes (fill)',
                  'music-note' => 'Musik',
                  'music-note-fill' => 'Musik (fill)'
                ] as $icon => $label)
                  <option value="{{ $icon }}" 
                                            {{ old('icon', $tag->icon ?? '') == $icon ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
            </div>
            @error('icon')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="text-muted">Pilih ikon dari Bootstrap Icons</small>
          </div>
                    
          <!-- Preview Section -->
          <div class="card bg-text-light mb-4">
            <div class="card-header">
              <h6 class="mb-0">Preview Tag</h6>
            </div>
            <div class="card-body">
              <div class="d-flex flex-wrap gap-3 align-items-center">
                <!-- Tag Preview -->
                <div>
                  <p class="small text-muted mb-1">Tampilan Tag:</p>
                  <span class="badge rounded-pill" id="tagPreview">
                    <i class="bi me-1" id="previewIcon"></i>
                    <span id="previewName">Nama Tag</span>
                  </span>
                </div>
                                
                <!-- Color Preview -->
                <div>
                  <p class="small text-muted mb-1">Warna:</p>
                  <div class="d-flex align-items-center">
                    <div class="color-preview me-2" id="colorPreview" style="width: 30px; height: 30px; border-radius: 4px;"></div>
                    <span id="colorHex">#0d6efd</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
                    
          <div class="d-flex justify-content-between">
            <a href="{{ route('apps.tags.index') }}" class="btn btn-secondary">
              <i class="bi bi-x-circle me-1"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-circle me-1"></i>
              {{ isset($tag) ? 'Perbarui' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
        
    <!-- Suggestions Card -->
    @if(isset($similarTags) && $similarTags->isNotEmpty())
      <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-lightbulb me-2"></i> Tag Serupa
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Berikut adalah tag dengan nama atau warna yang mirip:
                    </p>
                    <div class="tag-cloud">
                        @foreach($similarTags as $similarTag)
                            <a href="{{ route('apps.tags.show', $similarTag) }}" 
                               class="badge badge-light d-inline-flex align-items-center mb-2 me-2"
                               style="border: 1px solid #dee2e6;">
                                <span class="color-dot me-1" 
                                      style="background-color: {{ $similarTag->color }};"></span>
                                {{ $similarTag->name }}
                                <span class="badge bg-secondary ms-2">
                                    {{ $similarTag->usage_count }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorInput = document.getElementById('color');
        const colorPresetBtns = document.querySelectorAll('.color-preset-btn');
        const nameInput = document.getElementById('name');
        const iconSelect = document.getElementById('icon');
        
        // Preview elements
        const tagPreview = document.getElementById('tagPreview');
        const previewIcon = document.getElementById('previewIcon');
        const previewName = document.getElementById('previewName');
        const colorPreview = document.getElementById('colorPreview');
        const colorHex = document.getElementById('colorHex');
        const iconPreview = document.getElementById('iconPreview');
        
        // Update preview function
        function updatePreview() {
            const color = colorInput.value;
            const name = nameInput.value || 'Nama Tag';
            const icon = iconSelect.value || 'tag';
            
            // Update tag preview
            tagPreview.style.backgroundColor = color + '20';
            tagPreview.style.color = color;
            tagPreview.style.border = `1px solid ${color}`;
            previewName.textContent = name;
            
            // Update icon
            previewIcon.className = `bi bi-${icon}`;
            iconPreview.className = `bi bi-${icon}`;
            
            // Update color preview
            colorPreview.style.backgroundColor = color;
            colorHex.textContent = color;
        }
        
        // Initialize preview
        updatePreview();
        
        // Event listeners for real-time preview
        colorInput.addEventListener('input', updatePreview);
        nameInput.addEventListener('input', updatePreview);
        iconSelect.addEventListener('change', updatePreview);
        
        // Color preset buttons
        colorPresetBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const color = this.dataset.color;
                colorInput.value = color;
                updatePreview();
            });
        });
        
        // Form validation
        const form = document.getElementById('tagForm');
        form.addEventListener('submit', function(e) {
            const name = nameInput.value.trim();
            const color = colorInput.value;
            
            // Basic validation
            if (!name) {
                e.preventDefault();
                nameInput.classList.add('is-invalid');
                return;
            }
            
            // Color format validation
            const colorRegex = /^#[0-9A-F]{6}$/i;
            if (!colorRegex.test(color)) {
                e.preventDefault();
                colorInput.classList.add('is-invalid');
                return;
            }
        });
        
        // Remove invalid class when user starts typing
        nameInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
        
        colorInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
</script>
@endpush