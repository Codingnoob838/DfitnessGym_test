<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberAccountController extends Controller
{
    private function requireAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }

    public function index(Request $request): View
    {
        $this->requireAdmin($request);
        return view('members.index', [
            'members' => User::withTrashed()->with('memberships.plan')
                ->where('role', 'member')->where('approval_status', 'approved')
                ->latest()->paginate(20),
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $this->requireAdmin($request);
        abort_unless($user->role === 'member' && $user->approval_status === 'approved', 404);
        $user->load('memberships.plan')->loadCount(['attendance', 'payments']);
        return view('members.show', ['member' => $user]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->requireAdmin($request);
        abort_unless($user->role === 'member', 404);
        $user->delete();
        return redirect()->route('admin.members.index')->with('status', 'Member account removed. Records are preserved.');
    }

    public function restore(Request $request, int $user): RedirectResponse
    {
        $this->requireAdmin($request);
        $member = User::onlyTrashed()->findOrFail($user);
        abort_unless($member->role === 'member', 404);
        if ($member->phone && User::where('phone', $member->phone)->exists()) {
            return back()->withErrors(['phone' => 'This mobile number is already used by another account. Update that account before restoring this member.']);
        }
        $member->restore();
        return redirect()->route('admin.members.index')->with('status', 'Member account restored.');
    }
}
