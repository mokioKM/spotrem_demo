<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Property;
use App\Models\Role;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PropertyVendorSoftDeleteTest extends TestCase
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

    public function test_property_soft_deleted_and_hidden_from_default_query(): void
    {
        $admin = $this->actingSuperAdmin();
        $p = Property::query()->create([
            'name' => '削除対象',
            'address' => 'a',
            'region' => '東京都',
            'room_count' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->delete('/admin/properties/'.$p->id);

        $response->assertRedirect(route('admin.properties.index'));
        $this->assertSoftDeleted('properties', ['id' => $p->id]);
        $this->assertNull(Property::query()->find($p->id));
    }

    public function test_vendor_soft_deleted(): void
    {
        $admin = $this->actingSuperAdmin();
        $v = Vendor::query()->create([
            'name' => '削除業者',
            'phone' => '090-0000-0000',
            'line_uid' => null,
            'google_calendar_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->delete('/admin/vendors/'.$v->id);

        $response->assertRedirect(route('admin.vendors.index'));
        $this->assertSoftDeleted('vendors', ['id' => $v->id]);
        $this->assertNull(Vendor::query()->find($v->id));
    }
}
