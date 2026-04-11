<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
    public function up(): void
    {
        Schema::create('nguoi_dungs', function (Blueprint $table) {
            $table->id('id_nguoi_dung');
            $table->string('ten');
            $table->string('email')->unique();
            $table->string('so_dien_thoai')->unique();
            $table->string('mat_khau');
            $table->foreignId('id_vai_tro')->constrained('vai_tros', 'id_vai_tro')->cascadeOnDelete();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nguoi_dungs');
    }
};
