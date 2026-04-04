<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OptionBilling;
use App\Models\OptionContract;
use App\Models\Property;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicOptionInvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    private function seedBillingWithPdfUrl(string $pdfUrl): OptionBilling
    {
        $property = Property::query()->create([
            'name' => '物件',
            'address' => 'a',
            'region' => '東京都',
            'room_count' => 1,
            'is_active' => true,
        ]);

        $resident = Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Utest00000000000000000000000001',
            'name' => '入居者',
            'age' => null,
            'room_number' => '101',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $contract = OptionContract::query()->create([
            'resident_id' => $resident->id,
            'name' => '駐車場',
            'amount' => 12000,
            'due_date' => '2026-05-31',
            'is_active' => true,
        ]);

        return OptionBilling::query()->create([
            'option_contract_id' => $contract->id,
            'billing_period' => '2026-05',
            'due_date' => '2026-05-31',
            'invoice_pdf_url' => $pdfUrl,
            'invoice_pdf_filename' => '請求書.pdf',
            'status' => 'pending',
        ]);
    }

    public function test_signed_url_streams_pdf_with_correct_content_type(): void
    {
        $source = 'https://res.cloudinary.com/demo/raw/upload/v1/spotrem/invoices/x.pdf';
        Http::fake([
            'https://res.cloudinary.com/*' => Http::response("%PDF-1.4 test\n", 200, [
                'Content-Type' => 'application/octet-stream',
            ]),
        ]);

        $billing = $this->seedBillingWithPdfUrl($source);
        $url = URL::temporarySignedRoute(
            'public.option-invoices.show',
            now()->addHour(),
            ['optionBilling' => $billing->id],
        );

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('%PDF', (string) $response->streamedContent());
    }

    public function test_unsigned_request_is_forbidden(): void
    {
        $billing = $this->seedBillingWithPdfUrl('https://res.cloudinary.com/demo/raw/upload/v1/x.pdf');

        $response = $this->get('/option-invoices/'.$billing->id);

        $response->assertForbidden();
    }
}
