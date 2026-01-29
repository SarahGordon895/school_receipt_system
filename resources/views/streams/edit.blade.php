@extends('layouts.app')
@section('title','Edit Stream')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">Update Stream</div>
  <div class="card-body">
    <form method="POST" action="{{ route('streams.update', $stream) }}">
      @csrf @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <select class="form-select" id="class_id" name="class_id" required>
              <option value="">Select class</option>
              @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(old('class_id',$stream->class_id)==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
            <label for="class_id">Class</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="name" name="name" placeholder="Stream name" required value="{{ old('name',$stream->name) }}">
            <label for="name">Stream Name</label>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Update</button>
        <a href="{{ route('streams.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
