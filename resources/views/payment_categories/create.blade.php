@extends('layouts.app')
@section('title','Add Payment Category')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">New Category</div>
  <div class="card-body">
    <form method="POST" action="{{ route('payment-categories.store') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
            <label for="name">Category Name</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="number" min="0" class="form-control" id="default_amount" name="default_amount" placeholder="Default Amount (optional)">
            <label for="default_amount">Default Amount (optional)</label>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
        <a href="{{ route('payment-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
