@extends('layouts.guest')
@section('title', 'Too many attempts')
@section('content')
<div class="text-center py-4">
    <i class="bi bi-hourglass-split" style="font-size: 3rem; color: #A93330;"></i>
    <h4 class="mt-3">Too many attempts</h4>
    <p class="text-muted">Please wait a minute and try again.</p>
    <a href="{{ route('login') }}" class="btn btn-primary mt-2">Back to sign in</a>
</div>
@endsection
