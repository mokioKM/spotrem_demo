<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invitation_tokens', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('token', 255)->unique();
            $table->string('role', 20);
            $table->foreignId('issued_by')->constrained('admin_users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('target_vendor_id')->nullable()->constrained('vendors')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('target_admin_user_id')->nullable()->constrained('admin_users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->boolean('is_used')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_tokens');
    }
};
