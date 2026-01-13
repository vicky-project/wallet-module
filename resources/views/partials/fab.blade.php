<!-- Floating Action Button (FAB) -->
<div class="fab-container" id="fabContainer">
  <div class="fab-menu" id="fabMenu">
    <a href="#" class="fab-item" id="fabIncome">
      <i class="bi bi-plus-circle fab-income"></i>
      <span class="fab-label">Tambah Pemasukan</span>
    </a>
    <a href="#" class="fab-item" id="fabExpense">
      <i class="bi bi-dash-circle fab-expense"></i>
      <span class="fab-label">Tambah Pengeluaran</span>
    </a>
    <a href="#" class="fab-item" id="fabRecurring">
      <i class="bi bi-repeat fab-recurring"></i>
      <span class="fab-label">Transaksi Rutin</span>
    </a>
    <a href="#" class="fab-item" id="fabReport">
      <i class="bi bi-file-earmark-text fab-report"></i>
      <span class="fab-label">Laporan</span>
    </a>
  </div>
  <button class="fab-main" id="fabMain">
    <i class="bi bi-plus-lg" id="fabIcon"></i>
  </button>
</div>
    
    
<script>
  // Elemen FAB
  const fabMain = document.getElementById('fabMain');
  const fabMenu = document.getElementById('fabMenu');
  const fabIcon = document.getElementById('fabIcon');
  const fabIncome = document.getElementById('fabIncome');
  const fabExpense = document.getElementById('fabExpense');
  const fabRecurring = document.getElementById('fabRecurring');
  const fabReport = document.getElementById('fabReport');
  
  // FAB Toggle Functionality
  function toggleFabMenu() {
    fabMain.classList.toggle('active');
    fabMenu.classList.toggle('active');
                
    if (fabMain.classList.contains('active')) {
      fabIcon.classList.remove('bi-plus-lg');
      fabIcon.classList.add('bi-x');
    } else {
      fabIcon.classList.remove('bi-x');
      fabIcon.classList.add('bi-plus-lg');
    }
  }

  // Toggle FAB Menu
  fabMain.addEventListener('click', function(e) {
    e.stopPropagation();
    toggleFabMenu();
  });

  // Tutup FAB Menu ketika klik di luar
  document.addEventListener('click', function(e) {
    if (!fabMain.contains(e.target) && !fabMenu.contains(e.target)) {
      if (fabMenu.classList.contains('active')) {
        toggleFabMenu();
      }
    }
  });
  
  window.addEventListener('resize', function(){
    if(fabMenu.classList.contains('active')) {
      toggleFabMenu();
    }
  });

  // Tutup FAB Menu ketika klik item menu
  [fabIncome, fabExpense, fabRecurring, fabReport].forEach(item => {
    item.addEventListener('click', function(e) {
      e.stopPropagation();
      const action = this.id.replace('fab', '').toLowerCase();
      console.log(`Aksi FAB: ${action}`);

      // Tutup menu setelah memilih
      setTimeout(() => {
        if (fabMenu.classList.contains('active')) {
          toggleFabMenu();
        }
      }, 300);

      // Simulasi aksi (dalam implementasi nyata akan redirect ke halaman tertentu)
      switch(action) {
        case 'income':
          window.location.href = '{{ route("apps.transactions.create", ["type" => "income"]) }}';
          break;
        case 'expense':
          window.location.href = '{{ route("apps.transactions.create", ["type" => "expense"]) }}';
          break;
        case 'recurring':
          alert('Membuka halaman Transaksi Rutin');
          // window.location.href = '/transactions/recurring';
          break;
        case 'report':
          alert('Membuka halaman Laporan');
          // window.location.href = '/reports';
          break;
      }
    });
  });
</script>