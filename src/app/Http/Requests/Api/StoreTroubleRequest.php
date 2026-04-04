<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTroubleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('trouble_categories', 'id')->where(static function ($q): void {
                    $q->where('is_active', true)->where('show_phone_number', false);
                }),
            ],
            'description' => ['required', 'string', 'max:2000'],
            'vendor_id' => ['nullable', 'integer', Rule::exists(Vendor::class, 'id')->where('is_active', true)],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*.cloudinary_public_id' => ['required_with:attachments.*', 'string', 'max:255'],
            'attachments.*.file_type' => ['required_with:attachments.*', Rule::in(['image', 'video'])],
            'attachments.*.url' => ['required_with:attachments.*', 'url', 'max:2048'],
        ];
    }

    /**
     * @return list<array{cloudinary_public_id: string, file_type: string, url: string}>
     */
    public function attachmentsPayload(): array
    {
        $raw = $this->validated('attachments') ?? [];

        return array_values(array_map(static function (array $row): array {
            return [
                'cloudinary_public_id' => $row['cloudinary_public_id'],
                'file_type' => $row['file_type'],
                'url' => $row['url'],
            ];
        }, $raw));
    }
}
