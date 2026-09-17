@extends('layouts.app')
@section('title', 'Users')
@section('breadcrumb')
    <li class="breadcrumb-item active">Users</li>
@endsection
@section('content')
<x-page-header title="Users" subtitle="Who can sign in, and what they can do">
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add User</a>
</x-page-header>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive d-desktop-table">
            <table class="table table-bordered table-striped table-hover mb-0">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="fw-semibold">{{ $user->name }} @if($user->id === auth()->id())<span class="text-muted small">(you)</span>@endif</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge {{ $user->isAdmin() ? 'bg-primary' : 'bg-secondary' }}">
                                {{ $user->isAdmin() ? 'Admin' : 'Staff' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $user->last_login_at?->format('d M Y, h:i A') ?? 'Never' }}</td>
                        <td>
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary btn-icon" aria-label="Edit {{ $user->name }}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="d-mobile-cards p-3">
            @foreach($users as $user)
            <div class="card-list-item">
                <div class="cl-row mb-2">
                    <strong>{{ $user->name }}</strong>
                    <span class="badge {{ $user->isAdmin() ? 'bg-primary' : 'bg-secondary' }}">{{ $user->isAdmin() ? 'Admin' : 'Staff' }}</span>
                </div>
                <div class="cl-row"><span class="cl-label">Email</span><span class="cl-value">{{ $user->email }}</span></div>
                <div class="cl-row"><span class="cl-label">Status</span><span class="cl-value"><span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></span></div>
                <div class="cl-row"><span class="cl-label">Last Login</span><span class="cl-value">{{ $user->last_login_at?->format('d M Y, h:i A') ?? 'Never' }}</span></div>
                <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary w-100 mt-3"><i class="bi bi-pencil me-1"></i> Edit</a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
