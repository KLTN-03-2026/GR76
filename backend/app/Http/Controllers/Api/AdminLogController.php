<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NhatKyHeThong;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function index(Request $request)
    {
        $query = NhatKyHeThong::with(['admin', 'suCo']);

        if ($request->has('id_admin')) {
            $query->where('id_admin', $request->query('id_admin'));
        }

        if ($request->has('id_su_co')) {
            $query->where('id_su_co', $request->query('id_su_co'));
        }

        if ($request->has('tu_ngay') && $request->has('den_ngay')) {
            $query->whereBetween('thoi_gian', [$request->query('tu_ngay'), $request->query('den_ngay')]);
        }

        return response()->json(['data' => $query->orderBy('thoi_gian', 'desc')->get()]);
    }
}
