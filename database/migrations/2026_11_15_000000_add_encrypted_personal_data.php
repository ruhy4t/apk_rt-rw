<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'households', 'residents', 'service_requests', 'asset_loans', 'contributions', 'financial_entries', 'stored_files'] as $tableName) {
            if (Schema::hasColumn($tableName, 'private_data')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->longText('private_data')->nullable();
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Kolom data terenkripsi tidak boleh dihapus melalui rollback. Gunakan rencana pemulihan yang telah diverifikasi.');
    }
};
