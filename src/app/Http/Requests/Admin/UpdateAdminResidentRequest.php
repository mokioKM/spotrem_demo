<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminResidentRequest extends FormRequest
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
            'property_id' => ['required', 'integer', Rule::exists(Property::class, 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:100'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'room_number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\\-]+$/'],
            'is_active' => ['required', 'boolean'],
            'redirect_property_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array{property_id: int, name: string, age?: int|null, room_number: string, phone: string, is_active: bool}
     */
    public function residentPayload(): array
    {
        $v = $this->validated();

        return [
            'property_id' => (int) $v['property_id'],
            'name' => $v['name'],
            'age' => isset($v['age']) ? (int) $v['age'] : null,
            'room_number' => $v['room_number'],
            'phone' => $v['phone'],
            'is_active' => (bool) $v['is_active'],
        ];
    }
}
