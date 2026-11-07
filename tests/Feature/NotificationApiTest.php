<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    }

    protected function seedNotification(User $user, array $data, ?string $readAt = null): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\\Notifications\\Test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode($data),
            'read_at' => $readAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_index_lists_only_the_current_user_notifications(): void
    {
        $other = User::factory()->create();

        $this->seedNotification($this->user, ['type' => 'overdue_work_order', 'title' => 'WO Overdue', 'message' => 'WO A']);
        $this->seedNotification($other, ['type' => 'overdue_work_order', 'title' => 'WO Overdue', 'message' => 'WO B']);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'WO Overdue')
            ->assertJsonPath('data.0.read', false)
            ->assertJsonPath('meta.unread_count', 1);
    }

    public function test_unread_filter_excludes_read_notifications(): void
    {
        $this->seedNotification($this->user, ['title' => 'Belum dibaca']);
        $this->seedNotification($this->user, ['title' => 'Sudah dibaca'], now()->toDateTimeString());

        $this->getJson('/api/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Belum dibaca');
    }

    public function test_unread_count_endpoint_returns_count(): void
    {
        $this->seedNotification($this->user, ['title' => 'A']);
        $this->seedNotification($this->user, ['title' => 'B']);
        $this->seedNotification($this->user, ['title' => 'C'], now()->toDateTimeString());

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 2);
    }

    public function test_mark_as_read_updates_only_own_notification(): void
    {
        $id = $this->seedNotification($this->user, ['title' => 'Mine']);

        $this->postJson("/api/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('data.read', true);

        $this->assertNotNull(DB::table('notifications')->where('id', $id)->value('read_at'));
    }

    public function test_mark_as_read_rejects_notification_of_another_user(): void
    {
        $other = User::factory()->create();
        $id = $this->seedNotification($other, ['title' => 'Not mine']);

        $this->postJson("/api/notifications/{$id}/read")->assertNotFound();

        $this->assertNull(DB::table('notifications')->where('id', $id)->value('read_at'));
    }

    public function test_mark_all_as_read_clears_unread_for_current_user_only(): void
    {
        $other = User::factory()->create();

        $this->seedNotification($this->user, ['title' => 'A']);
        $this->seedNotification($this->user, ['title' => 'B']);
        $otherId = $this->seedNotification($other, ['title' => 'C']);

        $this->postJson('/api/notifications/read-all')->assertOk();

        $this->getJson('/api/notifications/unread-count')->assertJsonPath('data.count', 0);
        $this->assertNull(DB::table('notifications')->where('id', $otherId)->value('read_at'));
    }

    public function test_notification_endpoints_require_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/notifications')
            ->assertUnauthorized();
    }
}
