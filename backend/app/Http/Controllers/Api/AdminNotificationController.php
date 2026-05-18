<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThongBao;
use App\Models\NguoiDung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = ThongBao::with(['nguoiDung', 'admin'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    /**
     * Send notification to one or ALL users.
     * If id_nguoi_dung is omitted / "all", broadcast to every active user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_nguoi_dung' => 'nullable|string',   // can be numeric id OR "all"
            'tieu_de'       => 'required|string|max:255',
            'noi_dung'      => 'required|string',
        ]);

        $adminId = $request->user()->id_admin;
        $isBroadcast = !isset($data['id_nguoi_dung'])
            || $data['id_nguoi_dung'] === ''
            || $data['id_nguoi_dung'] === 'all';

        if ($isBroadcast) {
            // Send to ALL active users
            $users = NguoiDung::where('trang_thai', '!=', 'locked')
                ->pluck('id_nguoi_dung');

            $now = now();
            $rows = $users->map(fn ($uid) => [
                'id_admin'      => $adminId,
                'id_nguoi_dung' => $uid,
                'tieu_de'       => $data['tieu_de'],
                'noi_dung'      => $data['noi_dung'],
                'da_doc'        => false,
                'created_at'    => $now,
                'updated_at'    => $now,
            ])->toArray();

            // Batch insert for performance
            foreach (array_chunk($rows, 500) as $chunk) {
                ThongBao::insert($chunk);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã gửi thông báo đến {$users->count()} người dùng",
                'count'   => $users->count(),
            ], 201);
        }

        // Single user
        $request->validate([
            'id_nguoi_dung' => 'exists:nguoi_dungs,id_nguoi_dung',
        ]);

        $notification = ThongBao::create([
            'id_admin'      => $adminId,
            'id_nguoi_dung' => $data['id_nguoi_dung'],
            'tieu_de'       => $data['tieu_de'],
            'noi_dung'      => $data['noi_dung'],
        ]);
        $notification->load(['nguoiDung', 'admin']);

        return response()->json([
            'success' => true,
            'message' => 'Gửi thông báo thành công',
            'data'    => $notification,
        ], 201);
    }
}

