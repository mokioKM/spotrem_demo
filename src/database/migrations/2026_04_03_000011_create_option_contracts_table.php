<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('option_contracts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 200);
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_contracts');
    }
};
