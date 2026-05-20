<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('su_cos', function (Blueprint $table) {
            // is_spam: 0 = hợp lệ, 1 = bị AI đánh dấu spam
            $table->tinyInteger('is_spam')->default(0)->after('trang_thai');
            // Lý do spam (từ AI)
            $table->string('spam_reason')->nullable()->after('is_spam');
            // Số lần user gửi báo cáo tương tự (dùng để detect repeat spam)
            $table->integer('repeat_count')->default(0)->after('spam_reason');
        });
    }

    public function down(): void
    {
        Schema::table('su_cos', function (Blueprint $table) {
            $table->dropColumn(['is_spam', 'spam_reason', 'repeat_count']);
        });
    }
};
