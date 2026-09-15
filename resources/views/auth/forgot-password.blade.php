@extends('layouts.guest')
@section('title', 'Forgot Password')
@section('content')
    <p class="text-muted small mb-3">Enter your email address and we'll send you a password reset link.</p>
    @if(session('status'))
        <div class="alert alert-success small">{{ session('status') }}</div>
    @endif
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">
            Send Reset Link
        </button>
        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="small text-muted">Back to Login</a>
        </div>
    </form>
@endsection
