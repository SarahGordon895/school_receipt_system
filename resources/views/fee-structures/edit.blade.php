@extends('layouts.app')
@section('title','Edit Fee Structure')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">Update Fee Structure</div>
  <div class="card-body">
    <form method="POST" action="{{ route('fee-structures.update', $feeStructure) }}">
      @csrf
      @method('PUT')
      @include('fee-structures.partials.form', ['feeStructure' => $feeStructure])
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Update</button>
        <a href="{{ route('fee-structures.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
