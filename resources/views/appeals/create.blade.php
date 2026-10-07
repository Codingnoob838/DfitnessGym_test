@extends('layouts.app')

@section('title', 'Request password reset')

@section('content')
    <section class="auth-layout auth-layout-single">
        <div class="form-panel">
            <div class="form-heading">
                <h2>Request a reset code</h2>
                <p>Enter the mobile number used for your member account. We'll send a five-digit code by SMS.</p>
            </div>

            @if ($errors->any())
                <div class="notice error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('appeals.store') }}">
                @csrf

                <label for="phone">Registered mobile number</label>
                <input
                    id="phone"
                    name="phone"
                    type="tel"
                    value="{{ old('phone') }}"
                    required
                    inputmode="tel"
                    autocomplete="tel"
                    placeholder="09XXXXXXXXX"
                    autofocus
                >

                <button class="primary-button" type="submit">Request reset code</button>
            </form>

            <p class="fine-print">
                <a href="{{ route('login') }}">Back to sign in</a>
            </p>
        </div>
    </section>
@endsection
