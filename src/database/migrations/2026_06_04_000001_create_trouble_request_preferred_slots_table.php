<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trouble_request_preferred_slots', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('trouble_request_id')->constrained('trouble_requests')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedTinyInteger('priority');
            $table->date('slot_date');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->timestamps();

            $table->unique(['trouble_request_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trouble_request_preferred_slots');
    }
};
