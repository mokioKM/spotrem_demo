<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn('line_messaging_group_id');
        });

        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->where('key', 'notification_group_line_uid')
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->string('line_messaging_group_id', 255)->nullable()->after('line_uid');
        });
    }
};
