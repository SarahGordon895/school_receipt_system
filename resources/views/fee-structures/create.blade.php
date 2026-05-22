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
        <x-form-actions :cancelUrl="route('fee-structures.index')" submitLabel="Save fee structure" />
      </div>
    </form>
  </div>
</div>
@endsection
