@extends('layouts.app')
@section('title','Edit Receipt')

@section('content')
<div class="d-flex justify-content-end mb-3">
  <a href="{{ route('receipts.show', $receipt) }}" class="btn btn-outline-secondary">
    <i class="bi bi-eye me-1"></i> View Receipt
  </a>
</div>

<div class="card">
  <div class="card-header fw-semibold">Update Receipt</div>
  <div class="card-body">
    <form method="POST" action="{{ route('receipts.update', $receipt) }}">
      @csrf @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="student_name" name="student_name" required
                   value="{{ old('student_name',$receipt->student_name) }}" placeholder="Student Name">
            <label for="student_name">Student Name</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="class_name" name="class_name"
                   value="{{ old('class_name',$receipt->class_name) }}" placeholder="Class">
            <label for="class_name">Class (e.g. Form I)</label>
          </div>
        </div>

        <div class="col-md-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span class="fw-semibold">Payment Categories</span>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPaymentCategory()">
                <i class="bi bi-plus me-1"></i> Add Category
              </button>
            </div>
            <div class="card-body">
              <div id="paymentCategoriesContainer">
                @php
                  $existingCategories = $receipt->paymentCategories;
                  $index = 0;
                @endphp
                @if($existingCategories->count() > 0)
                  @foreach($existingCategories as $category)
                    <div class="payment-category-row row g-3 mb-3" data-index="{{ $index }}">
                      <div class="col-md-6">
                        <div class="form-floating">
                          <select class="form-select payment-category-select" name="payment_categories[{{ $index }}][category_id]" required>
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                              <option value="{{ $cat->id }}" data-amount="{{ $cat->default_amount ?? '' }}" 
                                @selected($category->id == $cat->id)>
                                {{ $cat->name }}{{ $cat->default_amount ? ' — Tsh '.number_format($cat->default_amount) : '' }}
                              </option>
                            @endforeach
                          </select>
                          <label for="payment_category_{{ $index }}">Payment Category</label>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-floating">
                          <input type="number" min="1" class="form-control category-amount" name="payment_categories[{{ $index }}][amount]"
                                 placeholder="Amount" required value="{{ $category->pivot->amount }}">
                          <label for="amount_{{ $index }}">Amount (Tsh)</label>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-floating">
                          <input type="text" class="form-control total-amount-display" readonly
                                 value="{{ number_format($category->pivot->amount) }}" placeholder="Total">
                          <label>Total</label>
                        </div>
                      </div>
                      <div class="col-md-12">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentCategory(this)">
                          <i class="bi bi-trash me-1"></i> Remove
                        </button>
                      </div>
                    </div>
                    @php($index++)
                  @endforeach
                @else
                  <div class="payment-category-row row g-3 mb-3" data-index="0">
                    <div class="col-md-6">
                      <div class="form-floating">
                        <select class="form-select payment-category-select" name="payment_categories[0][category_id]" required>
                          <option value="">Select category</option>
                          @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" data-amount="{{ $cat->default_amount ?? '' }}">
                              {{ $cat->name }}{{ $cat->default_amount ? ' — Tsh '.number_format($cat->default_amount) : '' }}
                            </option>
                          @endforeach
                        </select>
                        <label for="payment_category_0">Payment Category</label>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-floating">
                        <input type="number" min="1" class="form-control category-amount" name="payment_categories[0][amount]"
                               placeholder="Amount" required>
                        <label for="amount_0">Amount (Tsh)</label>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-floating">
                        <input type="text" class="form-control total-amount-display" readonly
                               value="0" placeholder="Total">
                        <label>Total</label>
                      </div>
                    </div>
                  </div>
                @endif
              </div>
              <div class="row mt-3">
                <div class="col-md-12">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Total Amount:</span>
                    <span class="fs-5 fw-bold text-primary">Tsh <span id="totalAmount">{{ number_format($receipt->amount) }}</span></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="form-floating">
            <input type="date" class="form-control" id="payment_date" name="payment_date" required
                   value="{{ old('payment_date',$receipt->payment_date) }}" placeholder="Payment Date">
            <label for="payment_date">Payment Date</label>
          </div>
        </div>

        <div class="col-md-4">
          <div class="form-floating">
            <select class="form-select" id="payment_mode" name="payment_mode" required>
              @foreach(['Cash','Bank','Mobile Money','Other'] as $mode)
                <option @selected(old('payment_mode',$receipt->payment_mode)===$mode)>{{ $mode }}</option>
              @endforeach
            </select>
            <label for="payment_mode">Payment Mode</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="reference" name="reference"
                   value="{{ old('reference',$receipt->reference) }}" placeholder="Reference (optional)">
            <label for="reference">Reference (optional)</label>
          </div>
        </div>

        <div class="col-md-12">
          <div class="form-floating">
            <textarea class="form-control" id="note" name="note" style="height: 120px"
                      placeholder="Receipt note (optional)">{{ old('note',$receipt->note) }}</textarea>
            <label for="note">Receipt Note (optional)</label>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary">
          <i class="bi bi-save me-1"></i> Update
        </button>
        <a href="{{ route('receipts.show',$receipt) }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Payment Categories JavaScript
  let categoryIndex = {{ max($receipt->paymentCategories->count(), 1) }};

  function addPaymentCategory() {
    const container = document.getElementById('paymentCategoriesContainer');
    const newRow = document.createElement('div');
    newRow.className = 'payment-category-row row g-3 mb-3';
    newRow.dataset.index = categoryIndex;
    
    newRow.innerHTML = `
        <div class="col-md-6">
            <div class="form-floating">
                <select class="form-select payment-category-select" name="payment_categories[${categoryIndex}][category_id]" required>
                    <option value="">Select category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" data-amount="{{ $cat->default_amount ?? '' }}">
                            {{ $cat->name }}{{ $cat->default_amount ? ' — Tsh '.number_format($cat->default_amount) : '' }}
                        </option>
                    @endforeach
                </select>
                <label for="payment_category_${categoryIndex}">Payment Category</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <input type="number" min="1" class="form-control category-amount" name="payment_categories[${categoryIndex}][amount]"
                       placeholder="Amount" required>
                <label for="amount_${categoryIndex}">Amount (Tsh)</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-floating">
                <input type="text" class="form-control total-amount-display" readonly
                       value="0" placeholder="Total">
                <label>Total</label>
            </div>
        </div>
        <div class="col-md-12">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentCategory(this)">
                <i class="bi bi-trash me-1"></i> Remove
            </button>
        </div>
    `;
    
    container.appendChild(newRow);
    attachCategoryListeners(newRow);
    categoryIndex++;
  }

  function removePaymentCategory(button) {
    const row = button.closest('.payment-category-row');
    row.remove();
    updateTotalAmount();
  }

  function attachCategoryListeners(row) {
    const select = row.querySelector('.payment-category-select');
    const amountInput = row.querySelector('.category-amount');
    
    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const defaultAmount = option?.dataset?.amount ? parseInt(option.dataset.amount, 10) : 0;
        if (defaultAmount && (!amountInput.value || Number(amountInput.value) === 0)) {
            amountInput.value = defaultAmount;
        }
        updateTotalAmount();
    });
    
    amountInput.addEventListener('input', updateTotalAmount);
  }

  function updateTotalAmount() {
    let total = 0;
    document.querySelectorAll('.category-amount').forEach(input => {
        const value = parseFloat(input.value) || 0;
        total += value;
        
        // Update individual row total display
        const row = input.closest('.payment-category-row');
        const totalDisplay = row.querySelector('.total-amount-display');
        if (totalDisplay) {
            totalDisplay.value = value > 0 ? value.toLocaleString() : '0';
        }
    });
    
    document.getElementById('totalAmount').textContent = total.toLocaleString();
  }

  // Initialize listeners for existing rows
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.payment-category-row').forEach(row => {
        attachCategoryListeners(row);
    });
    
    // Initial total calculation
    updateTotalAmount();
  });
</script>
@endpush
