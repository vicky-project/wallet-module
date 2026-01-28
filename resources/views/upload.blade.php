@extends('wallet::layouts.app')

@section('title', 'Upload Transaksi')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="bi bi-cloud-upload me-2"></i> Upload Transaksi dari File
        </h5>
      </div>
      <div class="card-body">
        <!-- Upload Instructions -->
        <div class="alert alert-info mb-4">
          <div class="d-flex">
            <div class="flex-shrink-0">
              <i class="bi bi-info-circle-fill fs-4"></i>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="alert-heading">Panduan Upload</h6>
              <ul class="mb-0">
                <li>File harus dalam format CSV, XLS, atau XLSX</li>
                <li>File harus memiliki header dengan kolom berikut: Tanggal, Deskripsi, Jumlah, Tipe, Kategori, Tag, Catatan</li>
                <li>Format tanggal: YYYY-MM-DD (contoh: 2024-01-15)</li>
                <li>Tipe harus: <code>income</code>, <code>expense</code>, atau <code>transfer</code></li>
                <li>Kategori harus sesuai dengan kategori yang sudah ada</li>
                <li>Tag dapat dipisahkan dengan koma (contoh: Makanan,Belanja)</li>
              </ul>
            </div>
          </div>
        </div>
                
        <!-- Upload Form -->
        <form action="{{ route('apps.uploads') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
          @csrf

          <!-- Account Selection -->
          <div class="mb-4">
            <label for="account_id" class="form-label fw-semibold">
              <i class="bi bi-credit-card me-2"></i>Pilih Akun <span class="text-danger">*</span>
            </label>
            <select class="form-select @error('account_id') is-invalid @enderror" id="account_id" name="account_id" required>
              <option value="">Pilih Akun Tujuan</option>
              @foreach($accounts as $account)
                <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                  {{ $account->name }} - @money($account->balance->getMinorAmount()->toInt())
                  @if($account->type)
                    <small class="text-muted">({{ $account->type }})</small>
                  @endif
                </option>
              @endforeach
            </select>
            @error('account_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">
              Semua transaksi yang diupload akan dimasukkan ke akun ini.
            </small>
          </div>
                    
          <!-- File Upload -->
          <div class="mb-4">
            <label for="file" class="form-label fw-semibold">
              <i class="bi bi-file-earmark-arrow-up me-2"></i>Pilih File <span class="text-danger">*</span>
            </label>
            <div class="file-upload-area border rounded p-4 text-center" id="fileUploadArea" style="background-color: #f8f9fa; border-style: dashed !important;">
              <i class="bi bi-cloud-arrow-up fs-1 text-muted mb-3"></i>
              <p class="mb-2">Drag & drop file atau klik untuk memilih</p>
              <p class="small text-muted mb-3">Format: CSV, XLS, XLSX (maks. 10MB)</p>
                            
              <input type="file" class="form-control d-none" id="file" name="file" accept=".csv,.xls,.xlsx,.txt" required>
                            
              <button type="button" class="btn btn-outline-primary btn-sm" id="browseBtn">
                <i class="bi bi-folder2-open me-1"></i> Browse File
              </button>
                            
              <div class="mt-3" id="selectedFileInfo" style="display: none;">
                <div class="alert alert-success py-2">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <i class="bi bi-file-earmark-check me-2"></i>
                      <span id="fileName"></span>
                      <small class="text-muted d-block" id="fileSize"></small>
                    </div>
                    <button type="button" class="btn-close btn-close-sm" onclick="clearFileSelection()"></button>
                  </div>
                </div>
              </div>
            </div>
            @error('file')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>
                    
          <!-- Advanced Options -->
          <div class="accordion mb-4" id="advancedOptions">
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#advancedSettings">
                  <i class="bi bi-gear me-2"></i> Pengaturan Lanjutan
                </button>
              </h2>
              <div id="advancedSettings" class="accordion-collapse collapse" data-bs-parent="#advancedOptions">
                <div class="accordion-body">
                  <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="skip_header" name="skip_header" value="1">
                    <label class="form-check-label" for="skip_header">
                      File tidak memiliki header (baris pertama)
                    </label>
                    <small class="text-muted d-block">
                      Jika dicentang, sistem akan menganggap baris pertama sebagai data transaksi.
                    </small>
                  </div>
                                    
                  <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="auto_create_categories" name="auto_create_categories" value="1" checked>
                    <label class="form-check-label" for="auto_create_categories">
                      Buat kategori baru secara otomatis
                    </label>
                    <small class="text-muted d-block">
                      Kategori yang belum ada akan dibuat otomatis.
                    </small>
                  </div>
                                    
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="auto_create_tags" 
                     name="auto_create_tags" value="1" checked>
                    <label class="form-check-label" for="auto_create_tags">
                      Buat tag baru secara otomatis
                    </label>
                    <small class="text-muted d-block">
                      Tag yang belum ada akan dibuat otomatis.
                    </small>
                  </div>
                </div>
              </div>
            </div>
          </div>
                    
          <!-- Action Buttons -->
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <a href="{{ route('transactions.upload.template') }}" class="btn btn-outline-secondary">
                <i class="bi bi-download me-1"></i> Download Template
              </a>
            </div>
            <div>
              <button type="reset" class="btn btn-secondary me-2">
                <i class="bi bi-x-circle me-1"></i> Reset
              </button>
              <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="bi bi-cloud-upload me-1"></i> Upload & Proses
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('file');
        const browseBtn = document.getElementById('browseBtn');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const selectedFileInfo = document.getElementById('selectedFileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');
        
        // Browse button click
        browseBtn.addEventListener('click', function() {
            fileInput.click();
        });
        
        // File input change
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                displayFileInfo(file);
            }
        });
        
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            fileUploadArea.style.backgroundColor = '#e9ecef';
            fileUploadArea.style.borderColor = '#0d6efd';
        }
        
        function unhighlight() {
            fileUploadArea.style.backgroundColor = '#f8f9fa';
            fileUploadArea.style.borderColor = '#dee2e6';
        }
        
        fileUploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                displayFileInfo(files[0]);
            }
        }
        
        // Display file info
        function displayFileInfo(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            selectedFileInfo.style.display = 'block';
            fileUploadArea.style.borderStyle = 'solid';
        }
        
        // Clear file selection
        window.clearFileSelection = function() {
            fileInput.value = '';
            selectedFileInfo.style.display = 'none';
            fileUploadArea.style.borderStyle = 'dashed';
        };
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Form submission
        uploadForm.addEventListener('submit', function(e) {
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Silakan pilih file terlebih dahulu.');
                return;
            }
            
            // Show loading state
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Memproses...
            `;
            submitBtn.disabled = true;
        });
    });
</script>
@endpush

@push('styles')
<style>
    .file-upload-area {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .file-upload-area:hover {
        background-color: #e9ecef !important;
        border-color: #0d6efd !important;
    }
    
    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: #0d6efd;
    }
    
    #selectedFileInfo .alert {
        margin-bottom: 0;
    }
</style>
@endpush