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
        <x-form-actions :cancelUrl="route('fee-structures.index')" submitLabel="Update fee structure" submitIcon="bi-check-lg" />
      </div>
    </form>
  </div>
</div>
@endsection
