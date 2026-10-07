<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\SmsReminder;

class RenewalReminders
{
    public function queueDue(): int
    {
        $target = today()->addDays(3);
        $created = 0;
        $memberships = Membership::with(['user', 'plan'])
            ->whereBetween('expires_at', [today(), $target])->whereDate('starts_at', '<=', today())->get();
        foreach ($memberships as $membership) {
            $member = $membership->user;
            if (! $member || $member->trashed() || $member->approval_status !== 'approved'
                || ! $member->sms_consent || ! $member->phone) continue;
            $renewed = Membership::where('user_id', $member->id)
                ->whereDate('starts_at', '>', $membership->expires_at)->exists();
            if ($renewed) continue;

            $reminder = SmsReminder::firstOrCreate(
                ['membership_id' => $membership->id],
                [
                    'user_id' => $member->id,
                    'phone' => $member->phone,
                    'scheduled_for' => today(),
                    'status' => 'queued',
                    'message' => "D'fitness Gym: Your {$membership->plan->name} membership expires on {$membership->expires_at->format('M j, Y')}. Please visit the gym to renew.",
                ]
            );
            if ($reminder->wasRecentlyCreated) $created++;
        }
        return $created;
    }

    public function configured(): bool
    {
        return app(SemaphoreSms::class)->configured();
    }

    public function sendQueued(): array
    {
        if (! $this->configured()) return ['submitted' => 0, 'failed' => 0, 'configured' => false];
        $submitted = 0;
        $failed = 0;
        SmsReminder::with(['user', 'membership'])->where('status', 'queued')
            ->whereDate('scheduled_for', '<=', today())->orderBy('id')->chunkById(50, function ($reminders) use (&$submitted, &$failed) {
                foreach ($reminders as $reminder) {
                    $member = $reminder->user;
                    if (! $member || $member->trashed() || ! $member->sms_consent || ! $member->phone) {
                        $reminder->update(['status' => 'cancelled', 'error' => 'Member unavailable or SMS consent withdrawn.']);
                        continue;
                    }
                    $renewed = Membership::where('user_id', $member->id)
                        ->whereDate('starts_at', '>', $reminder->membership->expires_at)->exists();
                    if ($renewed || $reminder->membership->expires_at->isBefore(today())) {
                        $reminder->update(['status' => 'cancelled', 'error' => 'Membership renewed or already expired.']);
                        continue;
                    }
                    $messageId = app(SemaphoreSms::class)->sendMessage($member->phone, $reminder->message);
                    if ($messageId !== null) {
                        $reminder->update([
                            'phone' => $member->phone,
                            'status' => 'submitted',
                            'provider_sid' => $messageId,
                            'submitted_at' => now(),
                            'error' => null,
                        ]);
                        $submitted++;
                    } else {
                        $reminder->update(['status' => 'failed', 'error' => 'Semaphore did not accept the SMS request.']);
                        $failed++;
                    }
                }
            });
        return ['submitted' => $submitted, 'failed' => $failed, 'configured' => true];
    }
}
