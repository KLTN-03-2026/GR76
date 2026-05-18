<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('su_cos', function (Blueprint $table) {
            // Store multiple image paths as JSON array (max 5 images)
            $table->json('hinh_anhs')->nullable()->after('hinh_anh');
        });
    }

    public function down(): void
    {
        Schema::table('su_cos', function (Blueprint $table) {
            $table->dropColumn('hinh_anhs');
        });
    }
};
