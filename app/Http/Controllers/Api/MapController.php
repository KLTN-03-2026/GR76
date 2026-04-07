<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SuCoService;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    protected SuCoService $suCoService;

    public function __construct(SuCoService $suCoService)
    {
        $this->suCoService = $suCoService;
    }

    public function index(): JsonResponse
    {
        $incidents = $this->suCoService->getMapIncidents();

        return response()->json([
            'success' => true,
            'data'    => $incidents,
        ]);
    }
}
