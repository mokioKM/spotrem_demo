<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTroubleCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', Rule::unique('trouble_categories', 'name')],
            'display_name' => ['required', 'string', 'max:100'],
            'show_phone_number' => ['required', 'boolean'],
            'emergency_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\\-+]+$/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array{name: string, display_name: string, show_phone_number: bool, emergency_phone: ?string, sort_order: int, is_active: bool}
     */
    public function categoryPayload(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'show_phone_number' => (bool) $validated['show_phone_number'],
            'emergency_phone' => isset($validated['emergency_phone']) && $validated['emergency_phone'] !== ''
                ? (string) $validated['emergency_phone']
                : null,
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => (bool) $validated['is_active'],
        ];
    }
}
