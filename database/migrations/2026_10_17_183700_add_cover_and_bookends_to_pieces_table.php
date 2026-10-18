<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pieces', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('desk');
            $table->longText('prologue')->nullable()->after('cover_path');
            $table->longText('epilogue')->nullable()->after('prologue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pieces', function (Blueprint $table) {
            $table->dropColumn(['cover_path', 'prologue', 'epilogue']);
        });
    }
};
