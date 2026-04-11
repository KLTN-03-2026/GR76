<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
    public function up(): void
    {
        Schema::create('muc_do_khan_caps', function (Blueprint $table) {
            $table->id('id_muc_do');
            $table->string('ten_muc_do');
            $table->integer('do_uu_tien')->default(0);
            $table->timestamps();
        });
    }

 
    public function down(): void
    {
        Schema::dropIfExists('muc_do_khan_caps');
    }
};
