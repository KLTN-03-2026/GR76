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
        Schema::create('kiem_tra_trung_lap', function (Blueprint $table) {
            $table->id('id_trung_lap');
            $table->foreignId('id_su_co_1')->constrained('su_cos', 'id_su_co')->cascadeOnDelete();
            $table->foreignId('id_su_co_2')->constrained('su_cos', 'id_su_co')->cascadeOnDelete();
            $table->decimal('do_tuong_dong', 5, 4)->nullable();
            $table->boolean('ket_qua')->default(false);
            $table->timestamp('thoi_gian_kiem_tra')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kiem_tra_trung_lap');
    }
};
