@extends('layouts.app')
@section('title', '403 - Forbidden')
@section('content')
<div class="text-center py-5">
    <i class="bi bi-shield-x" style="font-size: 4rem; color: #A93330;"></i>
    <h2 class="mt-3">403 - Access Denied</h2>
    <p class="text-muted">You do not have permission to access this page.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">
        <i class="bi bi-house"></i> Go to Dashboard
    </a>
</div>
@endsection
