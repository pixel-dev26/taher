@extends('layouts.app')
@section('title', 'My Account')
@section('breadcrumb')
    <li class="breadcrumb-item active">My Account</li>
@endsection
@section('content')
<div class="page-header">
    <h4><i class="bi bi-person-circle me-1"></i> My Account</h4>
    <div class="page-subtitle">Update your name, email, or password</div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-bold">Profile</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('account.update') }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label required">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label required">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <hr class="section-divider">
                    <h6 class="fw-bold mb-3">Change Password</h6>
                    <div class="form-hint mb-3">Leave these blank to keep your current password.</div>

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password">
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-hint">Min 8 characters, mixed case, at least one number.</div>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
