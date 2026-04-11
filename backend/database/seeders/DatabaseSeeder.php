<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\VaiTro;
use App\Models\NguoiDung;
use App\Models\LoaiSuCo;
use App\Models\MucDoKhanCap;
use App\Models\SuCo;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');

        // Roles
        $roles = [
            ['ten_vai_tro' => 'admin'],
            ['ten_vai_tro' => 'user']
        ];
        foreach ($roles as $role) {
            VaiTro::create($role);
        }

        // Admins
        for ($i = 1; $i <= 5; $i++) {
            Admin::create([
                'ten_dang_nhap' => 'admin' . $i,
                'mat_khau' => Hash::make('password'),
                'ho_ten' => $faker->name,
                'email' => 'admin' . $i . '@example.com',
                'so_dien_thoai' => $faker->numerify('09########'),
            ]);
        }

        // Users
        for ($i = 1; $i <= 20; $i++) {
            NguoiDung::create([
                'ten' => $faker->name,
                'email' => 'user' . $i . '@example.com',
                'so_dien_thoai' => $faker->numerify('09########'),
                'mat_khau' => Hash::make('password'),
                'id_vai_tro' => 2, // user
            ]);
        }

        // Categories
        $categories = ['Tai nạn giao thông', 'Cháy', 'Cây đổ', 'Ngập nước', 'Khác'];
        foreach ($categories as $cat) {
            LoaiSuCo::create(['ten_loai' => $cat]);
        }

        // Levels
        $levels = [
            ['ten_muc_do' => 'Thấp', 'do_uu_tien' => 1],
            ['ten_muc_do' => 'Trung bình', 'do_uu_tien' => 2],
            ['ten_muc_do' => 'Cao', 'do_uu_tien' => 3],
            ['ten_muc_do' => 'Khẩn cấp', 'do_uu_tien' => 4],
        ];
        foreach ($levels as $level) {
            MucDoKhanCap::create($level);
        }

        // Incidents
        for ($i = 1; $i <= 40; $i++) {
            SuCo::create([
                'id_nguoi_dung' => rand(1, 20),
                'tieu_de' => $faker->realText(50),
                'noi_dung' => $faker->realText(200),
                'hinh_anh' => null,
                'dia_chi' => $faker->address,
                'vi_do' => $faker->latitude(8.5, 23.3), // Vietnam approx bbox
                'kinh_do' => $faker->longitude(102.1, 109.4),
                'id_loai_su_co' => rand(1, count($categories)),
                'id_muc_do' => rand(1, count($levels)),
                'trang_thai' => collect(['pending', 'in_progress', 'resolved'])->random(),
            ]);
        }
    }
}
