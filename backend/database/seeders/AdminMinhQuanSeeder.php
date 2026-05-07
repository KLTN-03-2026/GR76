<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminMinhQuanSeeder extends Seeder
{
    /**
     * Thêm tài khoản admin minhquan1810.
     */
    public function run(): void
    {
        Admin::firstOrCreate(
            ['ten_dang_nhap' => 'minhquan1810'],
            [
                'ho_ten'        => 'Minh Quân',
                'email'         => 'minhquan1810@sos.vn',
                'so_dien_thoai' => '0900001810',
                'mat_khau'      => Hash::make('quan1810'),
            ]
        );
    }
}
