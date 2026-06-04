<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'preferred_slots' => ['nullable', 'array', 'max:3'],
            'preferred_slots.*.priority' => ['required_with:preferred_slots', 'integer', Rule::in([1, 2, 3])],
            'preferred_slots.*.date' => ['required_with:preferred_slots', 'date', 'after_or_equal:today'],
            'preferred_slots.*.start_time' => ['required_with:preferred_slots', 'regex:/^\d{2}:\d{2}$/'],
            'preferred_slots.*.end_time' => ['required_with:preferred_slots', 'regex:/^\d{2}:\d{2}$/'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*.cloudinary_public_id' => ['required_with:attachments.*', 'string', 'max:255'],
            'attachments.*.file_type' => ['required_with:attachments.*', Rule::in(['image', 'video'])],
            'attachments.*.url' => ['required_with:attachments.*', 'url', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $vendorId = $this->input('vendor_id');
            $slots = $this->input('preferred_slots');

            if ($vendorId !== null && $vendorId !== '') {
                if (! is_array($slots) || $slots === []) {
                    $validator->errors()->add('preferred_slots', __('業者を指定する場合は第1希望の日時を選択してください。'));

                    return;
                }

                $priorities = array_column($slots, 'priority');
                if (! in_array(1, $priorities, true)) {
                    $validator->errors()->add('preferred_slots', __('第1希望の日時を選択してください。'));
                }

                if (count($priorities) !== count(array_unique($priorities))) {
                    $validator->errors()->add('preferred_slots', __('希望順位が重複しています。'));
                }

                $keys = [];
                foreach ($slots as $index => $slot) {
                    if (! is_array($slot)) {
                        continue;
                    }
                    $key = ($slot['date'] ?? '').'|'.($slot['start_time'] ?? '').'|'.($slot['end_time'] ?? '');
                    if (isset($keys[$key])) {
                        $validator->errors()->add("preferred_slots.{$index}", __('同じ日時枠は重複して選択できません。'));
                    }
                    $keys[$key] = true;
                }
            } elseif (is_array($slots) && $slots !== []) {
                $validator->errors()->add('preferred_slots', __('業者未指定の場合は希望日時を送信できません。'));
            }
        });
    }

    /**
     * @return list<array{priority: int, date: string, start_time: string, end_time: string}>
     */
    public function preferredSlotsPayload(): array
    {
        $raw = $this->validated('preferred_slots') ?? [];

        usort($raw, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return array_values(array_map(static function (array $row): array {
            return [
                'priority' => (int) $row['priority'],
                'date' => $row['date'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
            ];
        }, $raw));
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
