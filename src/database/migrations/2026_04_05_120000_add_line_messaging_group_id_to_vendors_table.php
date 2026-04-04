<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            // 業者＋管理会社担当者が入る LINE グループ（トラブル依頼の共有用）。1 業者につき最大 1 件。
            $table->string('line_messaging_group_id', 255)->nullable()->after('line_uid');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn('line_messaging_group_id');
        });
    }
};
