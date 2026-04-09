"""
SOS AI Fusion Logic (Hybrid: Groq Vision + Keyword AI + Local ML)
=================================================================
Pipeline:
  1. Image → Groq Vision → Vietnamese description
  2. Keyword-based classification from Groq description (PRIORITY)
  3. Spam detection with auto-reject threshold
  4. Local TF-IDF classification (fallback)
  5. Duplicate detection (text + GPS)
  6. Structured response
"""

from __future__ import annotations
import re
import unicodedata


def _remove_accents(text: str) -> str:
    """Remove Vietnamese diacritics for fuzzy matching."""
    nfkd = unicodedata.normalize('NFKD', text)
    return ''.join(c for c in nfkd if not unicodedata.combining(c))


SAFE_DEFAULT = {
    "is_spam":           0,
    "id_loai_su_co":     1,
    "id_muc_do":         1,
    "image_label":       "normal",
    "image_description": "",
    "is_duplicate":      0,
    "duplicate_with_id": None,
    "similarity_score":  0.0,
    "do_tin_cay":        0.0,
    "source":            "fallback",
}

# ─────────────────────────────────────────────
# KEYWORD-BASED SMART CLASSIFIER (v2 — weighted, no generic words)
# ─────────────────────────────────────────────
# Each keyword has a weight. Score = sum of weights for matched keywords.
# Higher weight = more specific/important keyword.
_LOAI_KEYWORDS = {
    2: {  # DB ID=2: Cháy
        "keywords": {
            # Vietnamese accented (Groq returns these)
            "cháy": 3, "lửa": 3, "đám cháy": 4, "bốc cháy": 4, "ngọn lửa": 4,
            "cháy rừng": 5, "cháy nhà": 5, "cháy lớn": 5,
            "khói": 2, "khói đen": 3, "bùng phát": 2,
            "nổ": 3, "chập điện": 3, "rò rỉ gas": 3, "thiêu": 2,
            # English
            "fire": 4, "flame": 3, "smoke": 2, "burning": 3, "explosion": 4, "blaze": 4,
            "dập lửa": 4, "chữa cháy": 4, "pccc": 3,
        },
        "label": "Cháy",
        "image_label": "fire",
        "min_score": 2,
    },
    1: {  # DB ID=1: Tai nạn giao thông
        "keywords": {
            "tai nạn": 3, "tai nạn giao thông": 5, "va chạm": 3,
            "xe máy": 2, "xe tải": 2, "ô tô": 2, "xe buýt": 2, "xe khách": 2,
            "lật xe": 4, "đụng xe": 4, "tông xe": 4, "đâm xe": 4,
            "kẹt xe": 2, "ùn tắc giao thông": 3, "cao tốc": 2,
            "giao thông": 2,
            # English
            "accident": 3, "crash": 3, "collision": 3, "traffic accident": 5,
        },
        "label": "Tai nạn giao thông",
        "image_label": "accident",
        "min_score": 3,
    },
    3: {  # DB ID=3: Cây đổ
        "keywords": {
            "cây đổ": 5, "cây gãy": 4, "cây bật gốc": 5,
            "cành cây": 3, "cây ngã": 4, "cây lớn đổ": 5,
            "gió lớn": 2, "giông": 2, "bão": 2,
            "fallen tree": 4, "tree fall": 4,
        },
        "label": "Cây đổ",
        "image_label": "tree",
        "min_score": 3,
    },
    4: {  # DB ID=4: Ngập nước
        "keywords": {
            "ngập": 3, "ngập nước": 4, "ngập lụt": 4, "ngập đường": 4,
            "lũ": 3, "lũ lụt": 4, "lũ quét": 5,
            "mưa lớn": 3, "mưa to": 3, "nước dâng": 3,
            "triều cường": 3, "sạt lở": 3, "sạt lở đất": 4,
            "thiên tai": 3, "động đất": 3, "sóng thần": 4,
            # English
            "flood": 4, "storm": 3, "landslide": 4,
        },
        "label": "Ngập nước",
        "image_label": "flood",
        "min_score": 2,
    },
    5: {  # DB ID=5: Khác (Y tế, An ninh, v.v.)
        "keywords": {
            "cấp cứu": 3, "sơ cứu": 3, "đột quỵ": 4, "bất tỉnh": 3,
            "chảy máu": 3, "bị thương": 3, "đuối nước": 4, "ngộ độc": 4,
            "trộm": 3, "trộm cắp": 4, "cướp": 3, "cướp giật": 4,
            "đánh nhau": 4, "xô xát": 3, "bạo lực": 3,
            "hung khí": 3, "gây rối": 3, "phá hoại": 3,
            "an ninh": 3, "tội phạm": 3,
        },
        "label": "Khác",
        "image_label": "other",
        "min_score": 2,
    },
}

_MUCDO_KEYWORDS = {
    4: {  # Khẩn cấp
        "keywords": {
            "chết": 4, "tử vong": 4, "nghiêm trọng": 3, "khẩn cấp": 3,
            "cháy lớn": 3, "bùng phát": 2, "sập": 3, "lũ lớn": 3,
            "mất kiểm soát": 3, "lan rộng": 3, "bất tỉnh": 2,
            "critical": 3, "fatal": 4, "severe": 3, "emergency": 3,
        },
        "label": "Khẩn cấp",
    },
    3: {  # Cao
        "keywords": {
            "nguy hiểm": 3, "thương nặng": 3, "hư hỏng nặng": 3,
            "đe dọa": 2, "thiệt hại lớn": 3, "cần hỗ trợ": 2,
            "dangerous": 3, "serious": 2, "major": 2,
        },
        "label": "Cao",
    },
    2: {  # Trung bình
        "keywords": {
            "nhẹ": 2, "nhỏ": 2, "ảnh hưởng": 2, "một phần": 2,
            "ùn tắc": 2, "chậm": 2, "hạn chế": 2,
            "moderate": 2, "minor": 2,
        },
        "label": "Trung bình",
    },
    1: {  # Thấp
        "keywords": {
            "tiếng ồn": 2, "mùi lạ": 2, "nghi ngờ": 2, "khả nghi": 2,
            "low": 2, "slight": 2, "noise": 2,
        },
        "label": "Thấp",
    },
}

_LOAI_TO_IMAGE_LABEL = {
    1: "accident", 2: "fire", 3: "tree", 4: "flood", 5: "other",
}


def _keyword_classify(text: str) -> dict:
    """
    Classify using weighted keyword matching. Longer compound keywords
    are checked first and score higher. Uses min_score thresholds.
    """
    text_lower = text.lower()
    text_no_accent = _remove_accents(text_lower)
    result = {}

    # Classify loai (type) — weighted scoring
    best_loai_score = 0
    best_loai_id = None
    for loai_id, config in _LOAI_KEYWORDS.items():
        score = 0
        keywords = config["keywords"]  # dict: {keyword: weight}
        min_score = config.get("min_score", 2)
        # Sort by length descending (match longer phrases first)
        for kw in sorted(keywords.keys(), key=len, reverse=True):
            weight = keywords[kw]
            kw_lower = kw.lower()
            kw_no_accent = _remove_accents(kw_lower)
            if kw_lower in text_lower or kw_no_accent in text_no_accent:
                score += weight
        if score >= min_score and score > best_loai_score:
            best_loai_score = score
            best_loai_id = loai_id

    if best_loai_id:
        conf = min(0.95, 0.4 + best_loai_score * 0.05)
        result["id_loai_su_co"] = best_loai_id
        result["ten_loai"] = _LOAI_KEYWORDS[best_loai_id]["label"]
        result["loai_confidence"] = conf
        print(f"  [Keywords] loai={best_loai_id} ({result['ten_loai']}) score={best_loai_score}")

    # Classify mucdo (severity) — weighted, high to low
    for mucdo_id in [4, 3, 2, 1]:
        config = _MUCDO_KEYWORDS[mucdo_id]
        keywords = config["keywords"]
        score = 0
        for kw in keywords:
            kw_lower = kw.lower()
            kw_no_accent = _remove_accents(kw_lower)
            if kw_lower in text_lower or kw_no_accent in text_no_accent:
                score += keywords[kw]
        if score >= 2:
            result["id_muc_do"] = mucdo_id
            result["ten_muc_do"] = config["label"]
            result["mucdo_confidence"] = min(0.9, 0.3 + score * 0.08)
            break

    if "id_loai_su_co" in result:
        return result
    return None


def _loai_to_label(id_loai: int) -> str:
    return _LOAI_TO_IMAGE_LABEL.get(id_loai, "normal")


# ─────────────────────────────────────────────
# GROQ IMAGE DESCRIPTION
# ─────────────────────────────────────────────
def _get_image_description(pil_image) -> str:
    try:
        from groq_client import analyze_image_with_groq, image_to_base64
        b64 = image_to_base64(pil_image)
        return analyze_image_with_groq(b64)
    except Exception as e:
        print(f"  [Fusion] Groq failed: {e}")
        return ""


# ─────────────────────────────────────────────
# CORE PIPELINE
# ─────────────────────────────────────────────
SPAM_AUTO_REJECT_THRESHOLD = 0.8
DUPLICATE_TEXT_THRESHOLD = 0.85
DUPLICATE_DISTANCE_M = 500


def run_full_pipeline(
    tieu_de: str,
    noi_dung: str = "",
    pil_image=None,
    *,
    lat: float = None,
    lng: float = None,
    existing_incidents: list = None,
    include_detail: bool = True,
) -> dict:
    try:
        return _pipeline(
            tieu_de, noi_dung, pil_image,
            lat=lat, lng=lng,
            existing_incidents=existing_incidents or [],
            include_detail=include_detail,
        )
    except Exception as exc:
        import traceback
        print(f"  [Fusion] Pipeline error: {exc}")
        traceback.print_exc()
        return {**SAFE_DEFAULT}


def _pipeline(
    tieu_de, noi_dung, pil_image,
    lat, lng, existing_incidents, include_detail,
) -> dict:
    import predict
    from spam_model import predict_spam

    tieu_de  = (tieu_de  or "").strip()
    noi_dung = (noi_dung or "").strip()

    # ── Step 1: Groq Vision ─────────────────────────────────────────
    image_description = ""
    if pil_image is not None:
        image_description = _get_image_description(pil_image)

    # ── Step 2: Combine text ────────────────────────────────────────
    full_text = f"{tieu_de} {noi_dung}"
    if image_description:
        full_text = f"{full_text} {image_description}"

    # ── Step 3: Spam detection ──────────────────────────────────────
    spam_result = predict_spam(tieu_de, f"{noi_dung} {image_description}".strip())
    is_spam = spam_result["is_spam"]
    spam_conf = spam_result["spam_confidence"]

    # Auto-reject if spam confidence > threshold
    auto_rejected = False
    if spam_conf >= SPAM_AUTO_REJECT_THRESHOLD:
        is_spam = 1
        auto_rejected = True

    if is_spam:
        result = {
            "is_spam":           1,
            "id_loai_su_co":     0,
            "id_muc_do":         0,
            "image_label":       "normal",
            "image_description": image_description,
            "is_duplicate":      0,
            "duplicate_with_id": None,
            "similarity_score":  0.0,
            "do_tin_cay":        round(spam_conf, 4),
            "source":            "spam",
            "auto_rejected":     auto_rejected,
        }
        if include_detail:
            result["detail"] = {"spam": spam_result}
        return result

    # ── Step 4: SMART Classification ────────────────────────────────
    # Priority: keyword-based > TF-IDF local model
    
    # 4a. Keyword-based from full text (Groq desc + user text)
    keyword_result = _keyword_classify(full_text)
    
    # 4b. TF-IDF local model as fallback
    text_loai = None
    text_mucdo = None
    if predict.models_loaded():
        text_loai = predict.predict_loai(tieu_de, f"{noi_dung} {image_description}".strip())
        text_mucdo = predict.predict_mucdo(tieu_de, f"{noi_dung} {image_description}".strip())

    # 4c. Decide final classification
    if keyword_result and "id_loai_su_co" in keyword_result:
        # Keyword match found → use it (more accurate than weak TF-IDF)
        id_loai = keyword_result["id_loai_su_co"]
        ten_loai = keyword_result["ten_loai"]
        loai_conf = keyword_result.get("loai_confidence", 0.7)
        
        id_mucdo = keyword_result.get("id_muc_do", 2)
        ten_mucdo = keyword_result.get("ten_muc_do", "Trung bình")
        mucdo_conf = keyword_result.get("mucdo_confidence", 0.5)
        
        source = "keyword_ai"
        confidence = loai_conf
        
        print(f"  [Fusion] Keyword classification: loai={id_loai} ({ten_loai}), mucdo={id_mucdo} ({ten_mucdo})")
    elif text_loai and text_mucdo:
        # Fallback to TF-IDF
        id_loai = text_loai.get("id_loai_su_co", 1)
        ten_loai = text_loai.get("ten_loai", "")
        id_mucdo = text_mucdo.get("id_muc_do", 1)
        ten_mucdo = text_mucdo.get("ten_muc_do", "")
        confidence = text_loai.get("do_tin_cay", 0.3)
        source = "text_ml"
        
        print(f"  [Fusion] TF-IDF fallback: loai={id_loai}, mucdo={id_mucdo}")
    else:
        id_loai = 1
        id_mucdo = 1
        confidence = 0.0
        source = "fallback"

    image_label = _loai_to_label(id_loai)

    # ── Step 5: Duplicate detection ─────────────────────────────────
    dup_result = {
        "is_duplicate": 0,
        "duplicate_with_id": None,
        "similarity_score": 0.0,
        "text_similarity": 0.0,
        "distance_m": None,
    }
    if existing_incidents and predict.models_loaded():
        try:
            dup_result = predict.check_duplicate(
                tieu_de, noi_dung, existing_incidents,
                threshold=DUPLICATE_TEXT_THRESHOLD,
                lat=lat, lng=lng,
            )
            if dup_result.get("is_duplicate"):
                print(f"  [Fusion] DUPLICATE detected! sim={dup_result.get('similarity_score')}, dist={dup_result.get('distance_m')}m")
        except Exception as e:
            print(f"  [Fusion] Duplicate check failed: {e}")

    # ── Build response ──────────────────────────────────────────────
    result = {
        "is_spam":           0,
        "id_loai_su_co":     id_loai,
        "id_muc_do":         id_mucdo,
        "image_label":       image_label,
        "image_description": image_description,
        "is_duplicate":      dup_result.get("is_duplicate", 0),
        "duplicate_with_id": dup_result.get("duplicate_with_id"),
        "similarity_score":  dup_result.get("similarity_score", 0.0),
        "do_tin_cay":        round(confidence, 4),
        "source":            source,
    }

    if include_detail:
        result["detail"] = {
            "spam":              spam_result,
            "keyword_match":     keyword_result,
            "text_loai":         text_loai,
            "text_mucdo":        text_mucdo,
            "image_description": image_description,
            "duplicate":         dup_result,
        }

    return result
