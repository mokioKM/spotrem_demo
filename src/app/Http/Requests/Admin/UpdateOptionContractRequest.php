<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOptionContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('services.cloudinary.invoice_upload_max_kb', 20480);

        return [
            'resident_id' => [
                'required',
                'integer',
                Rule::exists('residents', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'is_active' => ['boolean'],
            'invoice_pdf' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:'.$maxKb],
        ];
    }
}
