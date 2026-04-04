<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_attachments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('request_id')->constrained('trouble_requests')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('cloudinary_public_id', 255);
            $table->string('file_type', 20);
            $table->text('url');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_attachments');
    }
};
