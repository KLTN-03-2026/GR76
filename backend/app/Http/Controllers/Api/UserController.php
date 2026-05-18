<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SuCo;
use App\Services\SuCoService;
use App\Http\Requests\StoreSuCoRequest;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $suCoService;

    public function __construct(SuCoService $suCoService)
    {
        $this->suCoService = $suCoService;
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()->load('vaiTro')]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $user->update($request->only('ten', 'so_dien_thoai'));
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $user]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        if (!Hash::check($request->mat_khau_cu, $user->mat_khau)) {
            return response()->json(['message' => 'Mật khẩu cũ không đúng'], 400);
        }
        $user->update(['mat_khau' => Hash::make($request->mat_khau_moi)]);
        return response()->json(['message' => 'Đổi mật khẩu thành công']);
    }

    public function storeIncident(StoreSuCoRequest $request)
    {
        $data = $request->validated();

        // Handle primary image upload
        if ($request->hasFile('hinh_anh')) {
            $path = $request->file('hinh_anh')->store('su_co_images', 'public');
            $data['hinh_anh'] = '/storage/' . $path;
        }

        // Handle multiple images upload (max 5)
        if ($request->hasFile('hinh_anhs')) {
            $paths = [];
            foreach ($request->file('hinh_anhs') as $file) {
                if (count($paths) >= 5) break;
                $p = $file->store('su_co_images', 'public');
                $paths[] = '/storage/' . $p;
            }
            $data['hinh_anhs'] = $paths;
            // Set primary image from first if not already set
            if (empty($data['hinh_anh']) && count($paths) > 0) {
                $data['hinh_anh'] = $paths[0];
            }
        }

        $incident = $this->suCoService->createIncident($data, $request->user()->id_nguoi_dung);
        $incident->load(['loaiSuCo', 'mucDoKhanCap']);

        return response()->json(['message' => 'Tạo sự cố thành công', 'data' => $incident], 201);
    }

    public function myIncidents(Request $request)
    {
        $incidents = SuCo::with(['loaiSuCo', 'mucDoKhanCap', 'phanTichAi'])
            ->where('id_nguoi_dung', $request->user()->id_nguoi_dung)
            ->orderBy('thoi_gian_dang', 'desc')
            ->get();
        return response()->json(['data' => $incidents]);
    }

    public function showIncident(Request $request, $id)
    {
        $suCo = SuCo::with(['loaiSuCo', 'mucDoKhanCap', 'phanTichAi'])
            ->where('id_su_co', $id)
            ->where('id_nguoi_dung', $request->user()->id_nguoi_dung)
            ->firstOrFail();
        return response()->json(['data' => $suCo]);
    }

    public function updateIncident(Request $request, $id)
    {
        $suCo = SuCo::where('id_su_co', $id)
            ->where('id_nguoi_dung', $request->user()->id_nguoi_dung)
            ->firstOrFail();

        if ($suCo->trang_thai !== 'pending') {
            return response()->json(['message' => 'Không thể chỉnh sửa sự cố đang được xử lý'], 403);
        }

        $data = $request->validate([
            'tieu_de'       => 'sometimes|string|min:5|max:200',
            'noi_dung'      => 'sometimes|string|min:10',
            'dia_chi'       => 'sometimes|string|min:5|max:255',
            'vi_do'         => 'sometimes|numeric',
            'kinh_do'       => 'sometimes|numeric',
            'id_loai_su_co' => 'sometimes|exists:loai_su_cos,id_loai_su_co',
            'id_muc_do'     => 'sometimes|exists:muc_do_khan_caps,id_muc_do',
            'hinh_anh'      => 'nullable|string',
        ]);

        $suCo->update($data);

        return response()->json(['message' => 'Cập nhật sự cố thành công', 'data' => $suCo]);
    }

    public function tiepNhan(Request $request, $id)
    {
        $suCo = SuCo::findOrFail($id);

        if ($suCo->trang_thai !== 'pending') {
            return response()->json(['message' => 'Sự cố này đang được xử lý hoặc đã hoàn thành'], 422);
        }

        $suCo->update(['trang_thai' => 'in_progress']);

        return response()->json(['message' => 'Đã tiếp nhận sự cố thành công', 'data' => $suCo]);
    }
}

