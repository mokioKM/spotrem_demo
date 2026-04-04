<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('property_id')->constrained('properties')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('line_uid', 100)->unique();
            $table->string('name', 100);
            $table->integer('age')->nullable();
            $table->string('room_number', 20);
            $table->string('phone', 20);
            $table->timestamp('registered_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
