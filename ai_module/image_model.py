"""
SOS Image Feature Extractor
============================
Uses pretrained ResNet50 (ImageNet) as a frozen backbone.
Extracts 2048-dim feature vectors from incident images.

Two modes:
1. Zero-shot: No labeled images needed — classifies via
   cosine similarity between image features and cached
   category prototype embeddings.
2. Fine-tune: When labeled images are available, trains a
   linear classifier on top of the frozen ResNet50 features.
"""

import os
import io
import json
import joblib
import numpy as np
from PIL import Image, UnidentifiedImageError
import requests as http_requests

MODELS_DIR   = os.path.join(os.path.dirname(__file__), "models")
IMAGE_MODEL_PATH   = os.path.join(MODELS_DIR, "image_classifier.pkl")
PROTOTYPE_PATH     = os.path.join(MODELS_DIR, "image_prototypes.pkl")
FUSION_MODEL_PATH  = os.path.join(MODELS_DIR, "fusion_loai_model.pkl")
FUSION_MUCDO_PATH  = os.path.join(MODELS_DIR, "fusion_mucdo_model.pkl")

# Category label descriptions (used for zero-shot text-to-image prototypes)
LOAI_LABELS = {
    1: "Cháy nổ",
    2: "Tai nạn giao thông",
    3: "Thiên tai",
    4: "Y tế khẩn cấp",
    5: "An ninh trật tự",
}
MUCDO_LABELS = {
    1: "Thấp",
    2: "Trung bình",
    3: "Nguy hiểm",
    4: "Khẩn cấp",
}

# ─────────────────────────────────────────────
# LAZY LOAD PYTORCH
# ─────────────────────────────────────────────
_torch_cache = {}

def _get_resnet():
    """Load ResNet50 once, return (model, transform, device)."""
    if "model" not in _torch_cache:
        import torch
        import torchvision.models as models
        import torchvision.transforms as T

        device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
        model  = models.resnet50(weights=models.ResNet50_Weights.IMAGENET1K_V1)
        # Remove final FC layer → output 2048-dim embeddings
        model.fc = torch.nn.Identity()
        model.eval()
        model.to(device)

        transform = T.Compose([
            T.Resize((224, 224)),
            T.ToTensor(),
            T.Normalize(mean=[0.485, 0.456, 0.406],
                        std=[0.229, 0.224, 0.225]),
        ])
        _torch_cache["model"]     = model
        _torch_cache["transform"] = transform
        _torch_cache["device"]    = device
        print("✅ ResNet50 loaded (ImageNet pretrained, frozen)")

    return _torch_cache["model"], _torch_cache["transform"], _torch_cache["device"]


# ─────────────────────────────────────────────
# CORE: EXTRACT IMAGE FEATURES
# ─────────────────────────────────────────────
def extract_features(pil_image: Image.Image) -> np.ndarray:
    """Convert PIL image → 2048-dim L2-normalised feature vector."""
    import torch

    model, transform, device = _get_resnet()
    img_rgb  = pil_image.convert("RGB")
    tensor   = transform(img_rgb).unsqueeze(0).to(device)

    with torch.no_grad():
        feats = model(tensor).cpu().numpy().squeeze()   # (2048,)

    # L2-normalise
    norm = np.linalg.norm(feats)
    return feats / (norm + 1e-8)


def load_image_from_path(path: str) -> Image.Image:
    """Load PIL Image from local file path or HTTP URL."""
    if path.startswith("http://") or path.startswith("https://"):
        resp = http_requests.get(path, timeout=10)
        resp.raise_for_status()
        return Image.open(io.BytesIO(resp.content))
    return Image.open(path)


def load_image_from_bytes(data: bytes) -> Image.Image:
    """Load PIL Image from raw bytes (multipart upload)."""
    return Image.open(io.BytesIO(data))


# ─────────────────────────────────────────────
# ZERO-SHOT PREDICTION (no labeled image data)
# ─────────────────────────────────────────────
def _load_prototypes() -> dict | None:
    if os.path.exists(PROTOTYPE_PATH):
        return joblib.load(PROTOTYPE_PATH)
    return None


def predict_loai_zero_shot(image_features: np.ndarray) -> dict:
    """
    Predict incident type from image features using stored prototypes.
    Returns best guess even without labeled training images.
    """
    prototypes = _load_prototypes()
    if prototypes is None or "loai" not in prototypes:
        # No prototypes yet — return neutral result
        return {"id_loai_su_co": -1, "ten_loai": "Chưa đủ dữ liệu ảnh", "do_tin_cay": 0.0}

    sims = {
        label_id: float(np.dot(image_features, proto))
        for label_id, proto in prototypes["loai"].items()
    }
    best_id  = max(sims, key=sims.get)
    best_sim = sims[best_id]
    return {
        "id_loai_su_co": best_id,
        "ten_loai":      LOAI_LABELS.get(best_id, "Không xác định"),
        "do_tin_cay":    round(best_sim, 4),
    }


def predict_mucdo_zero_shot(image_features: np.ndarray) -> dict:
    prototypes = _load_prototypes()
    if prototypes is None or "mucdo" not in prototypes:
        return {"id_muc_do": -1, "ten_muc_do": "Chưa đủ dữ liệu ảnh", "do_tin_cay": 0.0}

    sims = {
        label_id: float(np.dot(image_features, proto))
        for label_id, proto in prototypes["mucdo"].items()
    }
    best_id  = max(sims, key=sims.get)
    best_sim = sims[best_id]
    return {
        "id_muc_do":  best_id,
        "ten_muc_do": MUCDO_LABELS.get(best_id, "Không xác định"),
        "do_tin_cay": round(best_sim, 4),
    }


# ─────────────────────────────────────────────
# FINE-TUNED PREDICTION (after training with images)
# ─────────────────────────────────────────────
def predict_image_finetuned(image_features: np.ndarray) -> dict:
    """Use fine-tuned linear classifier if available."""
    result = {"image_loai": None, "image_mucdo": None, "mode": "zero_shot"}

    if os.path.exists(IMAGE_MODEL_PATH):
        clf = joblib.load(IMAGE_MODEL_PATH)
        pred  = int(clf.predict([image_features])[0])
        proba = float(np.max(clf.predict_proba([image_features])))
        result["image_loai"] = {
            "id_loai_su_co": pred,
            "ten_loai": LOAI_LABELS.get(pred, "Không xác định"),
            "do_tin_cay": round(proba, 4),
        }
        result["mode"] = "finetuned"

    if os.path.exists(FUSION_MODEL_PATH):
        pass  # handled elsewhere

    if result["image_loai"] is None:
        result["image_loai"]  = predict_loai_zero_shot(image_features)
        result["image_mucdo"] = predict_mucdo_zero_shot(image_features)

    return result


# ─────────────────────────────────────────────
# BUILD PROTOTYPES from labeled images
# ─────────────────────────────────────────────
def build_prototypes(image_dataset: list[dict]):
    """
    Build category prototypes by averaging image features.
    image_dataset: [{"path": str, "id_loai_su_co": int, "id_muc_do": int}]
    """
    from collections import defaultdict

    loai_feats  = defaultdict(list)
    mucdo_feats = defaultdict(list)

    for item in image_dataset:
        try:
            img    = load_image_from_path(item["path"])
            feats  = extract_features(img)
            if "id_loai_su_co" in item:
                loai_feats[item["id_loai_su_co"]].append(feats)
            if "id_muc_do" in item:
                mucdo_feats[item["id_muc_do"]].append(feats)
        except Exception as e:
            print(f"  ⚠️ Skip {item.get('path','?')}: {e}")

    prototypes = {
        "loai":  {k: np.mean(v, axis=0) for k, v in loai_feats.items()},
        "mucdo": {k: np.mean(v, axis=0) for k, v in mucdo_feats.items()},
    }
    os.makedirs(MODELS_DIR, exist_ok=True)
    joblib.dump(prototypes, PROTOTYPE_PATH)
    print(f"  ✅ Prototypes saved → {PROTOTYPE_PATH}")
    print(f"     Loai categories:  {list(prototypes['loai'].keys())}")
    print(f"     Mucdo categories: {list(prototypes['mucdo'].keys())}")
    return prototypes


# ─────────────────────────────────────────────
# TRAIN FINE-TUNED IMAGE CLASSIFIER
# ─────────────────────────────────────────────
def train_image_classifier(image_dataset: list[dict]):
    """
    Train a linear classifier on top of frozen ResNet50 features.
    image_dataset: [{"path": str, "id_loai_su_co": int, "id_muc_do": int}]
    """
    from sklearn.linear_model import LogisticRegression

    print(f"  📸 Extracting features from {len(image_dataset)} images...")
    X, y_loai, y_mucdo = [], [], []

    for item in image_dataset:
        try:
            img   = load_image_from_path(item["path"])
            feats = extract_features(img)
            X.append(feats)
            y_loai.append(item["id_loai_su_co"])
            y_mucdo.append(item["id_muc_do"])
        except Exception as e:
            print(f"  ⚠️ Skip {item.get('path','?')}: {e}")

    if len(X) < 2:
        print("  ⚠️ Not enough images to train. Need at least 2.")
        return

    X = np.array(X)

    print("  🤖 Training image loai classifier...")
    clf_loai = LogisticRegression(max_iter=500, random_state=42)
    clf_loai.fit(X, y_loai)
    joblib.dump(clf_loai, IMAGE_MODEL_PATH)
    print(f"     Saved → {IMAGE_MODEL_PATH}")

    print("  🤖 Training image mucdo classifier...")
    clf_mucdo = LogisticRegression(max_iter=500, random_state=42)
    clf_mucdo.fit(X, y_mucdo)
    joblib.dump(clf_mucdo, os.path.join(MODELS_DIR, "image_mucdo_model.pkl"))
    print("  ✅ Image classifiers trained!")

    # Also build prototypes for zero-shot
    build_prototypes(image_dataset)


# ─────────────────────────────────────────────
# MULTIMODAL FUSION
# ─────────────────────────────────────────────
def build_fusion_features(text_vec: np.ndarray, image_vec: np.ndarray) -> np.ndarray:
    """Concatenate normalised text TF-IDF sparse vec + image dense vec."""
    if hasattr(text_vec, "toarray"):
        text_dense = text_vec.toarray().squeeze()
    else:
        text_dense = np.array(text_vec).squeeze()
    return np.concatenate([text_dense, image_vec])


def train_fusion_classifiers(X_text, X_image, y_loai, y_mucdo):
    """Train fused text+image classifiers and save them."""
    from sklearn.linear_model import LogisticRegression

    X_fusion = np.array([
        build_fusion_features(xt, xi)
        for xt, xi in zip(X_text, X_image)
    ])

    print("  🔀 Training fusion loai classifier...")
    clf_loai = LogisticRegression(max_iter=500, random_state=42)
    clf_loai.fit(X_fusion, y_loai)
    joblib.dump(clf_loai, FUSION_MODEL_PATH)

    print("  🔀 Training fusion mucdo classifier...")
    clf_mucdo = LogisticRegression(max_iter=500, random_state=42)
    clf_mucdo.fit(X_fusion, y_mucdo)
    joblib.dump(clf_mucdo, FUSION_MUCDO_PATH)
    print(f"  ✅ Fusion models saved to {MODELS_DIR}")
