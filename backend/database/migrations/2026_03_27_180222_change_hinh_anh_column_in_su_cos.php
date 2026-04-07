<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('su_cos', function (Blueprint $table) {
            // Change from VARCHAR(255) to TEXT to store longer file paths or base64
            $table->text('hinh_anh')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('su_cos', function (Blueprint $table) {
            $table->string('hinh_anh')->nullable()->change();
        });
    }
};
