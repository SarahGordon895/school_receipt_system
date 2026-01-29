@extends('layouts.app')
@section('title','Add Student')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">New Student</div>
  <div class="card-body">
    <form method="POST" action="{{ route('students.store') }}">
      @csrf
      <div class="form-floating">
        <input type="text" class="form-control" id="name" name="name" placeholder="Student name" required>
        <label for="name">Student Name</label>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
