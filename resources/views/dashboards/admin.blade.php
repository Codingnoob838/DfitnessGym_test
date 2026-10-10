@extends('layouts.app')

@section('title', 'Admin dashboard')

@section('content')
    <section class="dashboard admin-dashboard">
        <div class="admin-dashboard-top">
            <div>
                <p class="kicker">ADMIN WORKSPACE</p>
                <h1>Gym dashboard</h1>
                <p>Welcome, {{ auth()->user()->name }}. Manage your gym from one place.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="notice success" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="admin-dashboard-layout">
            <div class="admin-dashboard-left">
                <article class="admin-overview-card">
                    <span>REGISTERED MEMBERS</span>
                    <strong>{{ $memberCount }}</strong>
                    <p>Members with access to the gym system</p>
                </article>

                <nav class="admin-shortcuts" aria-label="Gym management">
                    <a href="{{ route('admin.memberships.index') }}">
                        <i class="fi fi-br-dumbbell-fitness shortcut-icon" aria-hidden="true"></i>
                        <strong>Memberships</strong>
                        <small>Plans and reminders</small>
                    </a>
                    <a href="{{ route('admin.attendance.index') }}">
                        <i class="fi fi-br-calendar shortcut-icon" aria-hidden="true"></i>
                        <strong>Attendance</strong>
                        <small>Attendance history</small>
                    </a>
                    <a href="{{ route('admin.payments.index') }}">
                        <i class="fi fi-br-wallet shortcut-icon" aria-hidden="true"></i>
                        <strong>Payments</strong>
                        <small>Collected totals and history</small>
                    </a>
                    <a href="{{ route('admin.members.index') }}">
                        <i class="fi fi-br-users-gear shortcut-icon" aria-hidden="true"></i>
                        <strong>Member accounts</strong>
                        <small>Review and manage</small>
                    </a>
                </nav>
            </div>

            <div class="admin-dashboard-right">
                <section class="admin-walkin-card" aria-labelledby="walkin-heading">
                    <h2 id="walkin-heading">Record a walk-in</h2>
                    <p class="card-help">Walk-in rate: ₱120.00. Payment is recorded automatically.</p>
                    <form method="POST" action="{{ route('admin.attendance.walk-in') }}">
                        @csrf
                        <label for="walk-in-name">Visitor name</label>
                        <input id="walk-in-name" name="walk_in_name" required maxlength="255"
                            value="{{ old('walk_in_name') }}" placeholder="Full name">
                        <button class="primary-button" type="submit">Record walk-in and payment</button>
                    </form>
                </section>

                <section class="admin-scanner-card" aria-label="Member QR scanner">
                    <div class="admin-scanner-heading">
                        <div>
                            <p class="kicker">SCAN QR CODE</p>
                            <p class="admin-scanner-help">Scan a member's QR code to record check-in or check-out.</p>
                        </div>
                        <p><strong id="today-count">{{ $todayCount }}</strong> check-ins today</p>
                    </div>
                    <a class="primary-button admin-scanner-launch" href="{{ route('admin.attendance.scanner') }}">
                        Open QR code scanner
                    </a>
                </section>
            </div>
        </div>

        <section class="admin-registration-panel" aria-labelledby="pending-heading">
            <div class="admin-panel-heading">
                <div>
                    <p class="kicker">MEMBER REGISTRATION</p>
                    <h2 id="pending-heading">Pending registrations</h2>
                </div>
                <span class="status-pill upcoming">{{ $pendingCount }} pending</span>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Username</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingMembers as $member)
                            <tr>
                                <td><strong>{{ $member->name }}</strong></td>
                                <td>{{ '@' . $member->username }}</td>
                                <td>{{ $member->created_at->format('M j, Y') }}</td>
                                <td>
                                    <div class="registration-actions">
                                        <form method="POST" action="{{ route('admin.members.approve', $member) }}">
                                            @csrf
                                            <button class="approve-button" type="submit">Approve</button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.members.reject', $member) }}"
                                            onsubmit="return confirm('Reject this registration?')"
                                        >
                                            @csrf
                                            <button class="reject-button" type="submit">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">No registrations are awaiting approval.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
