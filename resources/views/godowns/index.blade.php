@extends('layouts.app')
@section('title', 'Godowns')
@section('breadcrumb')
    <li class="breadcrumb-item active">Godowns</li>
@endsection
@section('content')
<x-page-header title="Godowns" subtitle="Warehouses stock can move between">
    <a href="{{ route('godowns.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Godown</a>
</x-page-header>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive d-desktop-table">
            <table class="table table-bordered table-striped table-hover mb-0">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Address</th><th>Phone</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($godowns as $godown)
                    <tr>
                        <td><code>{{ $godown->code }}</code></td>
                        <td>{{ $godown->name }}</td>
                        <td class="small">{{ $godown->address ?? '-' }}</td>
                        <td>{{ $godown->contact_phone ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $godown->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $godown->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('godowns.edit', $godown) }}" class="btn btn-outline-primary btn-icon" aria-label="Edit {{ $godown->code }}" title="Edit">
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
            @foreach($godowns as $godown)
            <div class="card-list-item">
                <div class="cl-row mb-2">
                    <strong><code>{{ $godown->code }}</code> {{ $godown->name }}</strong>
                    <span class="badge {{ $godown->is_active ? 'bg-success' : 'bg-danger' }}">{{ $godown->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="cl-row"><span class="cl-label">Address</span><span class="cl-value">{{ $godown->address ?? '-' }}</span></div>
                <div class="cl-row"><span class="cl-label">Phone</span><span class="cl-value">{{ $godown->contact_phone ?? '-' }}</span></div>
                <a href="{{ route('godowns.edit', $godown) }}" class="btn btn-outline-primary w-100 mt-3"><i class="bi bi-pencil me-1"></i> Edit</a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
