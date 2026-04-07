<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use App\Models\NguoiDung;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = ThongBao::with(['nguoiDung', 'admin'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_nguoi_dung' => 'required|exists:nguoi_dungs,id_nguoi_dung',
            'tieu_de'       => 'required|string|max:255',
            'noi_dung'      => 'required|string',
        ]);

        $data['id_admin'] = $request->user()->id_admin;

        $notification = ThongBao::create($data);
        $notification->load(['nguoiDung', 'admin']);

        return response()->json([
            'success' => true,
            'message' => 'Gửi thông báo thành công',
            'data'    => $notification,
        ], 201);
    }
}
