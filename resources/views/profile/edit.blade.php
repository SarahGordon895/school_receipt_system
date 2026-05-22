@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-person me-2"></i>{{ __('Profile Information') }}
            </div>
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-shield-lock me-2"></i>{{ __('Update Password') }}
            </div>
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-danger-subtle">
            <div class="card-header fw-semibold text-danger">
                <i class="bi bi-exclamation-octagon me-2"></i>{{ __('Delete Account') }}
            </div>
            <div class="card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
