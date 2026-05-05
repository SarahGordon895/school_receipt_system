<div class="row g-3">
  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" class="form-control" id="name" name="name" placeholder="Name" required value="{{ old('name', $feeStructure?->name) }}">
      <label for="name">Name</label>
    </div>
  </div>
  <div class="col-md-6">
    <label class="form-label">Amount (TZS)</label>
    <input type="number" class="form-control" name="amount" min="1" required value="{{ old('amount', $feeStructure?->amount ?? 0) }}">
  </div>
  <div class="col-md-6">
    <div class="form-floating">
      <input type="text" class="form-control" name="class_name" id="class_name" placeholder="Class"
             value="{{ old('class_name', $feeStructure?->class_name) }}">
      <label for="class_name">Class (e.g. Form I)</label>
    </div>
  </div>
  <div class="col-md-6">
    <label class="form-label">Due Date</label>
    <input type="date" class="form-control" name="due_date" value="{{ old('due_date', optional($feeStructure?->due_date)->format('Y-m-d')) }}">
  </div>
  <div class="col-md-6 d-flex align-items-center">
    <div class="form-check mt-4">
      <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $feeStructure?->is_active ?? true))>
      <label class="form-check-label" for="is_active">Active fee structure</label>
    </div>
  </div>
</div>
