@extends('layouts.guest')
@section('title', 'Login')
@section('content')
    @if(session('status'))
        <div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i> {{ session('status') }}</div>
    @endif
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-icon-wrap">
                <i class="bi bi-envelope"></i>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Enter your email">
            </div>
            @error('email')
                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-icon-wrap">
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Enter your password">
            </div>
            @error('password')
                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember" style="min-height:auto;">
            <label class="form-check-label" for="remember" style="font-size:0.9rem;">Keep me signed in</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
        </button>
        @if(Route::has('password.request'))
            <div class="text-center mt-3">
                <a href="{{ route('password.request') }}" class="text-muted" style="font-size:0.82rem;">Forgot your password?</a>
            </div>
        @endif
    </form>
@endsection
