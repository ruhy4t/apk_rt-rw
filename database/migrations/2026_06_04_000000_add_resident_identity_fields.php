<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->text('family_card_number')->nullable();
            $table->char('family_card_digest', 64)->nullable()->unique();
        });

        Schema::table('residents', function (Blueprint $table) {
            $table->text('national_id')->nullable();
            $table->char('national_id_digest', 64)->nullable()->unique();
            $table->string('birthplace', 160)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 16)->nullable();
            $table->text('religion')->nullable();
            $table->string('marital_status', 32)->nullable();
            $table->string('education', 80)->nullable();
            $table->string('occupation', 160)->nullable();
            $table->string('residency_status', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropUnique(['family_card_digest']);
            $table->dropColumn(['family_card_number', 'family_card_digest']);
        });
        Schema::table('residents', function (Blueprint $table) {
            $table->dropUnique(['national_id_digest']);
            $table->dropColumn(['national_id', 'national_id_digest', 'birthplace', 'birth_date', 'gender', 'religion', 'marital_status', 'education', 'occupation', 'residency_status']);
        });
    }
};
