<div class="sidebar" id="sidebar">
  <div class="sidebar-brand d-flex justify-content-between align-items-center">
    <div>
      <h3 class="mb-1"><i class="bi bi-wallet2"></i> {{ config('app.name', 'VickyServer') }}</h3>
      <small class="text-light opacity-75">Manajemen Keuangan Pribadi</small>
    </div>
  </div>

  <ul class="sidebar-nav">
    <li>
      <a href="{{ route('apps.financial') }}" class="{{ request()->routeIs('apps.financial') ? 'active' : ''}}">
        <i class="bi bi-house-door"></i> Dashboard
      </a>
    </li>
    <li>
      <a href="{{ route('apps.accounts.index') }}" class="{{ request()->routeIs('apps.accounts.*') ? 'active' : '' }}">
        <i class="bi bi-wallet"></i> Accounts
      </a>
    </li>
    <li>
      <a href="{{ route('apps.transactions.index') }}" class="{{ request()->routeIs('apps.transactions.*') ? 'active' : '' }}">
        <i class="bi bi-receipt"></i> Transactions
      </a>
    </li>
    <li>
      <a href="{{ route('apps.categories.index') }}" class="{{ request()->routeIs('apps.categories.*') ? 'active' : '' }}">
        <i class="bi bi-pie-chart"></i> Categories
      </a>
    </li>
    <li>
      <a href="{{ route('apps.budgets.index') }}" class="{{ request()->routeIs('apps.budgets.*') ? 'active' : ''}}">
        <i class="bi bi-wallet-fill"></i> Budgets
      </a>
    </li>
    <li>
      <a href="{{ route('apps.recurrings.index') }}" class="{{ request()->routeIs('apps.recurrings.*') ? 'active' : ''}}">
        <i class="bi bi-calendar-week"></i> Recurrings
      </a>
    </li>
    <li>
      <a href="{{ route('apps.tags.index') }}" class="{{ request()->routeIs('apps.tags.*') ? 'active' : ''}}">
        <i class="bi bi-tags"></i> Tags
      </a>
    </li>
    <li>
      <a href="{{ route('apps.reports') }}" class="{{ request()->routeIs('apps.reports') }}">
        <i class="bi bi-graph-up"></i> Laporan
      </a>
    </li>
    @if(Route::has('settings'))
    <li class="mt-4">
      <a href="{{ route('settings') }}">
        <i class="bi bi-gear"></i> Pengaturan
      </a>
    </li>
    @endif
    <li>
      <a href="#">
        <i class="bi bi-question-circle"></i> Bantuan
      </a>
    </li>
  </ul>

  <div class="position-absolute bottom-0 w-100 p-3 text-center">
    <div class="card bg-dark text-white">
      <div class="card-body py-3">
        <small></small>
        <h5 class="mb-0"></h5>
      </div>
    </div>
  </div>
</div>