@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <section class="auth-layout auth-layout-single registration-layout">
        <div class="form-panel">
            <div class="form-heading">
                <h2>Create a member account</h2>
                <p>Complete the details below to request access, gym admin will review your registration before you can sign in.</p>
            </div>

            @if ($errors->any())
                <div class="notice error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form class="registration-form" method="POST" action="{{ route('register.submit') }}">
                @csrf

                <div class="registration-field">
                <label for="first_name">First name</label>
                <input
                    id="first_name"
                    name="first_name"
                    type="text"
                    value="{{ old('first_name') }}"
                    required
                    maxlength="80"
                    autocomplete="given-name"
                    placeholder="Your first name"
                >
                </div>

                <div class="registration-field">
                <label for="middle_name">Middle name (optional)</label>
                <input
                    id="middle_name"
                    name="middle_name"
                    type="text"
                    value="{{ old('middle_name') }}"
                    maxlength="80"
                    autocomplete="additional-name"
                    placeholder="Your middle name"
                >
                </div>

                <div class="registration-field">
                <label for="last_name">Last name</label>
                <input
                    id="last_name"
                    name="last_name"
                    type="text"
                    value="{{ old('last_name') }}"
                    required
                    maxlength="80"
                    autocomplete="family-name"
                    placeholder="Your last name"
                >
                </div>

                <div class="registration-field">
                <label for="username">Username</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    minlength="3"
                    maxlength="50"
                    autocomplete="username"
                    placeholder="Choose a unique username"
                >
                </div>

                <div class="registration-field">
                <label for="phone">Mobile number</label>
                <input
                    id="phone"
                    type="tel"
                    name="phone"
                    value="{{ old('phone') }}"
                    required
                    inputmode="tel"
                    placeholder="09XXXXXXXXX"
                    autocomplete="tel"
                >
                </div>

                <label class="check-row registration-wide">
                    <input type="checkbox" name="sms_consent" value="1" @checked(old('sms_consent'))>
                    <span>I agree to receive membership renewal reminders by SMS.</span>
                </label>

                <div class="registration-field">
                <label for="password">Password</label>
                <div class="password-field">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
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
                </div>

                <div class="registration-field">
                <label for="password_confirmation">Confirm password</label>
                <div class="password-field">
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repeat your password"
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
                </div>

                <button class="primary-button registration-wide" type="submit">Submit registration</button>
            </form>

            <p class="fine-print">
                Already have an account?
                <a href="{{ route('login') }}">Sign in</a>
            </p>
        </div>
    </section>
@endsection
