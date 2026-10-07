@extends('layouts.app')

@section('title', 'Member accounts')

@section('content')
    <section class="dashboard">
        <div class="dashboard-head">
            <div>
                <p class="kicker">ADMIN WORKSPACE</p>
                <h1>Member accounts.</h1>
                <p>View approved member details and membership status.</p>
            </div>
        </div>

        @if (session('status'))
            <div class="notice success" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Account status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        @php($accountStatus = $member->membershipAccountStatus())
                        <tr>
                            <td>
                                <strong>{{ $member->name }}</strong>
                                <small>{{ '@' . $member->username }}</small>
                            </td>
                            <td>
                                <span class="status-pill {{ strtolower($accountStatus['label']) }}">
                                    {{ $accountStatus['label'] }}
                                </span>
                                <small>{{ $accountStatus['detail'] }}</small>
                            </td>
                            <td>{{ $member->created_at->format('M j, Y') }}</td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('admin.members.show', $member) }}">View</a>
                                    @if ($member->trashed())
                                        <form method="POST" action="{{ route('admin.members.restore', $member->id) }}">
                                            @csrf
                                            <button class="text-button" type="submit">Restore</button>
                                        </form>
                                    @else
                                        <form
                                            method="POST"
                                            action="{{ route('admin.members.destroy', $member) }}"
                                            onsubmit="return confirm('Remove this member account? Its records will be kept and the account can be restored.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-button" type="submit">Remove</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No approved member accounts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $members->links() }}</div>

    </section>
@endsection
