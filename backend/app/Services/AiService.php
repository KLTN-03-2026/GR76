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
            ->get(['id_su_co', 'tieu_de', 'noi_dung', 'dia_chi'])
            ->map(fn ($s) => [
                'id_su_co' => $s->id_su_co,
                'tieu_de'  => $s->tieu_de,
                'noi_dung' => $s->noi_dung ?? '',
                'dia_chi'  => $s->dia_chi ?? '',
            ])->toArray();

        $result = $this->callJson('/check-duplicate', [
            'id_su_co'  => $suCoId,
            'tieu_de'   => $suCo->tieu_de ?? '',
            'noi_dung'  => $suCo->noi_dung ?? '',
            'dia_chi'   => $suCo->dia_chi ?? '',
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
     * Check duplicate/spam BEFORE saving to DB.
     *
     * ARCHITECTURE:
     *   Layer 1 – PHP-native engine (similar_text + levenshtein + GPS haversine)
     *             Always runs. Independent of Python AI service.
     *   Layer 2 – Python AI (spam NLP + TF-IDF duplicate)
     *             Optional bonus. Falls back gracefully if Python is offline.
     */
    public function checkSpamAndDuplicatePre(array $data): array
    {
        $existing = SuCo::orderBy('thoi_gian_dang', 'desc')
            ->limit(100)
            ->get(['id_su_co', 'tieu_de', 'noi_dung', 'dia_chi', 'vi_do', 'kinh_do'])
            ->map(fn ($s) => [
                'id_su_co' => $s->id_su_co,
                'tieu_de'  => $s->tieu_de,
                'noi_dung' => $s->noi_dung ?? '',
                'dia_chi'  => $s->dia_chi  ?? '',
                'vi_do'    => $s->vi_do,
                'kinh_do'  => $s->kinh_do,
            ])->toArray();

        // ── LAYER 1: PHP-native duplicate check ───────────────────────
        $phpResult = $this->phpNativeDuplicateCheck($data, $existing);
        if ($phpResult['is_duplicate']) {
            return [
                'is_spam'           => 0,
                'is_duplicate'      => 1,
                'similarity_score'  => $phpResult['score'],
                'duplicate_with_id' => $phpResult['id_su_co_trung'],
                'message'           => $phpResult['reason'],
                'source'            => 'php_engine',
            ];
        }

        // ── LAYER 2: Python AI (optional) ─────────────────────────────
        try {
            $predictResult = $this->callJson('/predict-json', [
                'tieu_de'  => $data['tieu_de'],
                'noi_dung' => $data['noi_dung'] ?? '',
                'vi_do'    => $data['vi_do']    ?? null,
                'kinh_do'  => $data['kinh_do']  ?? null,
            ]);

            if ($predictResult['is_spam'] ?? 0) {
                return ['is_spam' => 1, 'is_duplicate' => 0,
                        'message' => 'Nội dung sự cố có dấu hiệu spam.', 'source' => 'python_ai'];
            }

            $dupResult = $this->callJson('/check-duplicate', [
                'id_su_co'  => 0,
                'tieu_de'   => $data['tieu_de'],
                'noi_dung'  => $data['noi_dung'] ?? '',
                'vi_do'     => $data['vi_do']    ?? null,
                'kinh_do'   => $data['kinh_do']  ?? null,
                'dia_chi'   => $data['dia_chi']  ?? null,
                'existing'  => $existing,
                'threshold' => 0.65,
            ]);

            if ($dupResult['la_trung_lap'] ?? false) {
                return [
                    'is_spam'           => 0,
                    'is_duplicate'      => 1,
                    'similarity_score'  => $dupResult['do_tuong_dong'] ?? 0,
                    'duplicate_with_id' => $dupResult['id_su_co_trung'] ?? null,
                    'source'            => 'python_ai',
                ];
            }
        } catch (\Throwable) {
            // Python offline — PHP result already passed, safe to proceed
        }

        return ['is_spam' => 0, 'is_duplicate' => 0, 'similarity_score' => 0,
                'duplicate_with_id' => null, 'source' => 'php_engine'];
    }

    /**
     * PHP-native duplicate detection engine.
     * Uses similar_text(), levenshtein(), GPS haversine — no Python needed.
     *
     * TRIGGERS a duplicate if ANY of these conditions is true:
     *   R1: Title similarity >= 88%  (very close title alone)
     *   R2: Title >= 65% AND Address >= 65%
     *   R3: Title >= 65% AND GPS within 200m
     *   R4: Address >= 90% AND Title >= 40%
     *   R5: GPS within 50m AND Title >= 40%
     */
    private function phpNativeDuplicateCheck(array $data, array $existing): array
    {
        $newTitle   = mb_strtolower(trim($data['tieu_de'] ?? ''));
        $newAddress = mb_strtolower(trim($data['dia_chi']  ?? ''));
        $newLat     = ($data['vi_do']   !== '' && $data['vi_do']   !== null) ? (float)$data['vi_do']   : null;
        $newLng     = ($data['kinh_do'] !== '' && $data['kinh_do'] !== null) ? (float)$data['kinh_do'] : null;

        $bestScore  = 0.0;
        $bestId     = null;
        $bestReason = '';

        foreach ($existing as $inc) {
            $incTitle   = mb_strtolower(trim($inc['tieu_de'] ?? ''));
            $incAddress = mb_strtolower(trim($inc['dia_chi']  ?? ''));
            $incLat     = ($inc['vi_do']   !== null) ? (float)$inc['vi_do']   : null;
            $incLng     = ($inc['kinh_do'] !== null) ? (float)$inc['kinh_do'] : null;

            if (empty($incTitle) || empty($newTitle)) continue;

            // ── Title similarity ──────────────────────────────────────
            similar_text($newTitle, $incTitle, $titlePct);
            $titleSim = $titlePct / 100.0;

            // Levenshtein for short titles (handles typos)
            $maxLen = max(mb_strlen($newTitle), mb_strlen($incTitle));
            if ($maxLen > 0 && $maxLen <= 255) {
                $lev      = levenshtein($newTitle, $incTitle);
                $levSim   = 1.0 - ($lev / $maxLen);
                $titleSim = max($titleSim, $levSim);
            }

            // ── Address similarity ────────────────────────────────────
            $addrSim = 0.0;
            if (!empty($newAddress) && !empty($incAddress)) {
                similar_text($newAddress, $incAddress, $addrPct);
                $addrSim = $addrPct / 100.0;
                // Substring containment (e.g. shorter address is part of longer)
                if (mb_strlen($newAddress) > 10 && mb_strpos($incAddress, $newAddress) !== false) {
                    $addrSim = max($addrSim, 0.95);
                }
                if (mb_strlen($incAddress) > 10 && mb_strpos($newAddress, $incAddress) !== false) {
                    $addrSim = max($addrSim, 0.95);
                }
            }

            // ── GPS proximity ──────────────────────────────────────────
            $distM    = null;
            $gps200   = false;
            $gps50    = false;
            if ($newLat !== null && $newLng !== null && $incLat !== null && $incLng !== null) {
                $distM  = $this->haversineMeters($newLat, $newLng, $incLat, $incLng);
                $gps200 = $distM <= 200;
                $gps50  = $distM <= 50;
            }

            // ── Apply rules ───────────────────────────────────────────
            $isDup  = false;
            $score  = 0.0;
            $reason = '';
            $pct    = round($titleSim * 100);

            if ($titleSim >= 0.88) {                                             // R1
                $isDup = true; $score = $titleSim;
                $reason = "Tiêu đề gần như giống hệt ({$pct}%)";
            } elseif ($titleSim >= 0.65 && $addrSim >= 0.65) {                  // R2
                $isDup = true; $score = ($titleSim + $addrSim) / 2.0;
                $reason = "Tiêu đề và địa chỉ đều tương tự";
            } elseif ($titleSim >= 0.65 && $gps200) {                           // R3
                $isDup = true; $score = ($titleSim + 0.9) / 2.0;
                $reason = "Tiêu đề tương tự và cùng khu vực (" . round($distM) . "m)";
            } elseif ($addrSim >= 0.90 && $titleSim >= 0.40) {                  // R4
                $isDup = true; $score = ($titleSim + $addrSim) / 2.0;
                $reason = "Cùng địa chỉ, nội dung liên quan";
            } elseif ($gps50 && $titleSim >= 0.40) {                            // R5
                $isDup = true; $score = ($titleSim + 0.95) / 2.0;
                $reason = "Cùng vị trí GPS (" . round($distM) . "m), tiêu đề liên quan";
            }

            if ($isDup && $score > $bestScore) {
                $bestScore = $score; $bestId = $inc['id_su_co']; $bestReason = $reason;
            }
        }

        return ['is_duplicate' => $bestId !== null,
                'id_su_co_trung' => $bestId,
                'score'          => round($bestScore, 4),
                'reason'         => $bestReason];
    }

    /**
     * Haversine distance between two GPS points, returns meters.
     */
    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R    = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $R * 2 * asin(sqrt($a));
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
