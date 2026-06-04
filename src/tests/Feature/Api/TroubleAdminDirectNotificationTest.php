<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AdminUser;
use App\Models\Property;
use App\Models\Resident;
use App\Models\Role;
use App\Models\TroubleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TroubleAdminDirectNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.line.me/oauth2/v2.1/verify' => Http::response([
                'iss' => 'https://access.line.me',
                'sub' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
                'aud' => 'test-liff-channel',
                'exp' => time() + 3600,
                'iat' => time(),
                'name' => 'Test User',
            ], 200),
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);
    }

    public function test_pushes_new_trouble_to_all_linked_admin_users(): void
    {
        $role = Role::query()->create([
            'name' => 'staff',
            'display_name' => '担当者',
            'description' => null,
        ]);

        $admin1 = AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => '担当A',
            'email' => 'a@example.com',
            'password_hash' => 'hash',
            'line_uid' => 'Uadmin1admin1admin1admin1admin1',
            'is_active' => true,
        ]);

        $admin2 = AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => '担当B',
            'email' => 'b@example.com',
            'password_hash' => 'hash',
            'line_uid' => 'Uadmin2admin2admin2admin2admin2',
            'is_active' => true,
        ]);

        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => 10,
            'is_active' => true,
        ]);

        $category = TroubleCategory::query()->create([
            'name' => 'plumbing',
            'display_name' => '水回り',
            'show_phone_number' => false,
            'emergency_phone' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '山田',
            'age' => null,
            'room_number' => '101',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/trouble-requests', [
            'category_id' => $category->id,
            'description' => 'エアコンが動かない',
            'preferred_date' => now()->format('Y-m-d'),
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertCreated();

        $adminPushes = 0;
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($admin1, $admin2, &$adminPushes): bool {
            if ($request->url() !== 'https://api.line.me/v2/bot/message/push') {
                return false;
            }
            $data = $request->data();
            $to = $data['to'] ?? null;
            if ($to !== $admin1->line_uid && $to !== $admin2->line_uid) {
                return false;
            }
            $text = (string) ($data['messages'][0]['text'] ?? '');
            if (! str_contains($text, '新規トラブル依頼')) {
                return false;
            }
            $adminPushes++;

            return true;
        });

        $this->assertSame(2, $adminPushes);

        $this->assertDatabaseHas('notification_logs', [
            'recipient_type' => 'admin',
            'recipient_id' => $admin1->id,
            'event_type' => 'trouble_new_request',
            'status' => 'success',
        ]);
    }

    public function test_skips_admin_notification_when_no_linked_admins(): void
    {
        $role = Role::query()->create([
            'name' => 'staff',
            'display_name' => '担当者',
            'description' => null,
        ]);

        AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => '担当',
            'email' => 'c@example.com',
            'password_hash' => 'hash',
            'line_uid' => null,
            'is_active' => true,
        ]);

        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => 10,
            'is_active' => true,
        ]);

        $category = TroubleCategory::query()->create([
            'name' => 'plumbing',
            'display_name' => '水回り',
            'show_phone_number' => false,
            'emergency_phone' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '山田',
            'room_number' => '101',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $this->postJson('/api/trouble-requests', [
            'category_id' => $category->id,
            'description' => 'テスト',
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ])->assertCreated();

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return $request->url() === 'https://api.line.me/v2/bot/message/push'
                && ($request->data()['to'] ?? null) === 'Udeadbeefdeadbeefdeadbeefdeadbeef';
        });

        $this->assertDatabaseHas('notification_logs', [
            'recipient_type' => 'admin',
            'recipient_id' => 0,
            'event_type' => 'trouble_new_request',
            'status' => 'skipped',
        ]);
    }
}
