<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id('id_admin');
            $table->string('ten_dang_nhap')->unique();
            $table->string('mat_khau');
            $table->string('ho_ten');
            $table->string('email')->unique();
            $table->string('so_dien_thoai', 20)->nullable();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
