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
        Schema::create('nhat_ky_he_thong', function (Blueprint $table) {
            $table->id('id_log');
            $table->foreignId('id_admin')->nullable()->constrained('admins', 'id_admin')->nullOnDelete();
            $table->foreignId('id_su_co')->nullable()->constrained('su_cos', 'id_su_co')->nullOnDelete();
            $table->string('hanh_dong');
            $table->text('ghi_chu')->nullable();
            $table->timestamp('thoi_gian')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhat_ky_he_thong');
    }
};
