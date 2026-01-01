@extends('wallet::layouts.app')

@section('title', 'Create Transaction')

@use('Modules\Wallet\Helpers\Helper')
@use('Modules\Wallet\Enums\TransactionType')
@use('Modules\Wallet\Enums\CategoryType')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <a href="{{ route('apps.transactions.index') }}" class="btn btn-outline-secondary" role="button">
      <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
  </div>
  <h1 class="page-title">Buat Transaksi</h1>
</div>

<!-- Transaction Form -->
<form method="POST" action="{{ route('apps.transactions.store') }}" id="transactionForm">
  @csrf
  <div class="row">
    <!-- Left Column - Basic Information -->
    <div class="col-lg-8">
      <!-- Transaction Type Section -->
      <div class="form-section">
        <h5>Type</h5>
        <div class="type-toggle mb-4">
          @foreach(TransactionType::cases() as $type)
            <input type="radio" id="type_{{ $type->value }}" name="type" value="{{ $type->value }}" @checked(old('type', $preset['type']) === $type->value)>
            <label for="type_{{ $type->value }}" class="text-{{ $type->value }}">
              @switch($type->value)
                @case('income')
                <i class="bi bi-arrow-up-circle me-2"></i>{{ $type->name}}
                @break
                @case('expense')
                <i class="bi bi-arrow-down-circle me-2"></i>{{ $type->name }}
                @break
              @endswitch
            </label>
          @endforeach
        </div>
        
        <!-- Amount Input -->
        <div class="mb-4">
          <label for="amount" class="form-label">Amount</label>
          <div class="input-group amount-input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" class="form-control amount-input" id="amount" name="amount" value="{{ old('amount', $preset['amount']) }}" placeholder="0" required>
          </div>
          @error('amount')
          <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
        </div>
        
        <!-- Title and Description -->
        <div class="row">
          <div class="col-md-8 mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $preset['title']) }}" placeholder="Ex: Gaji Bulanan, Belanja Bulanan" required>
            @error('title')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4 mb-3">
            <label for="transaction_date" class="form-label">Date</label>
            <input type="date" class="form-control" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}" required>
            @error('transaction_date')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
        </div>
        
        <!-- Description -->
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control" id="description" name="description" rows="3" placeholder="Tambahkan catatan untuk transaksi ini...">{{ old('description') }}</textarea>
          @error('description')
          <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>
      
      <!-- Category Selection -->
      <div class="form-section">
        <h5>Pilih Kategori</h5>
        
        <!-- Income Category hidden by default -->
        <div id="incomeCategories" class="category-options" style="{{ old('type', $preset['type']) == CategoryType::INCOME->value ? '' : 'display: none;' }}">
          @forelse($incomeCategories as $category)
          <label class="category-option">
            <input type="radio" name="category_id" value="{{ $category->id}}" @checked(old('category_id', $preset['category_id']) == $category->id)>
            <div class="d-flex align-items-center w-100">
              <div class="category-icon">
                <i class="{{ $category->icon }}"></i>
              </div>
              <div class="flex-grow-1">
                <div class="fw-medium">{{ $category->name }}</div>
                @if($category->budget_limit)
                <small class="text-muted">Anggaran: {{ $category->formatted_budget_limit }}</small>
                @endif
              </div>
            </div>
          </label>
          @empty
          <div class="text-center py-4">
            <i class="bi bi-tag display-4 text-muted"></i>
            <p class="text-muted mt-2">Belum ada kategori {{ CategoryType::INCOME->value}}</p>
            <a href="{{ route('apps.categories.create', ['type' => CategoryType::INCOME->value]) }}" class="btn btn-sm btn-primary">Create Category</a>
          </div>
          @endforelse
        </div>
        
        <!-- Expense Category shown by default -->
        <div id="expenseCategories" class="category-options" style="{{ old('type', $preset['type']) == CategoryType::EXPENSE->value ? '' : 'display: none;' }}">
          @forelse($expenseCategories as $category)
          <label class="category-option"></label>
          @empty
          <div class="text-center py-4">
            <i class="bi bi-tag display-4 text-muted"></i>
            <p class="text-muted mt-2">Belum ada kategori {{ CategoryType::EXPENSE->value }}</p>
            <a href="{{ route('apps.categories.create', ['type' => CategoryType::EXPENSE->value]) }}" class="btn btn-sm btn-primary">Create Category</a>
          </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@push('styles')
<style>
  .form-section {
    background-color: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }
  
  .form-section h5 {
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #dee2e6;
  }
  
  .amount-input-group {
    position: relative;
  }
  
  .amount-input-group .input-group-text {
    background-color: #f8f9fa;
    border-right: none;
  }
  
  .amount-input-group input {
    border-left: none;
    padding-left: 0;
  }
  
  .type-toggle {
    display: flex;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
  }
  
  .type-toggle label {
    flex: 1;
    padding: 0.75rem;
    text-align: center;
    cursor: pointer;
    margin: 0;
    transition: all 0.3s;
  }
  
  .type-toggle input[type="radio"]{
    display: none;
  }
  
  .type-toggle input[type="radio"]:checked + label {
    background-color: #4361ee;
    color: white;
  }
  
  .type-toggle label[for="{{ CategoryType::INCOME->value }}"] {
    border-right: 1px solid #dee2e6;
  }
  
  .category-options {
    max-height: 300px;
    overflow-y: auto;
  }
  
  .category-option {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  
  
  
  body[data-bs-theme="dark"] .form-section {
    background-color: #1e1e1e;
  }
  
  body[data-bs-theme="dark"] .type-toggle {
    border-color: #495057;
  }
</style>
@endpush