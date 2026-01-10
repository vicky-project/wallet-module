@use('Modules\Wallet\Helpers\Helper')

<div class="mb-3">
  <label for="icon" class="form-label">Icon Kategori</label>
    
  <div class="d-flex">
    <!-- Input Text -->
    <div class="flex-grow-1 me-2">
      <input type="text" class="form-control @error('icon') is-invalid @enderror" id="icon" name="icon" value="{{ old('icon', $category->icon ?? 'bi-tag') }}" placeholder="bi-cash-stack" readonly>
    </div>
        
    <!-- Tombol Dropdown -->
    <div class="dropdown">
      <button type="button" class="btn btn-outline-secondary dropdown-toggle" id="iconPickerButton" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 60px;">
        <i id="selectedIconPreview" class="bi {{ $category->icon ?? 'bi-tag' }}"></i>
      </button>
            
      <!-- Simple Grid -->
      <div class="dropdown-menu p-2" style="width: 260px;height: 300px;overflow-y: scroll;">
        <div class="d-flex flex-wrap">
          @foreach(Helper::categoriesIconList() as $icon)
            <button type="button" class="btn btn-md btn-outline-secondary m-1 icon-simple-option" data-icon="{{ $icon }}" style="width: 50px; height: 50px;">
              <i class="bi {{ $icon }}"></i>
            </button>
          @endforeach
        </div>
      </div>
    </div>
  </div>
    
  @error('icon')
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
</div>
      
<script>
document.addEventListener('DOMContentLoaded', function() {
    const options = document.querySelectorAll('.icon-simple-option');
    const preview = document.getElementById('selectedIconPreview');
    const input = document.getElementById('icon');
    
    options.forEach(button => {
        button.addEventListener('click', function() {
            const iconClass = this.getAttribute('data-icon');
            preview.className = `bi ${iconClass}`;
            input.value = iconClass;
            
            // Close dropdown
            bootstrap.Dropdown.getInstance(document.getElementById('iconPickerButton')).hide();
        });
    });
});
</script>