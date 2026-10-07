<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function membershipAccountStatus(): array
    {
        $memberships = ($this->relationLoaded('memberships') ? $this->memberships : $this->memberships()->get())
            ->sortByDesc(fn (Membership $membership) => $membership->expires_at->timestamp);
        $current = $memberships->first(fn (Membership $membership) =>
            $membership->starts_at->lessThanOrEqualTo(today()) && $membership->expires_at->greaterThanOrEqualTo(today()));
        $latestExpired = $memberships->first(fn (Membership $membership) => $membership->expires_at->isBefore(today()));

        if ($this->trashed()) {
            return ['label' => 'Expired', 'detail' => 'Account removed', 'membership' => $current ?? $latestExpired];
        }
        if ($current) {
            return ['label' => 'Active', 'detail' => $current->plan?->name ?? 'Membership active', 'membership' => $current];
        }
        if ($latestExpired) {
            $days = (int) $latestExpired->expires_at->diffInDays(today());
            return ['label' => 'Expired', 'detail' => $days.' day'.($days === 1 ? '' : 's').' expired', 'membership' => $latestExpired];
        }

        return ['label' => 'Expired', 'detail' => 'No membership yet', 'membership' => null];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'approval_status',
        'phone',
        'sms_consent',
        'sms_consented_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'sms_consent' => 'boolean',
            'sms_consented_at' => 'datetime',
        ];
    }
}
