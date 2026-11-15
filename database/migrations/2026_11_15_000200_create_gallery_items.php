<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gallery_items')) {
            return;
        }
        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_area_id')->nullable()->constrained('rt_areas')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('type', 10);
            $table->string('video_url', 2048)->nullable();
            $table->string('audience', 20)->default('residents');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['audience', 'is_published', 'created_at'], 'gallery_publication_index');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Galeri tidak dihapus melalui rollback. Gunakan pemulihan yang telah disetujui.');
    }
};
