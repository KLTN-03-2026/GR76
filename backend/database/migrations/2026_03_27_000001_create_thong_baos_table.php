<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thong_baos', function (Blueprint $table) {
            $table->id('id_thong_bao');
            $table->foreignId('id_admin')->nullable()->constrained('admins', 'id_admin')->cascadeOnDelete();
            $table->foreignId('id_nguoi_dung')->nullable()->constrained('nguoi_dungs', 'id_nguoi_dung')->cascadeOnDelete();
            $table->string('tieu_de');
            $table->text('noi_dung');
            $table->boolean('da_doc')->default(false);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('thong_baos');
    }
};
