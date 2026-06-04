<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TroubleRequestPreferredSlot extends Model
{
    protected $fillable = [
        'trouble_request_id',
        'priority',
        'slot_date',
        'start_time',
        'end_time',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<TroubleRequest, $this>
     */
    public function troubleRequest(): BelongsTo
    {
        return $this->belongsTo(TroubleRequest::class);
    }

    public function displayLabel(): string
    {
        $day = $this->slot_date?->copy()->timezone('Asia/Tokyo')->locale('ja');

        if ($day === null) {
            return $this->start_time.'–'.$this->end_time;
        }

        return $day->isoFormat('Y-MM-DD（ddd）').$this->start_time.'–'.$this->end_time;
    }
}
