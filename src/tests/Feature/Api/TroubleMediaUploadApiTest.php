<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TroubleMediaUploadApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.cloudinary.cloud_name', 'test-cloud');
        Config::set('services.cloudinary.api_key', '123456789012345');
        Config::set('services.cloudinary.api_secret', 'test-api-secret-value');
        Config::set('services.cloudinary.trouble_upload_folder', 'spotrem/trouble');

        Http::fake([
            'https://api.line.me/oauth2/v2.1/verify' => Http::response([
                'iss' => 'https://access.line.me',
                'sub' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
                'aud' => 'test-liff-channel',
                'exp' => time() + 3600,
                'iat' => time(),
            ], 200),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->get('/api/media/trouble-upload-signature');

        $response->assertStatus(401);
    }

    public function test_returns_signature_payload_shape(): void
    {
        $response = $this->get('/api/media/trouble-upload-signature', [
            'Authorization' => 'Bearer fake-token',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'cloud_name',
            'api_key',
            'timestamp',
            'signature',
            'folder',
            'upload_url',
        ]);
        $response->assertJsonPath('cloud_name', 'test-cloud');
        $response->assertJsonPath('api_key', '123456789012345');
        $response->assertJsonPath('folder', 'spotrem/trouble');
        $response->assertJsonPath('upload_url', 'https://api.cloudinary.com/v1_1/test-cloud/auto/upload');
        $this->assertNotEmpty($response->json('signature'));
    }
}
