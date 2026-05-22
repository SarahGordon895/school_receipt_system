@extends('layouts.app')
@section('title','Edit Payment Category')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">Update Category</div>
  <div class="card-body">
    <form method="POST" action="{{ route('payment-categories.update',$cat) }}">
      @csrf @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name',$cat->name) }}" placeholder="Name" required>
            <label for="name">Category Name</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="number" min="0" class="form-control" id="default_amount" name="default_amount"
                   value="{{ old('default_amount',$cat->default_amount) }}" placeholder="Default Amount (optional)">
            <label for="default_amount">Default Amount (optional)</label>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <x-form-actions :cancelUrl="route('payment-categories.index')" submitLabel="Update category" submitIcon="bi-check-lg" />
      </div>
    </form>
  </div>
</div>
@endsection
