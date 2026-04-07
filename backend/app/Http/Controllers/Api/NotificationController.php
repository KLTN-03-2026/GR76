<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = ThongBao::with('admin')
            ->where('id_nguoi_dung', $request->user()->id_nguoi_dung)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function markRead(Request $request, $id)
    {
        $notification = ThongBao::where('id_thong_bao', $id)
            ->where('id_nguoi_dung', $request->user()->id_nguoi_dung)
            ->firstOrFail();

        $notification->update(['da_doc' => true]);

        return response()->json(['success' => true, 'message' => 'Đã đánh dấu đã đọc']);
    }
}
