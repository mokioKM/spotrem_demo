<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('option_billings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('option_contract_id')->constrained('option_contracts')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('billing_period', 7);
            $table->date('due_date');
            $table->text('invoice_pdf_url')->nullable();
            $table->string('invoice_pdf_filename', 255)->nullable();
            $table->foreignId('invoice_uploaded_by')->nullable()->constrained('admin_users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('invoice_uploaded_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('admin_users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['option_contract_id', 'billing_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_billings');
    }
};
