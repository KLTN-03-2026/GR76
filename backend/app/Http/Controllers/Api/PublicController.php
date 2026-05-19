<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuCo;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function map()
    {
        $incidents = SuCo::with(['loaiSuCo', 'mucDoKhanCap'])
            ->whereNotNull('vi_do')
            ->whereNotNull('kinh_do')
            ->whereIn('trang_thai', ['pending', 'in_progress', 'resolved'])
            ->select('id_su_co', 'tieu_de', 'dia_chi', 'vi_do', 'kinh_do', 'trang_thai', 'id_loai_su_co', 'id_muc_do', 'thoi_gian_dang', 'hinh_anh', 'hinh_anhs')
            ->orderBy('thoi_gian_dang', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $incidents]);
    }

    public function search(Request $request)
    {
        $query = SuCo::with(['loaiSuCo', 'mucDoKhanCap'])
            ->whereIn('trang_thai', ['pending', 'in_progress', 'resolved']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('tieu_de', 'like', "%{$q}%")
                    ->orWhere('noi_dung', 'like', "%{$q}%");
            });
        }

        if ($request->filled('khu_vuc')) {
            $query->where('dia_chi', 'like', '%' . $request->khu_vuc . '%');
        }

        if ($request->filled('id_muc_do')) {
            $query->where('id_muc_do', $request->id_muc_do);
        }

        if ($request->filled('id_loai_su_co')) {
            $query->where('id_loai_su_co', $request->id_loai_su_co);
        }

        $incidents = $query->orderBy('thoi_gian_dang', 'desc')->get();

        return response()->json(['success' => true, 'data' => $incidents]);
    }

    public function show($id)
    {
        $suCo = SuCo::with(['loaiSuCo', 'mucDoKhanCap', 'nguoiDung'])
            ->where('id_su_co', $id)
            ->whereIn('trang_thai', ['pending', 'in_progress', 'resolved'])
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $suCo]);
    }
}
