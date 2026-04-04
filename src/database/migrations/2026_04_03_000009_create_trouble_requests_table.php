<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trouble_requests', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('trouble_categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->cascadeOnUpdate()->nullOnDelete();
            $table->text('description');
            $table->date('preferred_date')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trouble_requests');
    }
};
