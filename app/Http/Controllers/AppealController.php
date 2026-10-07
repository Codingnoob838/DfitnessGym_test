<?php

namespace App\Http\Controllers;

use App\Models\Appeal;
use App\Models\User;
use App\Services\SemaphoreSms;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppealController extends Controller
{
    public function create(): View
    {
        return view('appeals.create');
    }

    public function store(Request $request, SemaphoreSms $sms): RedirectResponse
    {
        $request->session()->forget(['password_reset_phone', 'password_reset_verified_appeal_id']);
        $data = $request->validate([
            'phone' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
        ]);
        if (! $sms->configured()) {
            return back()->withErrors(['phone' => 'Password reset SMS is not configured yet. Please contact the gym.'])->onlyInput('phone');
        }

        $phone = PhoneNumber::normalize($data['phone']);
        $sendLimitKey = 'password-reset-send:'.sha1($phone);
        if (RateLimiter::tooManyAttempts($sendLimitKey, 3)) {
            return back()->withErrors(['phone' => 'Too many reset requests. Try again in one hour.'])->onlyInput('phone');
        }
        $cooldownKey = 'password-reset-cooldown:'.sha1($phone);
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return back()->withErrors(['phone' => 'Please wait one minute before requesting another code.'])->onlyInput('phone');
        }
        RateLimiter::hit($sendLimitKey, 3600);
        RateLimiter::hit($cooldownKey, 60);

        $members = User::where('phone', $phone)->where('role', 'member')
            ->where('approval_status', 'approved')->limit(2)->get();
        if ($members->count() === 1) {
            $member = $members->first();
            $code = sprintf('%05d', random_int(0, 99999));
            if (! $sms->sendPasswordResetCode($phone, $code)) {
                return back()->withErrors(['phone' => 'The reset SMS could not be sent. Please try again later.'])->onlyInput('phone');
            }
            DB::transaction(function () use ($member, $code) {
                Appeal::where('user_id', $member->id)->whereIn('status', ['pending', 'approved', 'issued'])
                    ->update(['status' => 'superseded', 'reset_code_hash' => null]);
                Appeal::create([
                    'user_id' => $member->id,
                    'username' => $member->username,
                    'message' => 'Password reset code sent by SMS.',
                    'status' => 'issued',
                    'reset_code_hash' => Hash::make($code),
                    'reset_expires_at' => now()->addMinutes(15),
                ]);
            });
        }

        $request->session()->put('password_reset_phone', $phone);
        return redirect()->route('password.code.form')
            ->with('status', 'If this number belongs to an approved member, a five-digit reset code has been sent by SMS. Enter it within 15 minutes.');
    }

    public function codeForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_phone')) {
            return redirect()->route('appeals.create');
        }

        return view('appeals.verify');
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'regex:/^\d{5}$/'],
        ]);
        $phone = $request->session()->get('password_reset_phone');
        if (! $phone) return redirect()->route('appeals.create');

        $limitKey = 'password-reset:'.sha1($phone);
        if (RateLimiter::tooManyAttempts($limitKey, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Try again in 15 minutes.']);
        }
        RateLimiter::hit($limitKey, 900);
        $appeal = Appeal::with('user')->whereHas('user', fn ($query) => $query->where('phone', $phone)->where('role', 'member')->where('approval_status', 'approved'))
            ->whereIn('status', ['approved', 'issued'])->whereNull('reset_used_at')
            ->where('reset_expires_at', '>', now())->latest()->get()
            ->first(fn (Appeal $appeal) => $appeal->reset_code_hash && Hash::check($data['code'], $appeal->reset_code_hash));
        if (! $appeal || ! $appeal->user || $appeal->user->role !== 'member' || $appeal->user->trashed()) {
            return back()->withErrors(['code' => 'The reset code is invalid or expired.']);
        }

        $request->session()->put('password_reset_verified_appeal_id', $appeal->id);
        RateLimiter::clear($limitKey);
        return redirect()->route('password.reset.form');
    }

    public function resetForm(Request $request): View|RedirectResponse
    {
        if (! $this->verifiedAppeal($request)) {
            return redirect()->route('password.code.form');
        }

        return view('appeals.reset');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $appeal = $this->verifiedAppeal($request);
        if (! $appeal) return redirect()->route('password.code.form');

        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        DB::transaction(function () use ($appeal, $data) {
            $locked = Appeal::whereKey($appeal->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['approved', 'issued'], true) && ! $locked->reset_used_at && $locked->reset_expires_at->isFuture(), 422);
            $locked->user->update(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)]);
            $locked->update(['status' => 'completed', 'reset_used_at' => now(), 'reset_code_hash' => null]);
        });
        $request->session()->forget(['password_reset_phone', 'password_reset_verified_appeal_id']);
        return redirect()->route('login')->with('status', 'Password changed. You can now sign in.');
    }

    private function verifiedAppeal(Request $request): ?Appeal
    {
        $phone = $request->session()->get('password_reset_phone');
        $appealId = $request->session()->get('password_reset_verified_appeal_id');
        if (! $phone || ! $appealId) return null;

        return Appeal::with('user')->whereKey($appealId)
            ->whereIn('status', ['approved', 'issued'])->whereNull('reset_used_at')
            ->where('reset_expires_at', '>', now())
            ->whereHas('user', fn ($query) => $query->where('phone', $phone)->where('role', 'member')->where('approval_status', 'approved')->whereNull('deleted_at'))
            ->first();
    }
}
