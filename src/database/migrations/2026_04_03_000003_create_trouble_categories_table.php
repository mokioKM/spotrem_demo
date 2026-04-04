<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trouble_categories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('display_name', 100);
            $table->boolean('show_phone_number')->default(false);
            $table->string('emergency_phone', 20)->nullable();
            $table->integer('sort_order');
            $table->boolean('is_active')->default(true);
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trouble_categories');
    }
};
