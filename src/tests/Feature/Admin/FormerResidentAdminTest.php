<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Property;
use App\Models\Resident;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FormerResidentAdminTest extends TestCase
{
    use RefreshDatabase;

    private function actingSuperAdmin(): AdminUser
    {
        $role = Role::query()->create([
            'name' => 'super_admin',
            'display_name' => 'スーパー管理者',
            'description' => null,
        ]);

        return AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => 'Test Admin',
            'email' => 'admin@test.local',
            'password_hash' => Hash::make('secret'),
            'line_uid' => null,
            'is_active' => true,
        ]);
    }

    public function test_guest_redirected_from_former_residents_index(): void
    {
        $response = $this->get('/admin/former-residents');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_moved_out_residents_only(): void
    {
        $admin = $this->actingSuperAdmin();
        $p = Property::query()->create([
            'name' => '物件A',
            'address' => 'a',
            'region' => '東京都',
            'room_count' => 10,
            'is_active' => true,
        ]);
        Resident::query()->create([
            'property_id' => $p->id,
            'line_uid' => 'Uactive',
            'name' => '入居中',
            'age' => null,
            'room_number' => '101',
            'phone' => '090-0000-0001',
            'registered_at' => now(),
            'is_active' => true,
        ]);
        $movedOut = Resident::query()->create([
            'property_id' => $p->id,
            'line_uid' => 'Umoved',
            'name' => '退去済み',
            'age' => null,
            'room_number' => '102',
            'phone' => '090-0000-0002',
            'registered_at' => now()->subYear(),
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/former-residents');

        $response->assertOk();
        $response->assertSee('退去済み');
        $response->assertDontSee('入居中');
        $response->assertSee(route('admin.former-residents.show', $movedOut), false);
    }

    public function test_show_returns_404_for_active_resident(): void
    {
        $admin = $this->actingSuperAdmin();
        $p = Property::query()->create([
            'name' => '物件',
            'address' => 'x',
            'region' => '東京都',
            'room_count' => 1,
            'is_active' => true,
        ]);
        $r = Resident::query()->create([
            'property_id' => $p->id,
            'line_uid' => 'U1',
            'name' => '現役',
            'age' => null,
            'room_number' => '1',
            'phone' => '090-1111-1111',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/former-residents/'.$r->id);

        $response->assertNotFound();
    }

    public function test_admin_can_view_moved_out_resident_detail(): void
    {
        $admin = $this->actingSuperAdmin();
        $p = Property::query()->create([
            'name' => '物件B',
            'address' => 'b',
            'region' => '大阪府',
            'room_count' => 5,
            'is_active' => true,
        ]);
        $r = Resident::query()->create([
            'property_id' => $p->id,
            'line_uid' => 'Ugone',
            'name' => '山田太郎',
            'age' => 40,
            'room_number' => '305',
            'phone' => '090-3333-3333',
            'registered_at' => now()->subMonths(6),
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/former-residents/'.$r->id);

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('退去済');
        $response->assertSee('物件B');
        $response->assertSee('305');
    }
}
