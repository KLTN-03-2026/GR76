<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing Vietnamese status values to English
        $mapping = [
            'Mới tiếp nhận' => 'pending',
            'Đang xử lý'    => 'in_progress',
            'Đã xác thực'   => 'resolved',
            'Từ chối'        => 'rejected',
        ];

        foreach ($mapping as $old => $new) {
            DB::table('su_cos')->where('trang_thai', $old)->update(['trang_thai' => $new]);
        }

        // Change default value
        Schema::table('su_cos', function (Blueprint $table) {
            $table->string('trang_thai')->default('pending')->change();
        });
    }

    public function down(): void
    {
        $mapping = [
            'pending'     => 'Mới tiếp nhận',
            'in_progress' => 'Đang xử lý',
            'resolved'    => 'Đã xác thực',
            'rejected'    => 'Từ chối',
        ];

        foreach ($mapping as $old => $new) {
            DB::table('su_cos')->where('trang_thai', $old)->update(['trang_thai' => $new]);
        }

        Schema::table('su_cos', function (Blueprint $table) {
            $table->string('trang_thai')->default('Mới tiếp nhận')->change();
        });
    }
};
