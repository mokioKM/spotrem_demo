<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResidentRequest extends FormRequest
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
            'property_id' => [
                'required',
                'integer',
                Rule::exists(Property::class, 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:100'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'room_number' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\\-]+$/'],
        ];
    }
}
