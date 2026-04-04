<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Role;
use App\Models\TroubleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TroubleCategoryAdminTest extends TestCase
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

    public function test_guest_redirected_from_trouble_categories_index(): void
    {
        $response = $this->get('/admin/trouble-categories');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_store_trouble_category(): void
    {
        $admin = $this->actingSuperAdmin();

        $response = $this->actingAs($admin, 'admin')->post('/admin/trouble-categories', [
            'name' => 'test_category',
            'display_name' => 'テスト表示',
            'show_phone_number' => '1',
            'emergency_phone' => '03-0000-0000',
            'sort_order' => '5',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.trouble-categories.index'));
        $this->assertDatabaseHas('trouble_categories', [
            'name' => 'test_category',
            'display_name' => 'テスト表示',
            'show_phone_number' => true,
            'emergency_phone' => '03-0000-0000',
            'sort_order' => 5,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_trouble_category(): void
    {
        $admin = $this->actingSuperAdmin();

        $cat = TroubleCategory::query()->create([
            'name' => 'orig_name',
            'display_name' => '旧表示',
            'show_phone_number' => false,
            'emergency_phone' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->put('/admin/trouble-categories/'.$cat->id, [
            'name' => 'orig_name',
            'display_name' => '新表示',
            'show_phone_number' => '1',
            'emergency_phone' => '',
            'sort_order' => '2',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.trouble-categories.index'));
        $this->assertDatabaseHas('trouble_categories', [
            'id' => $cat->id,
            'name' => 'orig_name',
            'display_name' => '新表示',
            'show_phone_number' => true,
            'sort_order' => 2,
        ]);
    }
}
