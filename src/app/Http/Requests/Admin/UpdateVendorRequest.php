<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $text = $this->string('regions_text')->toString();
        $lines = preg_split('/\R/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $regions = array_values(array_unique(array_filter(array_map('trim', $lines), static fn (string $r): bool => $r !== '')));
        $gid = $this->input('line_messaging_group_id');
        $this->merge([
            'regions' => $regions,
            'line_messaging_group_id' => (is_string($gid) && trim($gid) !== '') ? trim($gid) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\\-]+$/'],
            'line_messaging_group_id' => ['nullable', 'string', 'max:255'],
            'google_calendar_id' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => [
                'integer',
                Rule::exists('trouble_categories', 'id')->where('is_active', true),
            ],
            'regions_text' => ['required', 'string'],
            'regions' => ['required', 'array', 'min:1'],
            'regions.*' => ['required', 'string', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return list<int>
     */
    public function categoryIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->validated('category_ids');

        return array_values(array_unique(array_map(static fn (int|string $id): int => (int) $id, $ids)));
    }

    /**
     * @return list<string>
     */
    public function regionStrings(): array
    {
        /** @var list<string> $regions */
        $regions = $this->validated('regions');

        return $regions;
    }
}
