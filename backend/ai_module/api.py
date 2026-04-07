"""
SOS AI Microservice – FastAPI Server (Multimodal + Spam Detection)
===================================================================
Endpoints:
  GET   /health
  GET   /model-info
  GET   /logs/stats                 ← Prediction log statistics
  GET   /logs/recent                ← Last N predictions
  POST  /predict                    ← NEW unified endpoint (text + optional image)
  POST  /analyze                    ← Text only (legacy, preserved)
  POST  /analyze-with-image         ← Multimodal (legacy, preserved)
  POST  /predict-image              ← Image only
  POST  /check-duplicate            ← Duplicate detection
  POST  /retrain                    ← Re-train text + spam models in background
  POST  /retrain-images             ← Re-train image models with new labeled images
  POST  /feedback                   ← Log misclassified samples for retraining
  GET   /docs                       ← Swagger UI

Run:
    python api.py
"""

import os
import io
import json
import base64
import uvicorn
from datetime import datetime
from fastapi import FastAPI, HTTPException, BackgroundTasks, UploadFile, File, Form, Query
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import Optional
from PIL import Image

import predict
import train as trainer

app = FastAPI(
    title="SOS AI Service",
    description="🚨 Custom Multimodal AI for SOS Incident Management (Text + Image + Spam Detection)",
    version="3.0.0",
)
app.add_middleware(CORSMiddleware, allow_origins=["*"], allow_methods=["*"], allow_headers=["*"])


# ─────────────────────────────────────────────
# SCHEMAS
# ─────────────────────────────────────────────
class AnalyzeRequest(BaseModel):
    id_su_co: int
    tieu_de:  str = Field(..., min_length=2)
    noi_dung: str = ""

class PredictJsonRequest(BaseModel):
    """Unified /predict endpoint – JSON body variant."""
    tieu_de:      str = Field(..., min_length=1)
    noi_dung:     str = ""
    image_base64: Optional[str] = None   # Base64-encoded image (optional)

class IncidentRef(BaseModel):
    id_su_co: int
    tieu_de:  str
    noi_dung: str = ""

class DuplicateRequest(BaseModel):
    id_su_co:  int
    tieu_de:   str
    noi_dung:  str = ""
    existing:  list[IncidentRef] = []
    threshold: float = Field(default=0.75, ge=0.0, le=1.0)

class RetrainRequest(BaseModel):
    samples: list[dict]

class ImageLabelRequest(BaseModel):
    """Label an image by its URL for retraining."""
    image_url:      str
    id_loai_su_co:  int
    id_muc_do:      int
    tieu_de:        str = ""
    noi_dung:       str = ""

class FeedbackRequest(BaseModel):
    """Report a misclassified prediction for retraining."""
    tieu_de:         str
    noi_dung:        str = ""
    has_image:       bool = False
    expected_loai:   Optional[int] = None
    expected_mucdo:  Optional[int] = None
    expected_spam:   Optional[int] = None
    got_loai:        Optional[int] = None
    got_mucdo:       Optional[int] = None
    got_spam:        Optional[int] = None
    reason:          str = ""


# ─────────────────────────────────────────────
# HELPERS
# ─────────────────────────────────────────────
def _require_text_models():
    if not predict.models_loaded():
        raise HTTPException(503, detail="Text models not trained. Run: python train.py")

def _require_image_models():
    if not predict.image_models_loaded():
        raise HTTPException(503, detail="Image models not trained. Run: python train.py --images")

def _read_upload(upload: UploadFile) -> Image.Image:
    try:
        data = upload.file.read()
        return Image.open(io.BytesIO(data)).convert("RGB")
    except Exception as e:
        raise HTTPException(400, detail=f"Cannot read image: {e}")

def _decode_base64_image(b64: str) -> Optional[Image.Image]:
    """Decode a base64 image string to PIL Image. Returns None on failure."""
    try:
        # Strip data-URI prefix if present: data:image/jpeg;base64,...
        if "," in b64:
            b64 = b64.split(",", 1)[1]
        raw = base64.b64decode(b64)
        return Image.open(io.BytesIO(raw)).convert("RGB")
    except Exception:
        return None

# Safe default for complete AI failure
_SAFE_DEFAULT = {
    "is_spam":       0,
    "id_loai_su_co": 1,
    "id_muc_do":     1,
    "image_label":   "normal",
    "do_tin_cay":    0.0,
    "source":        "fallback",
}


# ─────────────────────────────────────────────
# ROUTES — SYSTEM
# ─────────────────────────────────────────────
@app.get("/health", tags=["System"])
def health():
    return {
        "status":              "ok",
        "text_models_ready":   predict.models_loaded(),
        "spam_models_ready":   predict.spam_models_loaded(),
        "image_models_ready":  predict.image_models_loaded(),
        "timestamp":           datetime.now().isoformat(),
    }


@app.get("/model-info", tags=["System"])
def model_info():
    models_dir = os.path.join(os.path.dirname(__file__), "models")
    info = {
        "text_models":  predict.models_loaded(),
        "spam_models":  predict.spam_models_loaded(),
        "image_models": predict.image_models_loaded(),
        "files": [],
    }
    if os.path.exists(models_dir):
        for f in os.listdir(models_dir):
            fp = os.path.join(models_dir, f)
            if not f.startswith("."):
                info["files"].append({
                    "name":     f,
                    "size_kb":  round(os.path.getsize(fp) / 1024, 2),
                    "modified": datetime.fromtimestamp(os.path.getmtime(fp)).isoformat(),
                })
    return info


# ─────────────────────────────────────────────
# ROUTES — LOGGING
# ─────────────────────────────────────────────
@app.get("/logs/stats", tags=["Logs"])
def logs_stats():
    """Return basic statistics from prediction + misclassification logs."""
    from logger import get_stats
    return get_stats()


@app.get("/logs/recent", tags=["Logs"])
def logs_recent(n: int = Query(default=20, ge=1, le=200)):
    """Return the last N predictions from the prediction log."""
    from logger import get_recent_predictions
    return {"predictions": get_recent_predictions(n), "count": n}


@app.post("/feedback", tags=["Logs"])
def feedback(req: FeedbackRequest):
    """
    Submit feedback on a wrong prediction.
    Logs to misclassified.jsonl for future retraining.
    """
    from logger import log_misclassified
    log_misclassified(
        request_payload={
            "tieu_de":   req.tieu_de,
            "noi_dung":  req.noi_dung,
            "has_image": req.has_image,
        },
        expected={
            "id_loai_su_co": req.expected_loai,
            "id_muc_do":     req.expected_mucdo,
            "is_spam":       req.expected_spam,
        },
        got={
            "id_loai_su_co": req.got_loai,
            "id_muc_do":     req.got_mucdo,
            "is_spam":       req.got_spam,
        },
        reason=req.reason,
    )
    return {"success": True, "message": "Feedback logged for retraining.", "logged_at": datetime.now().isoformat()}


# ─────────────────────────────────────────────
# ROUTES — UNIFIED PREDICT (NEW)
# ─────────────────────────────────────────────
@app.post("/predict", tags=["Unified AI"])
async def predict_unified(
    text:     Optional[str]        = Form(None,  description="Incident text (tieu_de + space + noi_dung)"),
    tieu_de:  Optional[str]        = Form(None,  description="Incident title"),
    noi_dung: Optional[str]        = Form("",   description="Incident body"),
    image:    Optional[UploadFile] = File(None,  description="Optional incident image (jpg/png)"),
):
    """
    🌟 Unified multi-modal prediction endpoint.

    Accepts multipart/form-data:
      - text    = combined text string (or use tieu_de + noi_dung)
      - image   = optional image file

    Returns:
      {
        "is_spam":        0 | 1,
        "id_loai_su_co":  int,
        "id_muc_do":      int,
        "image_label":    str,
        "do_tin_cay":     float,
        "source":         str
      }
    """
    from fusion_logic import run_full_pipeline
    from logger import log_prediction

    # Resolve title/body
    if text and not tieu_de:
        # Split on first sentence or use all as title
        parts    = text.strip().split(" ", 4)
        tieu_de  = " ".join(parts[:4]) if len(parts) >= 4 else text
        noi_dung = text
    tieu_de  = (tieu_de  or "").strip()
    noi_dung = (noi_dung or "").strip()

    if not tieu_de:
        return {**_SAFE_DEFAULT, "source": "fallback", "error": "No text provided"}

    # Read image if provided
    pil_image = None
    if image and image.filename:
        try:
            pil_image = _read_upload(image)
        except Exception:
            pil_image = None  # fallback to text only

    result = run_full_pipeline(tieu_de, noi_dung, pil_image, include_detail=False)

    # Log prediction (async-safe, non-blocking)
    log_prediction(
        {"tieu_de": tieu_de, "noi_dung": noi_dung, "has_image": pil_image is not None},
        result,
    )

    return {**result, "analyzed_at": datetime.now().isoformat()}


@app.post("/predict-json", tags=["Unified AI"])
async def predict_unified_json(req: PredictJsonRequest):
    """
    🌟 Unified prediction – JSON body variant.

    Accepts JSON:
      {
        "tieu_de":      "...",
        "noi_dung":     "...",
        "image_base64": "..."   (optional)
      }

    Returns same format as POST /predict.
    """
    from fusion_logic import run_full_pipeline
    from logger import log_prediction

    pil_image = None
    if req.image_base64:
        pil_image = _decode_base64_image(req.image_base64)

    result = run_full_pipeline(req.tieu_de, req.noi_dung, pil_image, include_detail=False)

    log_prediction(
        {"tieu_de": req.tieu_de, "noi_dung": req.noi_dung, "has_image": pil_image is not None},
        result,
    )

    return {**result, "analyzed_at": datetime.now().isoformat()}


# ─────────────────────────────────────────────
# ROUTES — LEGACY (preserved for Laravel AiService.php)
# ─────────────────────────────────────────────
@app.post("/analyze", tags=["Text AI"])
def analyze(req: AnalyzeRequest):
    """Classify incident from TEXT only (tieu_de + noi_dung). Legacy endpoint."""
    _require_text_models()
    return {
        "success":          True,
        "id_su_co":         req.id_su_co,
        "source":           "text",
        "loai_su_co":       predict.predict_loai(req.tieu_de, req.noi_dung),
        "muc_do_khan_cap":  predict.predict_mucdo(req.tieu_de, req.noi_dung),
        "analyzed_at":      datetime.now().isoformat(),
    }


@app.post("/predict-image", tags=["Image AI"])
async def predict_image_only(
    image: UploadFile = File(..., description="Incident image (jpg/png/webp)"),
):
    """Classify incident from IMAGE only. No text needed."""
    pil_img = _read_upload(image)
    result  = predict.predict_from_image(pil_img)
    return {
        "success":    True,
        "source":     "image",
        **result,
        "analyzed_at": datetime.now().isoformat(),
    }


@app.post("/analyze-with-image", tags=["Multimodal AI"])
async def analyze_with_image(
    id_su_co: int        = Form(...),
    tieu_de:  str        = Form(...),
    noi_dung: str        = Form(default=""),
    image:    UploadFile = File(None, description="Optional incident image"),
):
    """
    🌟 Legacy: Classify from TEXT + IMAGE combined.
    Preserved for backward-compat with Laravel AiService.php.
    """
    _require_text_models()

    if image and image.filename:
        pil_img = _read_upload(image)
        result  = predict.predict_multimodal(tieu_de, noi_dung, pil_img)
    else:
        result = {
            "loai_su_co":      predict.predict_loai(tieu_de, noi_dung),
            "muc_do_khan_cap": predict.predict_mucdo(tieu_de, noi_dung),
            "mode":   "text_only",
            "source": "text",
        }

    return {
        "success":   True,
        "id_su_co":  id_su_co,
        **result,
        "analyzed_at": datetime.now().isoformat(),
    }


@app.post("/check-duplicate", tags=["Text AI"])
def check_duplicate(req: DuplicateRequest):
    """Detect if incident is duplicate using cosine similarity."""
    _require_text_models()
    existing = [i.model_dump() for i in req.existing]
    result   = predict.check_duplicate(req.tieu_de, req.noi_dung, existing, req.threshold)
    return {"success": True, "id_su_co": req.id_su_co, **result, "checked_at": datetime.now().isoformat()}


# ─────────────────────────────────────────────
# BACKGROUND RETRAIN TASKS
# ─────────────────────────────────────────────
def _retrain_text_task(samples: list[dict]):
    data_path = os.path.join(os.path.dirname(__file__), "data", "training_data.json")
    with open(data_path, encoding="utf-8") as f:
        existing = json.load(f)
    existing.extend(samples)
    with open(data_path, "w", encoding="utf-8") as f:
        json.dump(existing, f, ensure_ascii=False, indent=2)
    predict._cache.clear()
    trainer.train(data_path)


def _retrain_images_task(labeled_images: list[dict]):
    from image_model import build_prototypes, train_image_classifier
    predict._cache.clear()
    if len(labeled_images) >= 4:
        train_image_classifier(labeled_images)
    else:
        build_prototypes(labeled_images)


@app.post("/retrain", tags=["Training"])
def retrain_text(req: RetrainRequest, background_tasks: BackgroundTasks):
    """
    Add new TEXT samples and retrain text + spam models in background.
    Samples with 'is_spam' field will also improve spam model.
    """
    if not req.samples:
        raise HTTPException(400, detail="No samples provided")
    # Validate: each valid-incident sample needs loai + mucdo
    for s in req.samples:
        if int(s.get("is_spam", 0)) == 0:
            missing = {"tieu_de", "id_loai_su_co", "id_muc_do"} - s.keys()
            if missing:
                raise HTTPException(422, detail=f"Valid sample missing fields: {missing}")
        else:
            if "tieu_de" not in s:
                raise HTTPException(422, detail="Spam sample missing 'tieu_de'")
    background_tasks.add_task(_retrain_text_task, req.samples)
    spam_count  = sum(1 for s in req.samples if int(s.get("is_spam", 0)) == 1)
    valid_count = len(req.samples) - spam_count
    return {
        "success":     True,
        "message":     f"Retraining with {valid_count} valid + {spam_count} spam sample(s).",
        "queued_at":   datetime.now().isoformat(),
    }


@app.post("/retrain-images", tags=["Training"])
def retrain_images(req: list[ImageLabelRequest], background_tasks: BackgroundTasks):
    """
    Add labeled image URLs and retrain image models.
    Admin can call this after confirming incidents with images.
    """
    if not req:
        raise HTTPException(400, detail="No images provided")
    labeled = [r.model_dump() for r in req]
    background_tasks.add_task(_retrain_images_task, labeled)
    return {
        "success": True,
        "message": f"Retraining image models with {len(labeled)} labeled image(s).",
        "note":    "Images will be downloaded and model updated in background.",
        "queued_at": datetime.now().isoformat(),
    }


# ─────────────────────────────────────────────
if __name__ == "__main__":
    port = int(os.getenv("AI_PORT", 8001))
    print(f"\n🚀 SOS AI Service v3.0 (Multimodal + Spam Detection) → http://localhost:{port}")
    print(f"   Swagger docs → http://localhost:{port}/docs\n")
    uvicorn.run("api:app", host="0.0.0.0", port=port, reload=True)
