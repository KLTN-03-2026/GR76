<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuCo;
use App\Services\SuCoService;
use App\Http\Requests\StoreSuCoRequest;
use App\Http\Requests\UpdateSuCoRequest;
use App\Http\Requests\UpdateIncidentStatusRequest;
use Illuminate\Http\Request;

class AdminIncidentController extends Controller
{
    protected $suCoService;

    public function __construct(SuCoService $suCoService)
    {
        $this->suCoService = $suCoService;
    }

    public function index()
    {
        return response()->json(['data' => SuCo::with(['nguoiDung', 'loaiSuCo', 'mucDoKhanCap', 'phanTichAi'])->get()]);
    }

    public function store(StoreSuCoRequest $request)
    {
        $userId = $request->input('id_nguoi_dung');
        if (!$userId) {
            return response()->json(['message' => 'Vui lòng cung cấp id_nguoi_dung'], 400);
        }
        $incident = $this->suCoService->createIncident($request->validated(), $userId);
        return response()->json(['message' => 'Tạo thành công', 'data' => $incident], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => SuCo::with(['nguoiDung', 'loaiSuCo', 'mucDoKhanCap', 'phanTichAi'])->findOrFail($id)]);
    }

    public function update(UpdateSuCoRequest $request, $id)
    {
        $suCo = SuCo::findOrFail($id);
        $this->suCoService->updateIncident($suCo, $request->validated());
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $suCo]);
    }

    public function destroy($id)
    {
        $suCo = SuCo::findOrFail($id);
        $suCo->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');
        $incidents = SuCo::with(['nguoiDung', 'loaiSuCo', 'mucDoKhanCap', 'phanTichAi'])
            ->where('tieu_de', 'like', "%{$q}%")
            ->orWhere('noi_dung', 'like', "%{$q}%")
            ->orWhere('dia_chi', 'like', "%{$q}%")
            ->get();
        return response()->json(['data' => $incidents]);
    }

    public function updateStatus(UpdateIncidentStatusRequest $request, $id)
    {
        $suCo = SuCo::findOrFail($id);
        $adminId = $request->user()->id_admin;
        $this->suCoService->updateStatus($suCo, $request->trang_thai, $adminId);
        return response()->json(['message' => 'Cập nhật trạng thái thành công', 'data' => $suCo]);
    }
}
