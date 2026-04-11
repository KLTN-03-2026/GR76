<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thong_baos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_su_co')->nullable()->after('id_nguoi_dung');
            $table->foreign('id_su_co')->references('id_su_co')->on('su_cos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('thong_baos', function (Blueprint $table) {
            $table->dropForeign(['id_su_co']);
            $table->dropColumn('id_su_co');
        });
    }
};
