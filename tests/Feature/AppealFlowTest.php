<?php

namespace Tests\Feature;

use App\Models\Appeal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppealFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_code_is_sent_by_sms_and_works_once_without_admin_review(): void
    {
        config()->set('services.semaphore', ['api_key' => 'test-api-key', 'sender_name' => 'DFitnessGym']);
        $member = User::factory()->create([
            'role' => 'member', 'approval_status' => 'approved',
            'username' => 'gymmember', 'phone' => '+639171234567',
        ]);
        $sentCode = null;
        Http::fake(function ($request) use (&$sentCode) {
            $this->assertSame('https://api.semaphore.co/api/v4/otp', $request->url());
            $this->assertSame('test-api-key', $request['apikey']);
            $this->assertSame('639171234567', $request['number']);
            $this->assertSame('DFitnessGym', $request['sendername']);
            $this->assertStringContainsString('{otp}', $request['message']);
            $sentCode = $request['code'];
            return Http::response([['message_id' => 12345, 'code' => (int) $sentCode, 'status' => 'Pending']], 200);
        });

        $this->get('/appeal')->assertOk();
        $this->post('/appeal', ['phone' => '09171234567'])->assertRedirect(route('password.code.form'));
        $this->get('/verify-reset-code')->assertOk()->assertSee('Enter reset code')->assertDontSee('name="password"', false);
        $this->get('/reset-password')->assertRedirect(route('password.code.form'));
        $this->assertMatchesRegularExpression('/^\d{5}$/', $sentCode);
        $reset = Appeal::firstOrFail();
        $this->assertSame($member->id, $reset->user_id);
        $this->assertSame('issued', $reset->status);
        $this->assertTrue(Hash::check($sentCode, $reset->reset_code_hash));
        $this->assertTrue($reset->reset_expires_at->isFuture());
        $data = ['password' => 'newpassword123', 'password_confirmation' => 'newpassword123'];
        $this->post('/reset-password', $data)->assertRedirect(route('password.code.form'));
        $this->post('/verify-reset-code', ['code' => '99999'])->assertSessionHasErrors('code');
        $this->post('/verify-reset-code', ['code' => $sentCode])->assertRedirect(route('password.reset.form'));
        $this->get('/reset-password')->assertOk()->assertSee('New password')->assertDontSee('name="code"', false);
        $this->post('/reset-password', $data)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('newpassword123', $member->fresh()->password));
        $this->assertSame('completed', $reset->fresh()->status);
        $this->get('/reset-password')->assertRedirect(route('password.code.form'));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/members')->assertOk()->assertDontSee('Password reset requests');
        $this->get('/admin/appeals')->assertNotFound();
    }

    public function test_unknown_number_does_not_send_sms_or_create_a_code(): void
    {
        config()->set('services.semaphore', ['api_key' => 'test-api-key', 'sender_name' => null]);
        Http::fake();
        $this->post('/appeal', ['phone' => '09179999999'])->assertRedirect(route('password.code.form'));
        $this->post('/verify-reset-code', ['code' => '12345'])->assertSessionHasErrors('code');
        $this->assertSame(0, Appeal::count());
        Http::assertNothingSent();
    }

    public function test_unconfigured_or_failed_sms_does_not_create_a_usable_code(): void
    {
        User::factory()->create([
            'role' => 'member', 'approval_status' => 'approved', 'phone' => '+639171234567',
        ]);
        $this->post('/appeal', ['phone' => '09171234567'])->assertSessionHasErrors('phone');
        $this->assertSame(0, Appeal::count());

        config()->set('services.semaphore', ['api_key' => 'test-api-key', 'sender_name' => null]);
        Http::fake(['api.semaphore.co/*' => Http::response(['message' => 'Rejected'], 400)]);
        $this->post('/appeal', ['phone' => '09171234567'])->assertSessionHasErrors('phone');
        $this->assertSame(0, Appeal::count());
    }

    public function test_new_sms_code_supersedes_the_previous_code_after_cooldown(): void
    {
        config()->set('services.semaphore', ['api_key' => 'test-api-key', 'sender_name' => null]);
        User::factory()->create([
            'role' => 'member', 'approval_status' => 'approved', 'phone' => '+639171234567',
        ]);
        Http::fake(function ($request) {
            return Http::response([['message_id' => 12345, 'code' => (int) $request['code'], 'status' => 'Pending']], 200);
        });

        $this->post('/appeal', ['phone' => '09171234567'])->assertRedirect(route('password.code.form'));
        $first = Appeal::firstOrFail();
        $this->post('/appeal', ['phone' => '09171234567'])->assertSessionHasErrors('phone');
        $this->assertSame(1, Appeal::count());

        $this->travel(61)->seconds();
        $this->post('/appeal', ['phone' => '09171234567'])->assertRedirect(route('password.code.form'));
        $this->assertSame('superseded', $first->fresh()->status);
        $this->assertNull($first->fresh()->reset_code_hash);
        $this->assertSame(1, Appeal::where('status', 'issued')->count());
    }

    public function test_expired_reset_code_cannot_change_password(): void
    {
        $member = User::factory()->create([
            'role' => 'member', 'approval_status' => 'approved',
            'username' => 'member2', 'phone' => '+639181234567',
        ]);
        $reset = Appeal::create([
            'user_id' => $member->id, 'username' => 'member2', 'message' => 'SMS password reset.',
            'status' => 'approved', 'reset_code_hash' => Hash::make('12345'),
            'reset_expires_at' => now()->subMinute(),
        ]);
        $oldPassword = $member->password;
        $this->withSession(['password_reset_phone' => '+639181234567'])
            ->post('/verify-reset-code', ['code' => '12345'])->assertSessionHasErrors('code');
        $this->get('/reset-password')->assertRedirect(route('password.code.form'));
        $this->assertSame($oldPassword, $member->fresh()->password);
        $this->assertSame('approved', $reset->fresh()->status);
    }

    public function test_verified_code_still_expires_before_password_is_changed(): void
    {
        $member = User::factory()->create([
            'role' => 'member', 'approval_status' => 'approved', 'phone' => '+639181234567',
        ]);
        $oldPassword = $member->password;
        $appeal = Appeal::create([
            'user_id' => $member->id, 'username' => $member->username,
            'message' => 'SMS password reset.', 'status' => 'issued',
            'reset_code_hash' => Hash::make('12345'), 'reset_expires_at' => now()->addMinute(),
        ]);

        $this->withSession(['password_reset_phone' => '+639181234567'])
            ->post('/verify-reset-code', ['code' => '12345'])->assertRedirect(route('password.reset.form'));
        $this->travel(2)->minutes();
        $this->post('/reset-password', [
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('password.code.form'));
        $this->assertSame($oldPassword, $member->fresh()->password);
        $this->assertSame('issued', $appeal->fresh()->status);
    }
}
