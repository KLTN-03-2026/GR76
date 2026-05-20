<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SuCo;
use App\Services\SuCoService;
use App\Services\AiService;
use App\Http\Requests\StoreSuCoRequest;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $suCoService;
    protected $aiService;

    public function __construct(SuCoService $suCoService, AiService $aiService)
    {
        $this->suCoService = $suCoService;
        $this->aiService   = $aiService;
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()->load('vaiTro')]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->only('ten', 'so_dien_thoai', 'ho_ten');
        
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        $user->update($data);
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
        $userId = $request->user()->id_nguoi_dung;

        // ── PRE-CHECK: Spam & Duplicate detection ────────────────────
        $checkPayload = [
            'tieu_de'       => $data['tieu_de'] ?? '',
            'noi_dung'      => $data['noi_dung'] ?? '',
            'dia_chi'       => $data['dia_chi']  ?? '',
            'vi_do'         => $data['vi_do']    ?? null,
            'kinh_do'       => $data['kinh_do']  ?? null,
            'id_nguoi_dung' => $userId,
        ];

        $check = $this->aiService->checkSpamAndDuplicatePre($checkPayload);

        // Nếu bị spam → vẫn lưu vào DB với flag is_spam=1 (admin xem được) và trả 422
        if ($check['is_spam'] ?? false) {
            // Xử lý ảnh trước (nếu có) để không mất file
            if ($request->hasFile('hinh_anh')) {
                $path = $request->file('hinh_anh')->store('su_co_images', 'public');
                $data['hinh_anh'] = '/storage/' . $path;
            }

            $source = $check['source'] ?? 'unknown';
            $reason = $check['message'] ?? 'Nội dung bị phát hiện spam.';

            // Lưu bản ghi spam để admin theo dõi
            $data['id_nguoi_dung'] = $userId;
            $data['trang_thai']    = 'rejected';
            $data['is_spam']       = 1;
            $data['spam_reason']   = $reason;
            $data['repeat_count']  = $check['repeat_count'] ?? 0;
            $data['id_loai_su_co'] = $data['id_loai_su_co'] ?? 5;
            $data['id_muc_do']     = $data['id_muc_do'] ?? 1;
            SuCo::create($data);

            // Xác định loại spam để UI hiển thị đúng thông điệp
            $spamType = match($source) {
                'repeat_location_spam' => 'repeat',
                'python_ai'            => 'content',
                default                => 'content',
            };

            return response()->json([
                'message'    => $reason,
                'spam_type'  => $spamType,
                'source'     => $source,
                'is_spam'    => true,
                'repeat_count' => $check['repeat_count'] ?? 0,
            ], 422);
        }

        // Nếu bị trùng lặp → không lưu, trả 422
        if ($check['is_duplicate'] ?? false) {
            return response()->json([
                'message'           => $check['message'] ?? 'Sự cố này đã được báo cáo trước đó. Cảm ơn bạn!',
                'is_duplicate'      => true,
                'duplicate_with_id' => $check['duplicate_with_id'] ?? null,
                'similarity_score'  => $check['similarity_score'] ?? 0,
            ], 422);
        }

        // ── HỢP LỆ: Lưu bình thường ───────────────────────────────
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
            if (empty($data['hinh_anh']) && count($paths) > 0) {
                $data['hinh_anh'] = $paths[0];
            }
        }

        $incident = $this->suCoService->createIncident($data, $userId);
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


        $data = $request->validate([
            'tieu_de'       => 'sometimes|string|min:5|max:200',
            'noi_dung'      => 'sometimes|string|min:10',
            'dia_chi'       => 'sometimes|string|min:5|max:255',
            'vi_do'         => 'sometimes|numeric',
            'kinh_do'       => 'sometimes|numeric',
            'id_loai_su_co' => 'sometimes|exists:loai_su_cos,id_loai_su_co',
            'id_muc_do'     => 'sometimes|exists:muc_do_khan_caps,id_muc_do',
        ]);

        if ($request->hasFile('hinh_anh')) {
            $path = $request->file('hinh_anh')->store('su_co_images', 'public');
            $data['hinh_anh'] = '/storage/' . $path;
        }

        if ($request->hasFile('hinh_anhs')) {
            $paths = [];
            foreach ($request->file('hinh_anhs') as $file) {
                if (count($paths) >= 5) break;
                $p = $file->store('su_co_images', 'public');
                $paths[] = '/storage/' . $p;
            }
            $data['hinh_anhs'] = $paths;
            if (empty($data['hinh_anh']) && empty($suCo->hinh_anh) && count($paths) > 0) {
                $data['hinh_anh'] = $paths[0];
            }
        }

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

