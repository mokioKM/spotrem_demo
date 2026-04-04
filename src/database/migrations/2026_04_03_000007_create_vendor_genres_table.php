<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_genres', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('trouble_categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->unique(['vendor_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_genres');
    }
};
