<div class="header">
  <div class="header-left">
    <button class="btn btn-outline-secondary sidebar-toggle d-lg-none me-2" id="sidebarToggle">
      <i class="bi bi-list"></i>
    </button>
  </div>
            
  <div class="header-actions">
    <!-- Tombol Tema Light/Dark dengan ikon -->
    <button class="btn theme-btn" id="themeToggle" title="Ubah Tema">
      <i class="bi bi-sun" id="themeIcon"></i>
    </button>
                
    <!-- Tombol Profile Dropdown dengan ikon saja -->
    <div class="dropdown">
      <button class="btn profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Profil Pengguna">
        <i class="bi bi-person-circle"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profil</a></li>
        <li><a class="dropdown-item" href="{{ route('telegram.link') }}"><i class="bi bi-gear me-2"></i> Pengaturan</a></li>
        @if(config('wallet.back_to_server_url'))
          <li><a href="{{ config('wallet.back_to_server_url') }}" class="dropdown-item"><i class="bi bi-server me-2"></i>Server</a></li>
        @endif
        <li><hr class="dropdown-divider"></li>
        @if(Route::has('logout'))
          <li>
            <form method="POST" action="{{ route('logout') }}" id="formLogout">
              @csrf
            </form>
            <button type="button" class="dropdown-item text-danger" onclick="if(confirm('Are you sure to logout this session?')) document.getElementById('formLogout').submit();"><i class="bi bi-box-arrow-right me-2"></i> Keluar</button>
          </li>
        @endif
      </ul>
    </div>
  </div>
</div>