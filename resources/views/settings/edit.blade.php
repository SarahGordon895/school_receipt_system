@extends('layouts.app')
@section('title','Settings')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">School Information</div>
  <div class="card-body">
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
      @csrf @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="school_name" name="school_name" placeholder="School Name" required
                   value="{{ old('school_name',$setting->school_name) }}">
            <label for="school_name">School Name</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="reg_number" name="reg_number" placeholder="Reg Number"
                   value="{{ old('reg_number',$setting->reg_number) }}">
            <label for="reg_number">Registration Number</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="contact_phone" name="contact_phone" placeholder="Phone"
                   value="{{ old('contact_phone',$setting->contact_phone) }}">
            <label for="contact_phone">Contact Phone</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="Email"
                   value="{{ old('contact_email',$setting->contact_email) }}">
            <label for="contact_email">Contact Email</label>
          </div>
        </div>

        <div class="col-md-12">
          <div class="form-floating">
            <input type="text" class="form-control" id="address" name="address" placeholder="Address"
                   value="{{ old('address',$setting->address) }}">
            <label for="address">Address</label>
          </div>
        </div>
      </div>

      <div class="col-md-6">
  <label class="form-label">Logo</label>
  <input type="file" class="form-control" name="logo" accept="image/*">
  @if($setting->logo_path)
    <div class="mt-2 d-flex align-items-center gap-3">
      <img src="{{ asset('storage/'.$setting->logo_path) }}" alt="Logo" class="rounded border" style="height:48px;">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
        <label class="form-check-label" for="remove_logo">Remove logo</label>
      </div>
    </div>
  @endif
</div>

<div class="col-md-12 mt-5">
  <div class="form-floating">
    <textarea class="form-control" id="receipt_footer" name="receipt_footer" style="height: 120px"
      placeholder="Footer to print on receipts (optional)">{{ old('receipt_footer',$setting->receipt_footer) }}</textarea>
    <label for="receipt_footer">Receipt Footer (optional)</label>
  </div>
</div>


      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Settings</button>
      </div>
    </form>
  </div>
</div>
@endsection
