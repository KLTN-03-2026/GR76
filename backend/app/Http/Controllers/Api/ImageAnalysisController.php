<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageAnalysisController extends Controller
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL   = 'meta-llama/llama-4-scout-17b-16e-instruct';

    // Mapping từ từ khóa → id_loai_su_co (khớp với DB: 1=tai nạn, 2=cháy, 3=cây đổ, 4=ngập nước, 5=khác)
    private const CATEGORY_KEYWORDS = [
        1 => ['tai nạn', 'giao thông', 'xe', 'đụng', 'va chạm', 'accident', 'ô tô', 'xe máy', 'xe tải'],
        2 => ['cháy', 'lửa', 'khói', 'hỏa hoạn', 'bốc cháy', 'đám cháy', 'fire', 'smoke'],
        3 => ['cây', 'cây đổ', 'cành', 'gãy', 'đổ cây', 'tree'],
        4 => ['ngập', 'lũ', 'nước', 'flood', 'ngập lụt', 'mưa lớn', 'ngập úng'],
    ];

    // Mapping mức độ → id_muc_do (khớp với DB)
    private const URGENCY_MAP = [
        'khẩn cấp' => 4,
        'cao'       => 3,
        'trung bình'=> 2,
        'thấp'      => 1,
    ];

    /**
     * Phân tích ảnh sự cố bằng Groq Vision API.
     * POST /api/analyze-image
     *
     * Body JSON: { "image_base64": "...", "tieu_de": "..." }
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'image_base64' => 'required|string',
        ]);

        $apiKey = config('services.groq.api_key') ?: env('GROQ_API_KEY');

        if (!$apiKey) {
            return response()->json([
                'success'           => false,
                'image_description' => '',
                'id_loai_su_co'     => null,
                'id_muc_do'         => null,
                'error'             => 'GROQ_API_KEY chưa được cấu hình.',
            ], 500);
        }

        $tieu_de    = $request->input('tieu_de', 'Sự cố');
        $base64     = $request->input('image_base64');

        // Đảm bảo có data URI prefix
        if (!str_starts_with($base64, 'data:')) {
            $base64 = "data:image/jpeg;base64,{$base64}";
        }

        $systemPrompt = <<<EOT
Bạn là chuyên gia phân tích sự cố khẩn cấp tại Việt Nam.
Khi nhận được hình ảnh sự cố, hãy phân tích và trả lời THEO ĐÚNG FORMAT sau (không thêm bớt gì khác):

LOAI: [tai_nan_giao_thong | chay_no | cay_do | ngap_nuoc | khac]
MUC_DO: [thap | trung_binh | cao | khan_cap]
MO_TA: [Mô tả 2-3 câu bằng tiếng Việt, cụ thể, chính xác những gì nhìn thấy trong ảnh]

Quy tắc phân loại:
- tai_nan_giao_thong: xe cộ va chạm, tai nạn đường bộ
- chay_no: lửa, khói, hỏa hoạn  
- cay_do: cây ngã đổ, cành gãy
- ngap_nuoc: đường ngập, lũ lụt, nước dâng
- khac: y tế, an ninh, sụt lún, hoặc không xác định được

Mức độ:
- thap: ít nguy hiểm, không có người bị thương
- trung_binh: có thể gây hại, cần xử lý sớm
- cao: nguy hiểm, có người bị thương hoặc thiệt hại lớn
- khan_cap: nguy hiểm ngay lập tức, đe dọa tính mạng
EOT;

        $payload = [
            'model'    => self::GROQ_MODEL,
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => $systemPrompt,
                ],
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'      => 'image_url',
                            'image_url' => ['url' => $base64, 'detail' => 'high'],
                        ],
                        [
                            'type' => 'text',
                            'text' => "Phân tích hình ảnh sự cố này" . ($tieu_de !== 'Sự cố' ? " (tiêu đề: {$tieu_de})" : '') . ". Trả lời theo đúng format đã quy định.",
                        ],
                    ],
                ],
            ],
            'max_tokens'  => 350,
            'temperature' => 0.1,
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post(self::GROQ_API_URL, $payload);

            if (!$response->successful()) {
                $status = $response->status();
                Log::warning('[Groq] API error: ' . $status . ' - ' . $response->body());

                $error = match (true) {
                    $status === 401 => 'GROQ_API_KEY không hợp lệ hoặc đã hết hạn. Vui lòng cấp key mới tại https://console.groq.com/keys và cập nhật vào backend/.env',
                    $status === 429 => 'Groq API quá tải (rate limit). Vui lòng thử lại sau vài giây.',
                    $status >= 500  => 'Groq API đang bảo trì. Vui lòng thử lại sau.',
                    default         => "Groq API lỗi ({$status})",
                };

                return response()->json([
                    'success'           => false,
                    'image_description' => '',
                    'id_loai_su_co'     => null,
                    'id_muc_do'         => null,
                    'error'             => $error,
                    'error_code'        => $status,
                ]);
            }

            $content = $response->json('choices.0.message.content', '');
            Log::info('[Groq] Response: ' . $content);

            // Parse structured response
            [$description, $idLoai, $idMucDo] = $this->parseGroqResponse($content);

            return response()->json([
                'success'           => true,
                'image_description' => $description,
                'id_loai_su_co'     => $idLoai,
                'id_muc_do'         => $idMucDo,
                'raw_response'      => $content,
            ]);

        } catch (\Throwable $e) {
            Log::error('[Groq] Exception: ' . $e->getMessage());
            return response()->json([
                'success'           => false,
                'image_description' => '',
                'id_loai_su_co'     => null,
                'id_muc_do'         => null,
                'error'             => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse Groq structured response:
     * LOAI: ...
     * MUC_DO: ...
     * MO_TA: ...
     */
    private function parseGroqResponse(string $content): array
    {
        $description = '';
        $idLoai      = null;
        $idMucDo     = null;

        // Extract MO_TA
        if (preg_match('/MO_TA:\s*(.+?)(?:\n|$)/is', $content, $m)) {
            $description = trim($m[1]);
        } else {
            // Fallback: nếu format không đúng, lấy toàn bộ content
            $description = trim(preg_replace('/^(LOAI|MUC_DO):.+$/m', '', $content));
            $description = trim($description);
        }

        // Extract LOAI
        if (preg_match('/LOAI:\s*(\w+)/i', $content, $m)) {
            $loaiCode = strtolower(trim($m[1]));
            $idLoai = match($loaiCode) {
                'tai_nan_giao_thong', 'tai_nan' => 1,
                'chay_no', 'chay'               => 2,
                'cay_do', 'cay'                 => 3,
                'ngap_nuoc', 'ngap'             => 4,
                default                          => 5, // khác
            };
        }

        // Fallback: detect từ mô tả nếu chưa có LOAI
        if (!$idLoai && $description) {
            $lower = mb_strtolower($description);
            foreach (self::CATEGORY_KEYWORDS as $id => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($lower, $kw)) {
                        $idLoai = $id;
                        break 2;
                    }
                }
            }
            if (!$idLoai) $idLoai = 5;
        }

        // Extract MUC_DO
        if (preg_match('/MUC_DO:\s*(\w+)/i', $content, $m)) {
            $mucDoCode = strtolower(trim($m[1]));
            $idMucDo = match($mucDoCode) {
                'thap'         => 1,
                'trung_binh'   => 2,
                'cao'          => 3,
                'khan_cap'     => 4,
                default        => 2,
            };
        }

        // Fallback mức độ từ mô tả
        if (!$idMucDo && $description) {
            $lower = mb_strtolower($description);
            foreach (self::URGENCY_MAP as $keyword => $id) {
                if (str_contains($lower, $keyword)) {
                    $idMucDo = $id;
                    break;
                }
            }
            if (!$idMucDo) $idMucDo = 2;
        }

        return [$description ?: 'Không xác định được nội dung sự cố từ hình ảnh.', $idLoai ?? 5, $idMucDo ?? 2];
    }
}
