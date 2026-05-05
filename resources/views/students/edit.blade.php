@extends('layouts.app')
@section('title','Edit Student')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">Update Student</div>
  <div class="card-body">
    <form method="POST" action="{{ route('students.update',$student) }}">
      @csrf @method('PUT')
      <div class="row g-3">
        <div class="col-md-4">
          <div class="form-floating">
            <input type="text" class="form-control" id="admission_no" name="admission_no" placeholder="Admission no" value="{{ old('admission_no',$student->admission_no) }}">
            <label for="admission_no">Admission No</label>
          </div>
        </div>
        <div class="col-md-8">
          <div class="form-floating">
            <input type="text" class="form-control" id="name" name="name" placeholder="Student name" required value="{{ old('name',$student->name) }}">
            <label for="name">Student Name</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="class_name" name="class_name" placeholder="Class" value="{{ old('class_name',$student->class_name) }}">
            <label for="class_name">Class (e.g. Form I)</label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-floating">
            <input type="text" class="form-control" id="parent_name" name="parent_name" placeholder="Parent name" value="{{ old('parent_name',$student->parent_name) }}">
            <label for="parent_name">Parent Name</label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-floating">
            <input type="text" class="form-control" id="parent_phone" name="parent_phone" placeholder="Parent phone" value="{{ old('parent_phone',$student->parent_phone) }}">
            <label for="parent_phone">Parent Phone</label>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-floating">
            <input type="email" class="form-control" id="parent_email" name="parent_email" placeholder="Parent email" value="{{ old('parent_email',$student->parent_email) }}">
            <label for="parent_email">Parent Email</label>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Fee Due Date</label>
          <input type="date" class="form-control" name="fee_due_date" value="{{ old('fee_due_date',optional($student->fee_due_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-6">
          <label class="form-label">Expected Total Fee (TZS)</label>
          <input type="number" min="0" class="form-control" name="expected_total_fee" value="{{ old('expected_total_fee',$student->expected_total_fee) }}">
        </div>
        <div class="col-12">
          <label class="form-label">Assign Fee Structures</label>
          @php
            $selectedStructures = collect(old('fee_structure_ids', $student->feeStructures->pluck('id')->all()));
          @endphp
          <select class="form-select" name="fee_structure_ids[]" multiple>
            @foreach($feeStructures as $feeStructure)
              <option value="{{ $feeStructure->id }}" @selected($selectedStructures->contains($feeStructure->id))>
                {{ $feeStructure->name }} (Tsh {{ number_format($feeStructure->amount) }})
              </option>
            @endforeach
          </select>
          <div class="form-text">Hold Ctrl/Cmd to select multiple items.</div>
        </div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Update</button>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
