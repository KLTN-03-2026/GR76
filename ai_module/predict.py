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
# DUPLICATE DETECTION
# ─────────────────────────────────────────────
def check_duplicate(
    tieu_de: str,
    noi_dung: str,
    existing_incidents: list[dict],
    threshold: float = 0.75,
) -> dict:
    c = _load_text_models()
    if not existing_incidents:
        return {"la_trung_lap": False, "do_tuong_dong": 0.0, "id_su_co_trung": None}

    new_text  = _preprocess(f"{tieu_de} {noi_dung}")
    all_texts = [_preprocess(f"{i.get('tieu_de','')} {i.get('noi_dung','')}") for i in existing_incidents]

    vecs     = c["tfidf"].transform([new_text] + all_texts)
    sims     = cosine_similarity(vecs[0], vecs[1:])[0]
    best_idx = int(np.argmax(sims))
    best_sim = float(sims[best_idx])
    is_dup   = best_sim >= threshold

    return {
        "la_trung_lap":   is_dup,
        "do_tuong_dong":  round(best_sim, 4),
        "id_su_co_trung": existing_incidents[best_idx]["id_su_co"] if is_dup else None,
    }


def models_loaded() -> bool:
    return all(os.path.exists(p) for p in [TFIDF_PATH, LOAI_PATH, MUCDO_PATH])


def image_models_loaded() -> bool:
    from image_model import PROTOTYPE_PATH, IMAGE_MODEL_PATH
    return os.path.exists(PROTOTYPE_PATH) or os.path.exists(IMAGE_MODEL_PATH)
