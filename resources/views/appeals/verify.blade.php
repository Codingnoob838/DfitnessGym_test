@extends('layouts.app')

@section('title', 'Verify reset code')

@section('content')
    <section class="auth-layout auth-layout-single">
        <div class="form-panel">
            <div class="form-heading">
                <h2>Enter reset code</h2>
                <p>Check your SMS for the five-digit code.</p>
            </div>

            @if ($errors->any())
                <div class="notice error" role="alert">{{ $errors->first() }}</div>
            @endif

            @if (session('status'))
                <div class="notice success" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.code.submit') }}">
                @csrf

                <label for="code">Five-digit reset code</label>
                <input
                    id="code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{5}"
                    required
                    minlength="5"
                    maxlength="5"
                    autocomplete="one-time-code"
                    autofocus
                >

                <button class="primary-button" type="submit">Verify code</button>
            </form>

            <p class="fine-print">
                Didn't receive a code? <a href="{{ route('appeals.create') }}">Request a new code</a>.
            </p>
            <p class="fine-print">
                <a href="{{ route('login') }}">Back to sign in</a>
            </p>
        </div>
    </section>
@endsection
