<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['rt_areas', 'rw_areas'] as $name) {
            if (! Schema::hasColumn($name, 'chairman_name')) {
                Schema::table($name, fn (Blueprint $table) => $table->longText('chairman_name')->nullable());
            }
        }
        foreach (['service_requests', 'announcements', 'community_events'] as $name) {
            if (! Schema::hasColumn($name, 'signatory_data')) {
                Schema::table($name, fn (Blueprint $table) => $table->longText('signatory_data')->nullable());
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Data penandatangan tidak boleh dihapus melalui rollback. Gunakan pemulihan yang telah disetujui.');
    }
};
