@extends('layouts.app')
@section('title', 'Edit Godown')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('godowns.index') }}">Godowns</a></li>
    <li class="breadcrumb-item active">Edit Godown</li>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Edit Godown: {{ $godown->code }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('godowns.update', $godown) }}">
                    @csrf @method('PUT')
                    <x-form-errors />
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" class="form-control" value="{{ $godown->code }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $godown->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2">{{ old('address', $godown->address) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $godown->contact_phone) }}">
                    </div>
                    @if(auth()->user()->isAdmin())
                    <div class="mb-3 form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input @error('is_active') is-invalid @enderror" id="is_active" name="is_active" value="1" {{ old('is_active', $godown->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                        @error('is_active') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-hint">Untick to close this godown. Only an admin can change this, and only once it holds no stock.</div>
                    </div>
                    @elseif(! $godown->is_active)
                    <div class="mb-3 text-muted small"><i class="bi bi-info-circle me-1"></i>This godown is inactive. Ask an admin to reactivate it.</div>
                    @endif
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Godown</button>
                        <a href="{{ route('godowns.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
