@extends('layouts.app')
@section('title', 'Change Password')
@section('breadcrumb')
    <li class="breadcrumb-item active">Change Password</li>
@endsection
@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Change Your Password</h5>
                </div>
                <div class="card-body">
                    @if(auth()->user()->must_change_password)
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> You must change your password before continuing.
                        </div>
                    @endif
                    <form method="POST" action="{{ url('/change-password') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Min 8 characters, mixed case, at least one number.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
