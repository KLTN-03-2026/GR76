<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use App\Models\SuCo;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function analyze(Request $request, $id)
    {
        $suCo = SuCo::findOrFail($id);
        $result = $this->aiService->analyzeIncident($id, $suCo->noi_dung);
        return response()->json([
            'message' => 'Phân tích thành công',
            'data' => $result
        ]);
    }

    public function checkDuplicate(Request $request, $id)
    {
        SuCo::findOrFail($id);
        $result = $this->aiService->checkDuplicate($id);
        return response()->json([
            'message' => 'Kiểm tra thành công',
            'data' => $result
        ]);
    }

    public function checkSpamAndDuplicatePre(Request $request)
    {
        $data = $request->validate([
            'tieu_de' => 'required|string',
            'noi_dung' => 'nullable|string',
            'dia_chi' => 'nullable|string',
            'vi_do' => 'nullable|numeric',
            'kinh_do' => 'nullable|numeric',
        ]);

        $result = $this->aiService->checkSpamAndDuplicatePre($data);
        return response()->json($result);
    }
}
