@extends('layouts.app')
@section('title', '419 - Page Expired')
@section('content')
<div class="text-center py-5">
    <i class="bi bi-hourglass-split" style="font-size: 4rem; color: #A93330;"></i>
    <h2 class="mt-3">This page has expired</h2>
    <p class="text-muted">The form sat open for too long. Nothing was saved — go back, and if you were signed out, sign in and enter it again.</p>
    <a href="{{ url()->previous() }}" class="btn btn-primary mt-3">
        <i class="bi bi-arrow-left"></i> Go Back
    </a>
</div>
@endsection
