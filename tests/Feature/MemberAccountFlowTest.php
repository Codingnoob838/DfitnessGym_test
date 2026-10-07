<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_views_removes_and_restores_member_without_losing_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => 'approved']);
        $member = User::factory()->create(['role' => 'member', 'approval_status' => 'approved']);
        $plan = MembershipPlan::where('name', 'Student')->firstOrFail();
        $membership = Membership::create(['user_id' => $member->id, 'membership_plan_id' => $plan->id, 'starts_at' => today(), 'expires_at' => today()->addDays(29)]);
        Payment::create(['user_id' => $member->id, 'membership_id' => $membership->id, 'amount' => '900.00', 'status' => 'paid', 'paid_at' => today(), 'method' => 'automatic']);
        Attendance::create(['user_id' => $member->id, 'method' => 'qr', 'check_in_date' => today(), 'checked_in_at' => now()]);

        $this->actingAs($admin)->get('/admin/members')
            ->assertOk()->assertSee('View')->assertDontSee('Edit')->assertDontSee('Rejected');
        $this->get('/admin/members/'.$member->id)
            ->assertOk()->assertSee($member->name)->assertSee($member->username)
            ->assertSee($plan->name)->assertSee('Active')->assertSee('30 days');
        $this->put('/admin/members/'.$member->id, ['name' => 'Updated Member'])->assertStatus(405);
        $this->delete('/admin/members/'.$member->id)->assertRedirect('/admin/members');
        $this->assertSoftDeleted('users', ['id' => $member->id]);
        $this->assertSame(1, Membership::count());
        $this->assertSame(1, Payment::count());
        $this->assertSame(1, Attendance::count());
        $this->get('/admin/payments')->assertOk()->assertSee($member->name);
        $this->get('/admin/attendance')->assertOk()->assertSee($member->name);
        $this->get('/admin/members')->assertOk()->assertSee('Account removed')->assertSee('Restore');
        $this->get('/admin/members/'.$member->id)->assertOk()->assertSee('Account removed');
        $this->post('/logout');
        $this->post('/login', ['username' => $member->username, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->actingAs($admin)->post('/admin/members/'.$member->id.'/restore')->assertRedirect();
        $this->assertFalse($member->fresh()->trashed());
        $this->post('/logout');
        $this->post('/login', ['username' => $member->username, 'password' => 'password'])->assertRedirect('/member/dashboard');
    }

    public function test_member_cannot_manage_accounts_and_admin_account_is_protected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => 'approved']);
        $member = User::factory()->create(['role' => 'member', 'approval_status' => 'approved']);
        $this->actingAs($member)->get('/admin/members')->assertForbidden();
        $this->get('/admin/members/'.$member->id)->assertForbidden();
        $this->delete('/admin/members/'.$member->id)->assertForbidden();
        $this->actingAs($admin)->delete('/admin/members/'.$admin->id)->assertNotFound();
        $this->get('/admin/members/'.$admin->id)->assertNotFound();
    }

    public function test_expired_membership_shows_days_expired_on_member_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'approval_status' => 'approved']);
        $member = User::factory()->create(['role' => 'member', 'approval_status' => 'approved']);
        $plan = MembershipPlan::where('name', 'Regular')->firstOrFail();
        Membership::create(['user_id' => $member->id, 'membership_plan_id' => $plan->id,
            'starts_at' => today()->subDays(35), 'expires_at' => today()->subDays(6)]);

        $this->actingAs($admin)->get('/admin/members')->assertOk()->assertSee('Expired')->assertSee('6 days expired');
        $this->get('/admin/members/'.$member->id)->assertOk()->assertSee('6 days expired');
    }

    public function test_removed_member_cannot_be_restored_with_another_members_mobile_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $removed = User::factory()->create(['role' => 'member', 'phone' => '+639171234567']);
        $removed->delete();
        User::factory()->create(['role' => 'member', 'phone' => '+639171234567']);

        $this->actingAs($admin)->post('/admin/members/'.$removed->id.'/restore')->assertSessionHasErrors('phone');
        $this->assertTrue($removed->fresh()->trashed());
    }
}
