"""
SOS AI Fusion Logic
====================
Orchestrates the full prediction pipeline:

  1. Spam detection  (short-circuit if spam)
  2. Text classification  (loai + mucdo)
  3. Image analysis  (optional)
  4. Confidence-based fusion of text + image
  5. Structured JSON response with image_label

Response format (matches Laravel AiService expectations):
  {
    "is_spam":        0 | 1,
    "id_loai_su_co":  int,
    "id_muc_do":      int,
    "image_label":    str,     # "fire" | "accident" | "flood" | "medical" | "security" | "normal"
    "do_tin_cay":     float,
    "source":         str,     # "text" | "image" | "fusion" | "spam" | "fallback"
    "detail": { ... }          # optional breakdown
  }
"""

from __future__ import annotations

# Safe default returned when everything fails
SAFE_DEFAULT = {
    "is_spam":       0,
    "id_loai_su_co": 1,
    "id_muc_do":     1,
    "image_label":   "normal",
    "do_tin_cay":    0.0,
    "source":        "fallback",
}

IMAGE_CONFIDENCE_THRESHOLD = 0.60  # use image result if confidence above this

# Map id_loai_su_co → human-readable image_label for Laravel
_LOAI_TO_IMAGE_LABEL: dict[int, str] = {
    1: "fire",
    2: "accident",
    3: "flood",
    4: "medical",
    5: "security",
}


def _loai_to_label(id_loai: int) -> str:
    return _LOAI_TO_IMAGE_LABEL.get(id_loai, "normal")


# ─────────────────────────────────────────────
# CORE FUSION FUNCTION
# ─────────────────────────────────────────────
def run_full_pipeline(
    tieu_de: str,
    noi_dung: str = "",
    pil_image=None,
    *,
    image_threshold: float = IMAGE_CONFIDENCE_THRESHOLD,
    include_detail: bool = True,
) -> dict:
    """
    Run the complete prediction pipeline.

    Args:
        tieu_de:          Incident title (required)
        noi_dung:         Incident body text (optional)
        pil_image:        PIL.Image.Image object (optional)
        image_threshold:  Min image confidence to use image result (default 0.60)
        include_detail:   Include detailed breakdown in 'detail' key

    Returns:
        Structured prediction dict.
    """
    try:
        return _pipeline(tieu_de, noi_dung, pil_image, image_threshold, include_detail)
    except Exception as exc:
        import traceback
        print(f"  ⚠️ Fusion pipeline error: {exc}")
        traceback.print_exc()
        return {**SAFE_DEFAULT}


def _pipeline(tieu_de, noi_dung, pil_image, image_threshold, include_detail) -> dict:
    import predict
    from spam_model import predict_spam

    tieu_de  = (tieu_de  or "").strip()
    noi_dung = (noi_dung or "").strip()

    # ── Step 1: Spam detection ──────────────────────────────────────────
    spam_result = predict_spam(tieu_de, noi_dung)
    is_spam     = spam_result["is_spam"]

    if is_spam:
        result = {
            "is_spam":       1,
            "id_loai_su_co": 0,   # undefined — rejected
            "id_muc_do":     0,
            "image_label":   "normal",
            "do_tin_cay":    round(spam_result["spam_confidence"], 4),
            "source":        "spam",
        }
        if include_detail:
            result["detail"] = {"spam": spam_result}
        return result

    # ── Step 2: Text classification ─────────────────────────────────────
    text_loai  = None
    text_mucdo = None
    if predict.models_loaded():
        text_loai  = predict.predict_loai(tieu_de, noi_dung)
        text_mucdo = predict.predict_mucdo(tieu_de, noi_dung)

    # ── Step 3: Image analysis (optional) ───────────────────────────────
    img_result = None
    if pil_image is not None:
        try:
            img_result = predict.predict_from_image(pil_image)
        except Exception as e:
            print(f"  ⚠️ Image analysis failed (fallback to text): {e}")

    # ── Step 4: Fusion decision ──────────────────────────────────────────
    id_loai, id_mucdo, confidence, source, image_label = _fuse(
        text_loai, text_mucdo, img_result, image_threshold
    )

    result = {
        "is_spam":       0,
        "id_loai_su_co": id_loai,
        "id_muc_do":     id_mucdo,
        "image_label":   image_label,
        "do_tin_cay":    round(confidence, 4),
        "source":        source,
    }

    if include_detail:
        result["detail"] = {
            "spam":       spam_result,
            "text_loai":  text_loai,
            "text_mucdo": text_mucdo,
            "image":      img_result,
        }

    return result


def _fuse(text_loai, text_mucdo, img_result, threshold) -> tuple:
    """
    Decide final loai + mucdo using confidence-based fusion.

    Returns (id_loai, id_mucdo, confidence, source, image_label)
    """
    has_text  = text_loai is not None and text_mucdo is not None
    has_image = img_result is not None

    if not has_text and not has_image:
        return (SAFE_DEFAULT["id_loai_su_co"], SAFE_DEFAULT["id_muc_do"],
                0.0, "fallback", "normal")

    # Pure text fallback
    if not has_image:
        confidence = text_loai.get("do_tin_cay", 0.0)
        id_loai    = text_loai.get("id_loai_su_co", 1)
        id_mucdo   = text_mucdo.get("id_muc_do", 1)
        return id_loai, id_mucdo, confidence, "text", _loai_to_label(id_loai)

    # Pure image (no text model)
    if not has_text:
        loai_block  = img_result.get("loai_su_co", {})
        mucdo_block = img_result.get("muc_do_khan_cap", {})
        id_loai  = loai_block.get("id_loai_su_co", 1)
        id_mucdo = mucdo_block.get("id_muc_do", 1)
        confidence = loai_block.get("do_tin_cay", 0.0)
        return id_loai, id_mucdo, confidence, "image", _loai_to_label(id_loai)

    # Both text + image — confidence-based selection
    text_conf = text_loai.get("do_tin_cay", 0.0)
    loai_block = img_result.get("loai_su_co", {})
    mucdo_block = img_result.get("muc_do_khan_cap", {})
    img_conf   = loai_block.get("do_tin_cay", 0.0)

    if img_conf >= threshold:
        # Image is confident → prefer image loai, max mucdo between both
        id_loai  = loai_block.get("id_loai_su_co", text_loai.get("id_loai_su_co", 1))
        id_mucdo = max(
            mucdo_block.get("id_muc_do", 1),
            text_mucdo.get("id_muc_do", 1),
        )
        confidence = (img_conf + text_conf) / 2
        source = "fusion"
    else:
        # Text is more reliable
        id_loai  = text_loai.get("id_loai_su_co", 1)
        id_mucdo = text_mucdo.get("id_muc_do", 1)
        confidence = text_conf
        source = "text"

    # image_label always reflects actual image if available and confident enough
    if img_conf >= threshold:
        image_label = _loai_to_label(loai_block.get("id_loai_su_co", -1))
    else:
        image_label = _loai_to_label(id_loai) if img_result else "normal"

    return id_loai, id_mucdo, confidence, source, image_label
