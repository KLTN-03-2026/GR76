<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 
    public function up(): void
    {
        Schema::create('loai_su_cos', function (Blueprint $table) {
            $table->id('id_loai_su_co');
            $table->string('ten_loai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loai_su_cos');
    }
};
