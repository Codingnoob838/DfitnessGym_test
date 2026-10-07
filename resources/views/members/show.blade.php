@extends('layouts.app')

@section('title', 'View member')

@section('content')
    @php($accountStatus = $member->membershipAccountStatus())
    <section class="dashboard">
        <div class="dashboard-head">
            <div>
                <h1>{{ $member->name }}</h1>
                <p>Member account information</p>
            </div>
            <a class="secondary-button" href="{{ route('admin.members.index') }}">Back to member accounts</a>
        </div>

        <section class="management-card member-detail-card" aria-labelledby="member-details-heading">
            <h2 id="member-details-heading">Account details</h2>
            <dl class="member-detail-grid">
                <div><dt>Full name</dt><dd>{{ $member->name }}</dd></div>
                <div><dt>Username</dt><dd>{{ '@' . $member->username }}</dd></div>
                <div><dt>Mobile number</dt><dd>{{ $member->phone ?? 'Not provided' }}</dd></div>
                <div><dt>Registered</dt><dd>{{ $member->created_at->format('F j, Y') }}</dd></div>
                <div><dt>SMS reminders</dt><dd>{{ $member->sms_consent ? 'Enabled' : 'Disabled' }}</dd></div>
                <div><dt>Account status</dt><dd><span class="status-pill {{ strtolower($accountStatus['label']) }}">{{ $accountStatus['label'] }}</span> <span class="member-detail-note">{{ $accountStatus['detail'] }}</span></dd></div>
            </dl>
        </section>

        <section class="management-card member-detail-card" aria-labelledby="member-membership-heading">
            <h2 id="member-membership-heading">Membership</h2>
            @if ($accountStatus['membership'])
                <dl class="member-detail-grid">
                    <div><dt>Plan</dt><dd>{{ $accountStatus['membership']->plan?->name ?? 'Unknown plan' }}</dd></div>
                    <div><dt>Subscription</dt><dd>{{ $accountStatus['membership']->plan?->duration_days ? $accountStatus['membership']->plan->duration_days . ' days' : '—' }}</dd></div>
                    <div><dt>Started</dt><dd>{{ $accountStatus['membership']->starts_at->format('F j, Y') }}</dd></div>
                    <div><dt>Expires</dt><dd>{{ $accountStatus['membership']->expires_at->format('F j, Y') }}</dd></div>
                </dl>
            @else
                <p>No membership has been assigned.</p>
            @endif
        </section>

        <section class="management-card member-detail-card" aria-labelledby="member-activity-heading">
            <h2 id="member-activity-heading">Recorded activity</h2>
            <dl class="member-detail-grid">
                <div><dt>Attendance visits</dt><dd>{{ $member->attendance_count }}</dd></div>
                <div><dt>Payments</dt><dd>{{ $member->payments_count }}</dd></div>
            </dl>
        </section>
    </section>
@endsection
