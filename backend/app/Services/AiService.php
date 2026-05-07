<?php

namespace App\Services;

use App\Models\PhanTichAi;
use App\Models\KiemTraTrungLap;
use App\Models\SuCo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiService
{
    protected string $aiUrl;

    public function __construct()
    {
        $this->aiUrl = rtrim(config('services.ai.url', 'http://localhost:8001'), '/');
    }

    // ─────────────────────────────────────────────────────────────────
    // PUBLIC METHODS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Analyze incident using TEXT + IMAGE (multimodal if image exists).
     * Called by AiController → POST /ai/phan-tich/{id}
     */
    public function analyzeIncident(int $suCoId, string $noiDung): PhanTichAi
    {
        $suCo = SuCo::findOrFail($suCoId);

        $result = $suCo->hinh_anh
            ? $this->analyzeMultimodal($suCo)
            : $this->analyzeTextOnly($suCo, $noiDung);

        $loai  = $result['loai_su_co']      ?? [];
        $mucdo = $result['muc_do_khan_cap'] ?? [];

        return PhanTichAi::updateOrCreate(
            ['id_su_co' => $suCoId],
            [
                'loai_su_co'      => $loai['id_loai_su_co'] ?? 1,
                'muc_do_khan_cap' => $mucdo['id_muc_do']     ?? 1,
                'do_tin_cay'      => $loai['do_tin_cay']     ?? 0.0,
            ]
        );
    }

    /**
     * Check duplicate incidents.
     * Called by AiController → POST /ai/check-duplicate/{id}
     */
    public function checkDuplicate(int $suCoId): KiemTraTrungLap|array
    {
        $suCo = SuCo::findOrFail($suCoId);

        $existing = SuCo::where('id_su_co', '!=', $suCoId)
            ->orderBy('thoi_gian_dang', 'desc')
            ->limit(50)
            ->get(['id_su_co', 'tieu_de', 'noi_dung'])
            ->map(fn ($s) => [
                'id_su_co' => $s->id_su_co,
                'tieu_de'  => $s->tieu_de,
                'noi_dung' => $s->noi_dung ?? '',
            ])->toArray();

        $result = $this->callJson('/check-duplicate', [
            'id_su_co'  => $suCoId,
            'tieu_de'   => $suCo->tieu_de ?? '',
            'noi_dung'  => $suCo->noi_dung ?? '',
            'existing'  => $existing,
            'threshold' => 0.75,
        ]);

        if ($result['la_trung_lap'] ?? false) {
            return KiemTraTrungLap::create([
                'id_su_co_1'    => $result['id_su_co_trung'],
                'id_su_co_2'    => $suCoId,
                'do_tuong_dong' => $result['do_tuong_dong'] ?? 0,
                'ket_qua'       => true,
            ]);
        }

        return [
            'id_su_co_trung' => null,
            'do_tuong_dong'  => $result['do_tuong_dong'] ?? 0,
            'ket_qua'        => false,
        ];
    }

    /**
     * Submit labeled incident to retrain text AI.
     */
    public function retrainText(array $samples): array
    {
        return $this->callJson('/retrain', ['samples' => $samples]);
    }

    /**
     * Submit labeled image URLs to retrain image AI.
     * Call this after admin confirms/resolves an incident with an image.
     */
    public function retrainImages(array $labeledImages): array
    {
        return $this->callJson('/retrain-images', $labeledImages);
    }

    /**
     * Health check.
     */
    public function isHealthy(): bool
    {
        try {
            $resp = Http::timeout(2)->get("{$this->aiUrl}/health");
            return $resp->ok() && $resp->json('status') === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Send text-only to /analyze.
     */
    private function analyzeTextOnly(SuCo $suCo, string $noiDung): array
    {
        return $this->callJson('/analyze', [
            'id_su_co' => $suCo->id_su_co,
            'tieu_de'  => $suCo->tieu_de ?? '',
            'noi_dung' => $noiDung,
        ]);
    }

    /**
     * Send text + image to /analyze-with-image (multipart form).
     */
    private function analyzeMultimodal(SuCo $suCo): array
    {
        try {
            // hinh_anh is stored as '/storage/su_co_images/xxx.jpg'
            // Strip leading '/storage/' to get the relative path for Storage::disk('public')
            $storagePath = ltrim(str_replace('/storage/', '', $suCo->hinh_anh), '/');
            $imagePath   = Storage::disk('public')->path($storagePath);

            if (!file_exists($imagePath)) {
                Log::warning("AI multimodal: image not found at {$imagePath}, falling back to text.");
                return $this->analyzeTextOnly($suCo, $suCo->noi_dung ?? '');
            }

            $response = Http::timeout(30)
                ->attach('image', file_get_contents($imagePath), basename($imagePath))
                ->post("{$this->aiUrl}/analyze-with-image", [
                    'id_su_co' => $suCo->id_su_co,
                    'tieu_de'  => $suCo->tieu_de  ?? '',
                    'noi_dung' => $suCo->noi_dung ?? '',
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('AI multimodal failed: ' . $response->body());
        } catch (\Throwable $e) {
            Log::warning('AI multimodal error: ' . $e->getMessage());
        }

        return $this->analyzeTextOnly($suCo, $suCo->noi_dung ?? '');
    }

    /**
     * Generic JSON POST to AI service with graceful fallback mock.
     */
    private function callJson(string $endpoint, array $payload): array
    {
        try {
            $response = Http::timeout(5)->post("{$this->aiUrl}{$endpoint}", $payload);
            if ($response->successful()) {
                return $response->json();
            }
            Log::warning("AI [{$endpoint}] error: " . $response->body());
        } catch (\Throwable $e) {
            Log::warning("AI [{$endpoint}] unreachable: " . $e->getMessage());
        }

        return $this->mockResponse($endpoint, $payload);
    }

    /**
     * Mock fallback — app keeps working when Python service is down.
     */
    private function mockResponse(string $endpoint, array $payload): array
    {
        return match ($endpoint) {
            '/analyze', '/analyze-with-image' => [
                'success'          => true,
                'id_su_co'         => $payload['id_su_co'] ?? 0,
                'source'           => 'mock',
                'loai_su_co'       => ['id_loai_su_co' => rand(1, 5), 'do_tin_cay' => 0.5],
                'muc_do_khan_cap'  => ['id_muc_do' => rand(1, 4),    'do_tin_cay' => 0.5],
            ],
            '/check-duplicate' => [
                'success'        => true,
                'la_trung_lap'   => false,
                'do_tuong_dong'  => 0.0,
                'id_su_co_trung' => null,
            ],
            default => ['success' => false],
        };
    }
}
