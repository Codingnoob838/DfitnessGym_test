@extends('layouts.app')

@section('title', 'Member dashboard')

@section('content')
    <section class="dashboard">
        <div class="dashboard-head">
            <div>
                <p class="kicker">MEMBER AREA</p>
                <h1>Welcome, {{ auth()->user()->name }}.</h1>
                <p>Your D'fitness Gym account is ready.</p>
            </div>
        </div>

        <h2>Membership status</h2>

        <div class="membership-summary">
            @if ($currentMembership)
                <span class="status-pill {{ strtolower($currentMembership->status) }}">
                    {{ $currentMembership->status }}
                </span>
                <h3>{{ $currentMembership->plan->name }}</h3>
                <p>Expires on {{ $currentMembership->expires_at->format('F j, Y') }}.</p>
                <strong class="membership-days-left">
                    {{ $currentMembership->days_remaining === 0 ? 'Expires today' : $currentMembership->days_remaining . ' day' . ($currentMembership->days_remaining === 1 ? '' : 's') . ' left' }}
                </strong>
            @elseif ($latestMembership)
                <span class="status-pill {{ strtolower($latestMembership->status) }}">
                    {{ $latestMembership->status }}
                </span>
                <h3>{{ $latestMembership->plan->name }}</h3>
                <p>
                    {{ $latestMembership->status === 'Expired' ? 'Expired on' : 'Starts on' }}
                    {{ $latestMembership->status === 'Expired' ? $latestMembership->expires_at->format('F j, Y') : $latestMembership->starts_at->format('F j, Y') }}.
                </p>
            @else
                <span class="status-pill expired">No membership</span>
                <h3>Get started</h3>
                <p>Ask the gym admin to assign a membership plan to your account.</p>
            @endif
        </div>

        <h2>Your account QR code</h2>

        <div class="membership-summary member-qr-card">
            <div class="member-qr-image">
                <img
                    src="{{ route('member.qr') }}"
                    alt="Your member QR code for gym check-in and check-out"
                    width="260"
                    height="260"
                    loading="lazy"
                >
            </div>

            <div class="member-qr-details">
                <p class="kicker">GYM MEMBER ID</p>
                <h3>{{ auth()->user()->name }}</h3>
                <p class="member-qr-username">{{ '@' . auth()->user()->username }}</p>
                <p class="member-qr-instructions">
                    Show this QR code to the gym admin when you arrive and when you leave. Each scan records your check-in or check-out.
                </p>

                <div class="member-qr-meta">
                    <span>Membership</span>
                    <strong>{{ $currentMembership?->plan->name ?? ($latestMembership?->plan->name ?? 'Not assigned') }}</strong>
                </div>
                <div class="member-qr-meta">
                    <span>Status</span>
                    <strong>{{ $currentMembership?->status ?? ($latestMembership?->status ?? 'No membership') }}</strong>
                </div>

                @if ($currentMembership)
                    <div class="member-qr-meta">
                        <span>Expires</span>
                        <strong>
                            {{ $currentMembership->expires_at->format('F j, Y') }} ·
                            {{ $currentMembership->days_remaining === 0 ? 'today' : $currentMembership->days_remaining . ' day' . ($currentMembership->days_remaining === 1 ? '' : 's') . ' left' }}
                        </strong>
                    </div>
                @endif

                <a class="secondary-button member-id-download" href="{{ route('member.id.download') }}" download="dfitness-member-id.png">
                    Download member ID
                </a>
            </div>
        </div>

        <div class="member-history-switch" aria-label="Member history">
            <button type="button" class="member-history-button is-active" data-history-target="member-attendance-history" aria-controls="member-attendance-history" aria-pressed="true">Attendance history</button>
            <button type="button" class="member-history-button" data-history-target="member-payment-history" aria-controls="member-payment-history" aria-pressed="false">Payment history</button>
        </div>

        <section id="member-attendance-history" class="member-history-panel" aria-label="Attendance history">
        <h2>Attendance history</h2>
 
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Checked in</th>
                        <th>Checked out</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($checkIns as $checkIn)
                        <tr>
                            <td>{{ $checkIn->checked_in_at->format('F j, Y') }}</td>
                            <td>{{ $checkIn->checked_in_at->format('g:i A') }}</td>
                            <td>
                                {{ $checkIn->checked_out_at?->format('g:i A') ?? '—' }}
                                @if ($checkIn->auto_checked_out)
                                    <small>Automatic checkout</small>
                                @endif
                            </td>
                            <td>
                                @if ($checkIn->checked_out_at)
                                    @php
                                        $durationMinutes = max(0, (int) floor($checkIn->checked_in_at->diffInSeconds($checkIn->checked_out_at) / 60));
                                        $durationHours = intdiv($durationMinutes, 60);
                                        $remainingMinutes = $durationMinutes % 60;
                                    @endphp
                                    @if ($durationHours > 0)
                                        {{ $durationHours }} hr{{ $durationHours === 1 ? '' : 's' }}{{ $remainingMinutes > 0 ? ' ' . $remainingMinutes . ' min' : '' }}
                                    @else
                                        {{ $durationMinutes === 0 ? 'Under 1 min' : $durationMinutes . ' min' }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No attendance yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $checkIns->links() }}</div>
        </section>

        <section id="member-payment-history" class="member-history-panel" aria-label="Payment history">
        <h2>Payment history</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Recorded at</th>
                        <th>Membership</th>
                        <th>Amount</th>
                        <th>Subscription</th>
                        <th>Expires</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->created_at->format('F j, Y') }}</td>
                            <td>{{ $payment->membership?->plan?->name ?? '—' }}</td>
                            <td>₱{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->membership?->plan?->duration_days ? $payment->membership->plan->duration_days . ' days' : '—' }}</td>
                            <td>{{ $payment->membership?->expires_at?->format('F j, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No payments recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $payments->links() }}</div>
        </section>

        <h2>SMS renewal reminders</h2>

        @if (session('status'))
            <div class="notice success" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="management-card contact-card">
            <p class="card-help">
                Add a Philippine mobile number and choose whether to receive a reminder three days before your membership expires.
            </p>

            <form method="POST" action="{{ route('member.contact.update') }}">
                @csrf
                @method('PUT')

                <label for="contact-phone">Mobile number</label>
                <input
                    id="contact-phone"
                    type="tel"
                    name="phone"
                    value="{{ old('phone', auth()->user()->phone) }}"
                    placeholder="09XXXXXXXXX"
                >

                <label class="check-row">
                    <input type="checkbox" name="sms_consent" value="1" @checked(old('sms_consent', auth()->user()->sms_consent))>
                    <span>I agree to receive membership renewal reminders by SMS.</span>
                </label>

                <button class="primary-button" type="submit">Save preferences</button>
            </form>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('js/member-history.js') }}?v=1"></script>
@endsection
