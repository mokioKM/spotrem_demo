<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('recipient_type', 20);
            $table->unsignedBigInteger('recipient_id');
            $table->string('channel', 20);
            $table->string('event_type', 50);
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('sent_at');
            $table->string('status', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['recipient_type', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
