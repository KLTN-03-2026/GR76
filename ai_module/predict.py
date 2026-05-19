"""
SOS AI Prediction Engine – Multimodal (Text + Image + Spam)
============================================================
predict_loai(tieu_de, noi_dung)
predict_mucdo(tieu_de, noi_dung)
predict_spam(tieu_de, noi_dung)      ← NEW
predict_from_image(pil_image)        → loai + mucdo from image only
predict_multimodal(tieu_de, noi_dung, pil_image) → fused prediction
check_duplicate(tieu_de, noi_dung, existing_incidents)
"""

import os
import joblib
import json
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity

MODELS_DIR = os.path.join(os.path.dirname(__file__), "models")

LOAI_PATH         = os.path.join(MODELS_DIR, "loai_su_co_model.pkl")
MUCDO_PATH        = os.path.join(MODELS_DIR, "muc_do_model.pkl")
TFIDF_PATH        = os.path.join(MODELS_DIR, "tfidf_vectorizer.pkl")
TEXTS_PATH        = os.path.join(MODELS_DIR, "training_texts.json")
FUSION_LOAI_PATH  = os.path.join(MODELS_DIR, "fusion_loai_model.pkl")
FUSION_MUCDO_PATH = os.path.join(MODELS_DIR, "fusion_mucdo_model.pkl")
IMAGE_CLF_PATH    = os.path.join(MODELS_DIR, "image_classifier.pkl")
IMAGE_MUCDO_PATH  = os.path.join(MODELS_DIR, "image_mucdo_model.pkl")

MUCDO_LABELS = {1: "Thấp", 2: "Trung bình", 3: "Nguy hiểm", 4: "Khẩn cấp"}
LOAI_LABELS  = {
    1: "Cháy nổ", 2: "Tai nạn giao thông",
    3: "Thiên tai", 4: "Y tế khẩn cấp", 5: "An ninh trật tự",
}

# Lazy caches
_cache = {}


def _preprocess(text: str) -> str:
    try:
        from underthesea import word_tokenize
        return word_tokenize(text, format="text").lower()
    except Exception:
        return text.lower()


def _load_text_models():
    if "tfidf" not in _cache:
        if not all(os.path.exists(p) for p in [TFIDF_PATH, LOAI_PATH, MUCDO_PATH]):
            raise FileNotFoundError("⚠️ Run: python train.py  first!")
        _cache["tfidf"]     = joblib.load(TFIDF_PATH)
        _cache["loai_clf"]  = joblib.load(LOAI_PATH)
        _cache["mucdo_clf"] = joblib.load(MUCDO_PATH)
        if os.path.exists(TEXTS_PATH):
            with open(TEXTS_PATH, encoding="utf-8") as f:
                _cache["known_texts"] = json.load(f)
    return _cache


def _load_fusion_models():
    result = {}
    if os.path.exists(FUSION_LOAI_PATH):
        result["fusion_loai"]  = joblib.load(FUSION_LOAI_PATH)
    if os.path.exists(FUSION_MUCDO_PATH):
        result["fusion_mucdo"] = joblib.load(FUSION_MUCDO_PATH)
    return result


def _classify(clf, vec, label_map: dict) -> dict:
    pred  = int(clf.predict(vec)[0])
    proba = float(np.max(clf.predict_proba(vec)[0]))
    return {
        "id":       pred,
        "label":    label_map.get(pred, "Không xác định"),
        "do_tin_cay": round(proba, 4),
    }


# ─────────────────────────────────────────────
# SPAM PREDICTION
# ─────────────────────────────────────────────
def predict_spam(tieu_de: str, noi_dung: str = "") -> dict:
    """Predict whether a report is spam. Delegates to spam_model module."""
    from spam_model import predict_spam as _spam_predict
    return _spam_predict(tieu_de, noi_dung)


def spam_models_loaded() -> bool:
    from spam_model import spam_models_loaded as _check
    return _check()


# ─────────────────────────────────────────────
# TEXT-ONLY PREDICTIONS
# ─────────────────────────────────────────────
def predict_loai(tieu_de: str, noi_dung: str = "") -> dict:
    c    = _load_text_models()
    text = _preprocess(f"{tieu_de} {noi_dung}")
    vec  = c["tfidf"].transform([text])
    r    = _classify(c["loai_clf"], vec, LOAI_LABELS)
    return {"id_loai_su_co": r["id"], "ten_loai": r["label"], "do_tin_cay": r["do_tin_cay"]}


def predict_mucdo(tieu_de: str, noi_dung: str = "") -> dict:
    c    = _load_text_models()
    text = _preprocess(f"{tieu_de} {noi_dung}")
    vec  = c["tfidf"].transform([text])
    r    = _classify(c["mucdo_clf"], vec, MUCDO_LABELS)
    return {"id_muc_do": r["id"], "ten_muc_do": r["label"], "do_tin_cay": r["do_tin_cay"]}


# ─────────────────────────────────────────────
# IMAGE-ONLY PREDICTIONS
# ─────────────────────────────────────────────
def predict_from_image(pil_image) -> dict:
    """Predict loai + mucdo from image only."""
    from image_model import (
        extract_features,
        predict_loai_zero_shot,
        predict_mucdo_zero_shot,
    )

    feats = extract_features(pil_image)
    mode  = "zero_shot"

    # Use fine-tuned classifier if available
    if os.path.exists(IMAGE_CLF_PATH):
        clf   = joblib.load(IMAGE_CLF_PATH)
        r     = _classify(clf, [feats], LOAI_LABELS)
        loai  = {"id_loai_su_co": r["id"], "ten_loai": r["label"], "do_tin_cay": r["do_tin_cay"]}
        mode  = "finetuned"
    else:
        loai = predict_loai_zero_shot(feats)

    if os.path.exists(IMAGE_MUCDO_PATH):
        clf   = joblib.load(IMAGE_MUCDO_PATH)
        r     = _classify(clf, [feats], MUCDO_LABELS)
        mucdo = {"id_muc_do": r["id"], "ten_muc_do": r["label"], "do_tin_cay": r["do_tin_cay"]}
    else:
        mucdo = predict_mucdo_zero_shot(feats)

    return {"loai_su_co": loai, "muc_do_khan_cap": mucdo, "mode": mode, "source": "image"}


# ─────────────────────────────────────────────
# MULTIMODAL PREDICTION (TEXT + IMAGE FUSED)
# ─────────────────────────────────────────────
def predict_multimodal(tieu_de: str, noi_dung: str, pil_image) -> dict:
    """
    Best quality prediction: combines text features + image features.
    Falls back gracefully: fusion → image-only → text-only.
    """
    from image_model import extract_features, build_fusion_features

    c         = _load_text_models()
    text      = _preprocess(f"{tieu_de} {noi_dung}")
    text_vec  = c["tfidf"].transform([text])
    img_feats = extract_features(pil_image)

    fusions   = _load_fusion_models()

    if "fusion_loai" in fusions and "fusion_mucdo" in fusions:
        # ── Best: fused text + image ────────────────────
        fusion_vec = build_fusion_features(text_vec, img_feats)
        r_loai   = _classify(fusions["fusion_loai"],  [fusion_vec], LOAI_LABELS)
        r_mucdo  = _classify(fusions["fusion_mucdo"], [fusion_vec], MUCDO_LABELS)
        mode = "fusion"
    else:
        # ── Fallback: average text + image predictions ──
        text_loai  = predict_loai(tieu_de, noi_dung)
        text_mucdo = predict_mucdo(tieu_de, noi_dung)
        img_pred   = predict_from_image(pil_image)

        # Combine by taking higher confidence
        t_conf = text_loai["do_tin_cay"]
        i_conf = img_pred["loai_su_co"]["do_tin_cay"]
        if t_conf >= i_conf:
            r_loai  = {"id": text_loai["id_loai_su_co"], "label": text_loai["ten_loai"],   "do_tin_cay": t_conf}
            r_mucdo = {"id": text_mucdo["id_muc_do"],    "label": text_mucdo["ten_muc_do"], "do_tin_cay": text_mucdo["do_tin_cay"]}
        else:
            r_loai  = {"id": img_pred["loai_su_co"]["id_loai_su_co"], "label": img_pred["loai_su_co"]["ten_loai"],              "do_tin_cay": i_conf}
            r_mucdo = {"id": img_pred["muc_do_khan_cap"]["id_muc_do"], "label": img_pred["muc_do_khan_cap"]["ten_muc_do"],       "do_tin_cay": img_pred["muc_do_khan_cap"]["do_tin_cay"]}
        mode = "ensemble"

    return {
        "loai_su_co": {
            "id_loai_su_co": r_loai["id"],
            "ten_loai":      r_loai["label"],
            "do_tin_cay":    r_loai["do_tin_cay"],
        },
        "muc_do_khan_cap": {
            "id_muc_do":  r_mucdo["id"],
            "ten_muc_do": r_mucdo["label"],
            "do_tin_cay": r_mucdo["do_tin_cay"],
        },
        "mode":   mode,
        "source": "text+image",
    }


# ─────────────────────────────────────────────
# DUPLICATE DETECTION (Enhanced with GPS)
# ─────────────────────────────────────────────
import math
import difflib

def _haversine_km(lat1, lon1, lat2, lon2) -> float:
    """Calculate distance between two GPS points in kilometers."""
    R = 6371  # Earth radius in km
    dlat = math.radians(lat2 - lat1)
    dlon = math.radians(lon2 - lon1)
    a = (math.sin(dlat / 2) ** 2 +
         math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) *
         math.sin(dlon / 2) ** 2)
    c = 2 * math.asin(math.sqrt(a))
    return R * c


def check_duplicate(
    tieu_de: str,
    noi_dung: str,
    existing_incidents: list[dict],
    threshold: float = 0.75,
    lat: float = None,
    lng: float = None,
    dia_chi: str = None,
) -> dict:
    """
    Enhanced duplicate detection: TF-IDF text similarity + GPS distance.

    Rules:
        - Text similarity > threshold alone → potential duplicate
        - If GPS available: similarity > 0.8 AND distance < 0.5km → definite duplicate
        - Combined score = weighted (0.7 * text_sim + 0.3 * geo_score)
    """
    c = _load_text_models()
    if not existing_incidents:
        return {"is_duplicate": 0, "duplicate_with_id": None, "similarity_score": 0.0}

    new_text  = _preprocess(f"{tieu_de} {noi_dung}")
    all_texts = [_preprocess(f"{i.get('tieu_de','')} {i.get('noi_dung','')}") for i in existing_incidents]

    vecs     = c["tfidf"].transform([new_text] + all_texts)
    sims     = cosine_similarity(vecs[0], vecs[1:])[0]

    # Compute combined scores (text + geo proximity)
    scores = []
    for idx, inc in enumerate(existing_incidents):
        text_sim_tfidf = float(sims[idx])
        # Fallback string similarity for OOV words and exact phrasing
        text_sim_seq = difflib.SequenceMatcher(None, new_text, all_texts[idx]).ratio()
        text_sim = max(text_sim_tfidf, text_sim_seq)

        geo_score = 0.0
        distance_m = None

        # GPS-based scoring
        if lat is not None and lng is not None:
            inc_lat = inc.get("vi_do") or inc.get("lat")
            inc_lng = inc.get("kinh_do") or inc.get("lng")
            if inc_lat is not None and inc_lng is not None:
                try:
                    dist_km = _haversine_km(lat, lng, float(inc_lat), float(inc_lng))
                    distance_m = dist_km * 1000
                    # Geo score: 1.0 if same spot, 0.0 if > 2km
                    geo_score = max(0.0, 1.0 - (dist_km / 2.0))
                except (ValueError, TypeError):
                    pass

        # Address-based scoring with robust matching
        address_match = False
        addr_sim = 0.0
        if dia_chi and inc.get("dia_chi"):
            addr1 = dia_chi.lower().strip()
            addr2 = str(inc.get("dia_chi")).lower().strip()
            
            addr_sim = difflib.SequenceMatcher(None, addr1, addr2).ratio()
            
            # Match if strings are very similar OR one contains another
            if addr_sim > 0.85 or (len(addr1) > 10 and addr1 in addr2) or (len(addr2) > 10 and addr2 in addr1):
                address_match = True

        # Combined score
        if distance_m is not None:
            combined = 0.7 * text_sim + 0.3 * geo_score
        else:
            combined = text_sim
            
        # Boost score if address is similar and text is somewhat related
        if address_match:
            if distance_m is None:
                distance_m = 0.0
            # Since address matches closely, lower the text_sim requirement
            if text_sim > 0.2:
                combined = max(combined, threshold + 0.1)

        scores.append({
            "idx": idx,
            "text_sim": text_sim,
            "geo_score": geo_score,
            "distance_m": distance_m,
            "combined": combined,
        })

    # Find best match
    best = max(scores, key=lambda x: x["combined"])
    best_inc = existing_incidents[best["idx"]]

    # Determine if duplicate
    is_dup = False
    if best["distance_m"] is not None:
        # GPS available → strict: similarity > 0.8 AND distance < 500m
        if best["text_sim"] >= 0.8 and best["distance_m"] < 500:
            is_dup = True
        elif best["combined"] >= threshold:
            is_dup = True
    else:
        # No GPS → text-only threshold
        is_dup = best["text_sim"] >= threshold

    return {
        "is_duplicate":     1 if is_dup else 0,
        "duplicate_with_id": best_inc.get("id_su_co") if is_dup else None,
        "similarity_score": round(best["combined"], 4),
        "text_similarity":  round(best["text_sim"], 4),
        "distance_m":       round(best["distance_m"], 1) if best["distance_m"] is not None else None,
        # Legacy field for backward compatibility
        "la_trung_lap":     is_dup,
        "do_tuong_dong":    round(best["combined"], 4),
        "id_su_co_trung":   best_inc.get("id_su_co") if is_dup else None,
    }


def models_loaded() -> bool:
    return all(os.path.exists(p) for p in [TFIDF_PATH, LOAI_PATH, MUCDO_PATH])


def image_models_loaded() -> bool:
    from image_model import PROTOTYPE_PATH, IMAGE_MODEL_PATH
    return os.path.exists(PROTOTYPE_PATH) or os.path.exists(IMAGE_MODEL_PATH)
