@extends('layouts.app')
@section('title','Edit Class')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">Update Class</div>
  <div class="card-body">
    <form method="POST" action="{{ route('classes.update', $class) }}">
      @csrf @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="name" name="name" placeholder="Class name" required value="{{ old('name',$class->name) }}">
            <label for="name">Class Name</label>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Update</button>
        <a href="{{ route('classes.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
