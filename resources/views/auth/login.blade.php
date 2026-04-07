@extends('layouts.app')

@section('title', 'Admin Login')

@section('content')
    <div class="auth-shell">
        <section class="hero">
            <p class="eyebrow">Admin</p>
            <h1>Sign in</h1>
            <p>Use your internal admin account to manage laws, documents, structured nodes, and media.</p>
        </section>

        <section class="card auth-card">
            <form action="{{ route('login') }}" method="post" style="display: grid; gap: 1rem;">
                @csrf

                <label>
                    <div class="law-meta">Email</div>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                </label>

                <label>
                    <div class="law-meta">Password</div>
                    <input type="password" name="password" required>
                </label>

                <label>
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Keep me signed in
                </label>

                @if ($errors->any())
                    <div class="empty-state">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <button type="submit">Login</button>
            </form>
        </section>
    </div>
@endsection
