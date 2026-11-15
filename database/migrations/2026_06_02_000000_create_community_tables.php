<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rw_areas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('rt_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rw_area_id')->constrained()->restrictOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->timestamps();
            $table->unique(['rw_area_id', 'code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rw_area_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('rt_area_id')->nullable()->constrained()->restrictOnDelete();
        });

        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_area_id')->constrained()->restrictOnDelete();
            $table->string('household_code', 32);
            $table->text('address')->nullable();
            $table->timestamps();
            $table->unique(['rt_area_id', 'household_code']);
        });

        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('relationship', 50)->nullable();
            $table->string('phone', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_area_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('audience', 20)->default('public');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['audience', 'is_published', 'published_at']);
        });

        Schema::create('community_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_area_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('audience', 20)->default('public');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['audience', 'is_published', 'starts_at']);
        });

        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('period', 20);
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['household_id', 'period']);
        });

        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_area_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('direction', 10);
            $table->string('category', 80);
            $table->unsignedBigInteger('amount');
            $table->text('description')->nullable();
            $table->date('occurred_on');
            $table->boolean('public_summary')->default(false);
            $table->timestamps();
            $table->index(['rt_area_id', 'occurred_on']);
            $table->index(['public_summary', 'direction']);
        });

        Schema::create('community_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_area_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('category', 80)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition', 32)->nullable();
            $table->boolean('public_summary')->default(false);
            $table->timestamps();
            $table->index(['rt_area_id', 'category']);
        });

        Schema::create('asset_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('community_assets')->restrictOnDelete();
            $table->foreignId('household_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->text('purpose')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['household_id', 'status']);
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 40);
            $table->string('subject');
            $table->text('description');
            $table->text('response')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->timestamps();
            $table->index(['household_id', 'status']);
            $table->index(['requester_id', 'created_at']);
        });

        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            $table->morphs('fileable');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('disk', 32)->default('b2');
            $table->string('object_key', 512)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('stored_files');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('asset_loans');
        Schema::dropIfExists('community_assets');
        Schema::dropIfExists('financial_entries');
        Schema::dropIfExists('contributions');
        Schema::dropIfExists('community_events');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('residents');
        Schema::dropIfExists('households');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rt_area_id');
            $table->dropConstrainedForeignId('rw_area_id');
        });
        Schema::dropIfExists('rt_areas');
        Schema::dropIfExists('rw_areas');
    }
};
