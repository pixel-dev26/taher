@extends('layouts.app')
@section('title', '404 - Not Found')
@section('content')
<div class="text-center py-5">
    <i class="bi bi-question-circle" style="font-size: 4rem; color: #F39C12;"></i>
    <h2 class="mt-3">404 - Page Not Found</h2>
    <p class="text-muted">The page you're looking for doesn't exist.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">
        <i class="bi bi-house"></i> Go to Dashboard
    </a>
</div>
@endsection
