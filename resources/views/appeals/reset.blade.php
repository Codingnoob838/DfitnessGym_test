@extends('layouts.app')

@section('title', 'Set new password')

@section('content')
    <section class="auth-layout auth-layout-single">
        <div class="form-panel">
            <div class="form-heading">
                <h2>New password</h2>
                <p>Enter and confirm your new password.</p>
            </div>

            @if ($errors->any())
                <div class="notice error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="notice success" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.reset.submit') }}">
                @csrf

                <label for="password">New password</label>
                <div class="password-field">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        data-password-target="password"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        Show
                    </button>
                </div>

                <label for="password_confirmation">Confirm new password</label>
                <div class="password-field">
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        data-password-target="password_confirmation"
                        aria-label="Show confirm password"
                        aria-pressed="false"
                    >
                        Show
                    </button>
                </div>

                <button class="primary-button" type="submit">Save new password</button>
            </form>

            <p class="fine-print">
                <a href="{{ route('login') }}">Back to sign in</a>
            </p>
        </div>
    </section>
@endsection
