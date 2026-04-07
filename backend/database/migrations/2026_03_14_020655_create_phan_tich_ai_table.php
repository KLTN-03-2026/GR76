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
        Schema::create('phan_tich_ai', function (Blueprint $table) {
            $table->id('id_ai');
            $table->foreignId('id_su_co')->constrained('su_cos', 'id_su_co')->cascadeOnDelete();
            $table->integer('loai_su_co')->nullable();
            $table->integer('muc_do_khan_cap')->nullable();
            $table->decimal('do_tin_cay', 5, 4)->nullable();
            $table->timestamp('thoi_gian_phan_tich')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phan_tich_ai');
    }
};
