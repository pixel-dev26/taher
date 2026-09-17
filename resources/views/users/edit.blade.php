@extends('layouts.app')
@section('title', 'Edit User')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Edit User</li>
@endsection
@section('content')
@php $isSelf = $user->id === auth()->id(); @endphp
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Edit User: {{ $user->name }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label required">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label required">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            @if($isSelf)
                                <label class="form-label">Role</label>
                                <input type="hidden" name="role" value="admin">
                                <input type="text" class="form-control" value="Admin" disabled>
                                <div class="form-hint">You can't change your own role.</div>
                            @else
                                <label for="role" class="form-label required">Role</label>
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                    <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff — day-to-day data entry</option>
                                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin — full access, including Users and the Activity Log</option>
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-block">Status</label>
                            @if($isSelf)
                                <input type="hidden" name="is_active" value="1">
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" checked disabled>
                                    <label class="form-check-label">Active</label>
                                </div>
                                <div class="form-hint">You can't deactivate your own account.</div>
                            @else
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input @error('is_active') is-invalid @enderror" id="is_active" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                                @error('is_active') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @endif
                        </div>
                    </div>

                    <hr class="section-divider">
                    <h6 class="fw-bold mb-3">Reset Password</h6>
                    <div class="form-hint mb-3">Leave these blank to keep the current password.</div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                            <div class="form-hint">Min 8 characters, mixed case, at least one number.</div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
