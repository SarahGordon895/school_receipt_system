@extends('layouts.app')
@section('title','Add Fee Structure')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">New Fee Structure</div>
  <div class="card-body">
    <form method="POST" action="{{ route('fee-structures.store') }}">
      @csrf
      @include('fee-structures.partials.form', ['feeStructure' => null])
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
        <a href="{{ route('fee-structures.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
