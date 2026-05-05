@extends('layouts.app') 
@section('title', 'Generate Receipt')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Receipts
        </a>
    </div>

    <div class="card">
        <div class="card-header fw-semibold">Receipt Details</div>
        <div class="card-body">
            <form method="POST" action="{{ route('receipts.store') }}" id="receiptForm" autocomplete="off">
                @csrf
                <div class="row g-3">

                    {{-- Student typeahead --}}
                    <div class="col-md-6 position-relative">
                        <input type="hidden" name="student_id" id="student_id" value="{{ old('student_id') }}">
                        <div class="form-floating">
                            <input
                                type="text"
                                class="form-control"
                                id="student_name"
                                name="student_name"
                                placeholder="Student Name"
                                value="{{ old('student_name') }}"
                                autocomplete="off"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-owns="student_suggestions"
                                aria-haspopup="listbox"
                                required
                            >
                            <label for="student_name">Student Name</label>
                        </div>

                        {{-- suggestions dropdown --}}
                        <ul id="student_suggestions"
                            class="list-group position-absolute w-100 shadow-sm"
                            role="listbox"
                            style="max-height: 240px; overflow:auto; z-index: 2060; display:none;">
                        </ul>

                        <div class="form-text">
                            Start typing to search. Press <kbd>↓/↑</kbd> to navigate, <kbd>Enter</kbd> to select.
                            If not found, keep the typed name—we’ll save it as entered.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="class_name" name="class_name"
                                   placeholder="Class (e.g. Form I)" value="{{ old('class_name') }}">
                            <label for="class_name">Class (e.g. Form I)</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required
                                   value="{{ old('payment_date', now()->toDateString()) }}">
                            <label for="payment_date">Payment Date</label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select" id="payment_mode" name="payment_mode" required>
                                @foreach (['Cash', 'Bank', 'Mobile Money', 'Other'] as $mode)
                                    <option @selected(old('payment_mode') === $mode)>{{ $mode }}</option>
                                @endforeach
                            </select>
                            <label for="payment_mode">Payment Mode</label>
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
                                    <div class="payment-category-row row g-3 mb-3" data-index="0">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select payment-category-select" name="payment_categories[0][category_id]" required>
                                                    <option value="">Select category</option>
                                                    @foreach(\App\Models\PaymentCategory::orderBy('name')->get() as $cat)
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
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold">Total Amount:</span>
                                            <span class="fs-5 fw-bold text-primary">Tsh <span id="totalAmount">0</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="reference" name="reference"
                                   placeholder="Reference (optional)" value="{{ old('reference') }}">
                            <label for="reference">Reference (optional)</label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-floating">
                            <textarea class="form-control" id="note" name="note" style="height: 120px"
                                      placeholder="Receipt note (optional)">{{ old('note') }}</textarea>
                            <label for="note">Receipt Note (optional)</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary" onclick="console.log('Button clicked');">
                        <i class="bi bi-save me-1"></i> Save & Print
                    </button>
                    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
/* ---------- Student Typeahead ---------- */
const input     = document.getElementById('student_name');
const hiddenId  = document.getElementById('student_id');
const list      = document.getElementById('student_suggestions');

let items = [];
let activeIndex = -1;
let lastQuery = '';
const debounce = (fn, d=220) => { let t; return (...a) => { clearTimeout(t); t=setTimeout(()=>fn(...a), d); }; };

function showList() {
  list.style.display = items.length ? 'block' : 'none';
  input.setAttribute('aria-expanded', items.length ? 'true' : 'false');
}
function hideList() {
  list.style.display = 'none';
  input.setAttribute('aria-expanded', 'false');
  activeIndex = -1;
}
function highlight(text, q) {
  if (!q) return text;
  try {
    const rx = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')', 'ig');
    return text.replace(rx, '<mark>$1</mark>');
  } catch { return text; }
}
function renderList(q='') {
  list.innerHTML = items.map((it, i) => `
    <li class="list-group-item list-group-item-action ${i===activeIndex?'active':''}"
        role="option" data-id="${it.id}" data-name="${it.name}">
      <span class="d-block">${highlight(it.name, q)}</span>
    </li>`).join('');
  showList();
}

async function fetchStudents(q) {
  if (!q || q.length < 2) { items = []; renderList(''); return; }
  try {
    const res = await fetch(`{{ route('api.students.search') }}?s=` + encodeURIComponent(q), {
      headers: {'X-Requested-With':'XMLHttpRequest'}
    });
    items = await res.json();
    renderList(q);
  } catch {
    items = []; renderList('');
  }
}

const fetchDebounced = debounce(fetchStudents, 220);

input.addEventListener('input', (e) => {
  const v = e.target.value.trim();
  if (hiddenId.value) hiddenId.value = ''; // user typed after selecting; clear selection
  lastQuery = v;
  fetchDebounced(v);
});

input.addEventListener('keydown', (e) => {
  const key = e.key;
  if (list.style.display !== 'none') {
    if (key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(activeIndex+1, items.length-1); renderList(lastQuery); }
    else if (key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(activeIndex-1, 0); renderList(lastQuery); }
    else if (key === 'Enter') {
      if (activeIndex >= 0 && items[activeIndex]) {
        e.preventDefault();
        selectItem(items[activeIndex]);
      }
    } else if (key === 'Escape') { hideList(); }
  } else {
    if (key === 'ArrowDown') { if (items.length) showList(); }
  }
});

list.addEventListener('mousedown', (e) => {
  // Use mousedown so we don't lose focus before click registers
  const li = e.target.closest('li[data-id]');
  if (!li) return;
  selectItem({ id: li.dataset.id, name: li.dataset.name });
});

function selectItem(it) {
  input.value = it.name;
  hiddenId.value = it.id;
  hideList();
}

// Hide suggestions when clicking outside
document.addEventListener('click', (e) => {
  if (!e.target.closest('#student_name') && !e.target.closest('#student_suggestions')) hideList();
});

// Prefill behavior on load if old values exist
@if (old('student_id') && old('student_name'))
  hiddenId.value = "{{ old('student_id') }}";
  input.value = @json(old('student_name'));
@endif
</script>
<script>
let categoryIndex = 1;

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
                    @foreach(\App\Models\PaymentCategory::orderBy('name')->get() as $cat)
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

// Initialize listeners for the first row
document.addEventListener('DOMContentLoaded', function() {
    try {
        const firstRow = document.querySelector('.payment-category-row');
        if (firstRow) {
            attachCategoryListeners(firstRow);
        }
        
        // Add form submission validation
        const form = document.getElementById('receiptForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Form submission triggered');
                
                const categorySelects = document.querySelectorAll('.payment-category-select');
                const amountInputs = document.querySelectorAll('.category-amount');
                let isValid = true;
                
                console.log('Category selects found:', categorySelects.length);
                console.log('Amount inputs found:', amountInputs.length);
                
                // Check if at least one category is selected with amount
                let hasValidCategory = false;
                categorySelects.forEach((select, index) => {
                    const amountInput = amountInputs[index];
                    console.log(`Category ${index}:`, select.value, 'Amount:', amountInput.value);
                    if (select.value && amountInput.value && parseFloat(amountInput.value) > 0) {
                        hasValidCategory = true;
                    }
                });
                
                console.log('Has valid category:', hasValidCategory);
                
                if (!hasValidCategory) {
                    e.preventDefault();
                    alert('Please select at least one payment category and enter a valid amount.');
                    return false;
                }
                
                // Update total amount hidden field before submission
                updateTotalAmount();
                console.log('Form will submit');
            });
        } else {
            console.error('Form not found');
        }
    } catch (error) {
        console.error('JavaScript error:', error);
    }
});
</script>

@endpush
