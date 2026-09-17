@extends('layouts.app')
@section('title', 'Add User')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Add User</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Add New User</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.store') }}">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label required">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label required">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            <div class="form-hint">Min 8 characters, mixed case, at least one number.</div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label required">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label required">Role</label>
                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                            <option value="staff" {{ old('role', 'staff') === 'staff' ? 'selected' : '' }}>Staff — day-to-day data entry</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin — full access, including Users and the Activity Log</option>
                        </select>
                        <div class="form-hint">Staff can do everything except delete a product or view the Activity Log.</div>
                        @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="alert alert-info border-0 py-2" style="background: var(--info-soft);">
                        <i class="bi bi-info-circle me-1"></i>
                        The new user must change this password the first time they sign in.
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create User</button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
