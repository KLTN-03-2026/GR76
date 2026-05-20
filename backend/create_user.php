<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = new App\Models\User();
$user->ten = 'Test User';
$user->email = 'testuser2026@gmail.com';
$user->mat_khau = Hash::make('password');
$user->vai_tro = 'nguoi_dung';
$user->trang_thai = 1;
$user->email_verified_at = now();
$user->save();
echo "User created";
