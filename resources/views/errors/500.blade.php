@extends('layouts.app')
@section('title', '500 - Something went wrong')
@section('content')
<div class="text-center py-5">
    <i class="bi bi-exclamation-octagon" style="font-size: 4rem; color: #A93330;"></i>
    <h2 class="mt-3">Something went wrong</h2>
    <p class="text-muted">The problem has been recorded. Please go back and try again; if it keeps happening, contact your administrator.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3">
        <i class="bi bi-house"></i> Go to Dashboard
    </a>
</div>
@endsection
