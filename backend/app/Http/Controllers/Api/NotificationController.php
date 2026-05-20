<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ThongBao::with(['admin', 'suCo']);

        if (isset($user->id_admin)) {
            $query->where('id_admin', $user->id_admin);
        } else {
            $query->where('id_nguoi_dung', $user->id_nguoi_dung);
        }

        $notifications = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        $query = ThongBao::where('id_thong_bao', $id);

        if (isset($user->id_admin)) {
            $query->where('id_admin', $user->id_admin);
        } else {
            $query->where('id_nguoi_dung', $user->id_nguoi_dung);
        }

        $notification = $query->firstOrFail();
        $notification->update(['da_doc' => true]);

        return response()->json(['success' => true, 'message' => 'Đã đánh dấu đã đọc']);
    }
}

