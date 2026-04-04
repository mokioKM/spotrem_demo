<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\OptionBilling;
use App\Models\OptionContract;
use App\Models\Property;
use App\Models\Resident;
use App\Models\Role;
use App\Services\Line\OptionInvoiceLinePostback;
use App\Services\Media\CloudinaryOptionInvoiceUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class OptionContractPdfTest extends TestCase
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

    private function seedResidentWithLine(): Resident
    {
        $property = Property::query()->create([
            'name' => '物件',
            'address' => 'a',
            'region' => '東京都',
            'room_count' => 1,
            'is_active' => true,
        ]);

        return Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Uoptionresidenttest0000000000001',
            'name' => '入居者',
            'age' => null,
            'room_number' => '101',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_contract_uploads_pdf_to_cloudinary_and_saves_billing_url(): void
    {
        $this->mock(CloudinaryOptionInvoiceUploadService::class, function ($m): void {
            $m->shouldReceive('uploadPdf')
                ->once()
                ->andReturn([
                    'secure_url' => 'https://res.cloudinary.com/test/raw/upload/v1/spotrem/invoices/demo.pdf',
                    'public_id' => 'spotrem/invoices/demo',
                ]);
        });

        $admin = $this->actingSuperAdmin();
        $resident = $this->seedResidentWithLine();

        $pdf = UploadedFile::fake()->create('invoice.pdf', 50, 'application/pdf');

        $response = $this->actingAs($admin, 'admin')->post('/admin/option-contracts', [
            'resident_id' => $resident->id,
            'name' => '駐車場',
            'amount' => '12000',
            'due_date' => '2026-05-31',
            'is_active' => '1',
            'invoice_pdf' => $pdf,
        ]);

        $response->assertRedirect(route('admin.option-contracts.index'));
        $this->assertDatabaseHas('option_billings', [
            'invoice_pdf_url' => 'https://res.cloudinary.com/test/raw/upload/v1/spotrem/invoices/demo.pdf',
        ]);
    }

    public function test_send_demo_pushes_line_when_pdf_exists(): void
    {
        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        $admin = $this->actingSuperAdmin();
        $resident = $this->seedResidentWithLine();

        $contract = OptionContract::query()->create([
            'resident_id' => $resident->id,
            'name' => '駐車場',
            'amount' => 12000,
            'due_date' => '2026-05-31',
            'is_active' => true,
        ]);

        $billing = OptionBilling::query()->create([
            'option_contract_id' => $contract->id,
            'billing_period' => '2026-05',
            'due_date' => '2026-05-31',
            'invoice_pdf_url' => 'https://res.cloudinary.com/test/raw/upload/v1/x.pdf',
            'invoice_pdf_filename' => 'x.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/option-contracts/'.$contract->id.'/send-demo');

        $response->assertRedirect(route('admin.option-contracts.index'));
        $response->assertSessionHas('status');

        Http::assertSent(function (Request $request) use ($billing): bool {
            if ($request->url() !== 'https://api.line.me/v2/bot/message/push') {
                return false;
            }
            $decoded = json_decode($request->body(), true);
            $text = (string) (($decoded['messages'][0]['text'] ?? ''));
            $msg1 = $decoded['messages'][1] ?? null;
            $action = is_array($msg1)
                ? (($msg1['template']['actions'][0] ?? [])['data'] ?? null)
                : null;

            return is_array($decoded)
                && ($decoded['to'] ?? null) === 'Uoptionresidenttest0000000000001'
                && str_contains($text, 'option-invoices/'.$billing->id)
                && str_contains($text, 'signature=')
                && ($msg1['type'] ?? null) === 'template'
                && $action === OptionInvoiceLinePostback::PAYMENT_COMPLETE;
        });
    }

    public function test_send_demo_redirects_with_error_when_no_pdf(): void
    {
        $admin = $this->actingSuperAdmin();
        $resident = $this->seedResidentWithLine();

        $contract = OptionContract::query()->create([
            'resident_id' => $resident->id,
            'name' => '駐車場',
            'amount' => 12000,
            'due_date' => '2026-05-31',
            'is_active' => true,
        ]);

        OptionBilling::query()->create([
            'option_contract_id' => $contract->id,
            'billing_period' => '2026-05',
            'due_date' => '2026-05-31',
            'invoice_pdf_url' => null,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/option-contracts/'.$contract->id.'/send-demo');

        $response->assertRedirect(route('admin.option-contracts.index'));
        $response->assertSessionHasErrors('send');
    }
}
