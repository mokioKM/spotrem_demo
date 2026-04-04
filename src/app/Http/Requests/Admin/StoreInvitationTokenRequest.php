<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationTokenRequest extends FormRequest
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
            'role' => ['required', 'string', Rule::in(['admin_user', 'vendor'])],
            'admin_user_id' => [
                'nullable',
                'integer',
                Rule::requiredIf($this->string('role')->toString() === 'admin_user'),
                Rule::exists('admin_users', 'id')->where('is_active', true),
            ],
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::requiredIf($this->string('role')->toString() === 'vendor'),
                Rule::exists('vendors', 'id')->where('is_active', true),
            ],
        ];
    }
}
