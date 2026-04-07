<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('su_cos', function (Blueprint $table) {
            $table->id('id_su_co');
            $table->foreignId('id_nguoi_dung')->constrained('nguoi_dungs', 'id_nguoi_dung')->cascadeOnDelete();
            $table->string('tieu_de');
            $table->text('noi_dung');
            $table->string('hinh_anh')->nullable();
            $table->string('dia_chi')->nullable();
            $table->decimal('vi_do', 10, 8)->nullable();
            $table->decimal('kinh_do', 11, 8)->nullable();
            $table->foreignId('id_loai_su_co')->constrained('loai_su_cos', 'id_loai_su_co');
            $table->foreignId('id_muc_do')->constrained('muc_do_khan_caps', 'id_muc_do');
            $table->string('trang_thai')->default('Mới tiếp nhận');
            $table->timestamp('thoi_gian_dang')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('su_cos');
    }
};
