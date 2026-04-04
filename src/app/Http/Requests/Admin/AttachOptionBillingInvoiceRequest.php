<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AttachOptionBillingInvoiceRequest extends FormRequest
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
        $maxKb = (int) config('services.cloudinary.invoice_upload_max_kb', 20480);

        return [
            'invoice_pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.$maxKb],
        ];
    }
}
