<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_regions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('region', 100);
            $table->unique(['vendor_id', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_regions');
    }
};
