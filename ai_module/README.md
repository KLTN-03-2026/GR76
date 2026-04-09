# SOS AI Module

Thư mục AI riêng cho hệ thống SOS. Train được, tự cải thiện, tích hợp vào Laravel qua HTTP.

## Kiến trúc

```
User POST /api/su-co
    → Laravel SuCoService (tạo sự cố)
    → AiService::analyzeIncident() [HTTP]
        → Python FastAPI :8001/analyze
            → TF-IDF + Logistic Regression
            → Trả về: loai_su_co + muc_do_khan_cap + do_tin_cay
    → Lưu vào bảng phan_tich_ai
    → Broadcast NewIncidentCreated
```

## Cài đặt lần đầu

```bash
cd ai_module

# 1. Tạo môi trường Python
python -m venv venv
venv\Scripts\activate        # Windows
# source venv/bin/activate   # Linux/Mac

# 2. Cài thư viện
pip install -r requirements.txt

# 3. Train model
python train.py

# 4. Chạy AI server
python api.py
# → http://localhost:8001
# → Docs: http://localhost:8001/docs
```

## Cấu hình .env Laravel

```ini
AI_SERVICE_URL=http://localhost:8001
```

## Endpoints Python API

| Method | URL | Chức năng |
|--------|-----|-----------|
| GET | `/health` | Kiểm tra service |
| GET | `/model-info` | Thông tin model đã train |
| POST | `/analyze` | Phân loại sự cố + mức độ |
| POST | `/check-duplicate` | Phát hiện trùng lặp |
| POST | `/retrain` | Tự học thêm dữ liệu mới |
| GET | `/docs` | Swagger UI tự động |

## Tự train thêm dữ liệu

### Cách 1: Thêm vào file JSON rồi train lại
```bash
# Thêm dữ liệu vào data/training_data.json
python train.py
```

### Cách 2: Gọi API retrain (admin confirm incident)
```json
POST http://localhost:8001/retrain
{
  "samples": [
    {
      "tieu_de": "Cháy rừng ngoại ô",
      "noi_dung": "Lửa lan nhanh, nguy cơ cao",
      "id_loai_su_co": 1,
      "ten_loai": "Cháy nổ",
      "id_muc_do": 4,
      "ten_muc_do": "Khẩn cấp"
    }
  ]
}
```
→ Model tự cập nhật ngay trong background, không cần restart.

## Cấu trúc thư mục

```
ai_module/
├── api.py              ← FastAPI server (chạy cái này)
├── train.py            ← Script training
├── predict.py          ← Engine dự đoán
├── requirements.txt    ← Python packages
├── data/
│   └── training_data.json  ← Dữ liệu huấn luyện (thêm vào đây!)
└── models/             ← Model đã train (tự tạo sau train)
    ├── loai_su_co_model.pkl
    ├── muc_do_model.pkl
    └── tfidf_vectorizer.pkl
```

## Nhãn (Labels)

### Loại sự cố (`id_loai_su_co`)
| ID | Tên |
|----|-----|
| 1 | Cháy nổ |
| 2 | Tai nạn giao thông |
| 3 | Thiên tai |
| 4 | Y tế khẩn cấp |
| 5 | An ninh trật tự |

### Mức độ khẩn cấp (`id_muc_do`)
| ID | Tên |
|----|-----|
| 1 | Thấp |
| 2 | Trung bình |
| 3 | Nguy hiểm |
| 4 | Khẩn cấp |

## Lưu ý
- Nếu Python service chưa chạy, Laravel **vẫn hoạt động bình thường** (fallback mock)
- Càng nhiều dữ liệu training → AI càng chính xác
- Folder `models/` không commit lên git (tự train trên từng máy)
