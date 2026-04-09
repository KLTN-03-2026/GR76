"""
Note: On Windows, set PYTHONIOENCODING=utf-8 or run via MSYS2 bash for emoji output.
SOS AI Training Script – Multimodal (Text + Image + Spam)
==========================================================
Trains 4 sets of models:
1. Text-only (TF-IDF + Logistic Regression) → loai + mucdo
2. Spam detection (TF-IDF + Logistic Regression) → is_spam binary
3. Image-only (ResNet50 features + Linear classifier)
4. Fusion     (Text + Image combined features)

Usage:
    python train.py                          # text + spam (default)
    python train.py --images                 # text + spam + image
    python train.py --data data/training_data.json --images
"""

import sys, json
# Ensure UTF-8 output on Windows
if sys.stdout.encoding and sys.stdout.encoding.lower() not in ("utf-8", "utf8"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
if sys.stderr.encoding and sys.stderr.encoding.lower() not in ("utf-8", "utf8"):
    sys.stderr.reconfigure(encoding="utf-8", errors="replace")
import os
import argparse
import joblib
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import cross_val_score

DATA_PATH   = "data/training_data.json"
IMAGES_DIR  = "data/images"               # subfolder per loai_su_co
MODELS_DIR  = "models"

LOAI_MODEL_PATH  = os.path.join(MODELS_DIR, "loai_su_co_model.pkl")
MUCDO_MODEL_PATH = os.path.join(MODELS_DIR, "muc_do_model.pkl")
TFIDF_PATH       = os.path.join(MODELS_DIR, "tfidf_vectorizer.pkl")

# ─────────────────────────────────────────────
# TEXT PREPROCESSING
# ─────────────────────────────────────────────
try:
    from underthesea import word_tokenize
    def preprocess(text: str) -> str:
        return word_tokenize(text, format="text").lower()
except ImportError:
    def preprocess(text: str) -> str:
        return text.lower()

def combine_text(record: dict) -> str:
    parts = []
    if record.get("tieu_de"):  parts.append(record["tieu_de"])
    if record.get("noi_dung"): parts.append(record["noi_dung"])
    return preprocess(" ".join(parts))


# ─────────────────────────────────────────────
# DISCOVER LABELED IMAGES
# ─────────────────────────────────────────────
def discover_image_dataset(images_dir: str) -> list[dict]:
    """
    Auto-discover labeled images from folder structure:
    data/images/
    ├── loai_1/         ← id_loai_su_co = 1  (Cháy nổ)
    │   ├── mucdo_4/    ← id_muc_do = 4
    │   │   ├── img1.jpg
    │   │   └── img2.jpg
    │   └── mucdo_3/
    └── loai_2/
    """
    import glob
    supported = {".jpg", ".jpeg", ".png", ".webp", ".bmp"}
    dataset   = []

    if not os.path.exists(images_dir):
        return dataset

    for loai_dir in os.scandir(images_dir):
        if not loai_dir.is_dir() or not loai_dir.name.startswith("loai_"):
            continue
        id_loai = int(loai_dir.name.split("_")[1])

        for mucdo_dir in os.scandir(loai_dir.path):
            if not mucdo_dir.is_dir() or not mucdo_dir.name.startswith("mucdo_"):
                continue
            id_mucdo = int(mucdo_dir.name.split("_")[1])

            for f in os.scandir(mucdo_dir.path):
                if os.path.splitext(f.name)[1].lower() in supported:
                    dataset.append({
                        "path":          f.path,
                        "id_loai_su_co": id_loai,
                        "id_muc_do":     id_mucdo,
                    })

    print(f"  📸 Found {len(dataset)} labeled images in {images_dir}")
    return dataset


# ─────────────────────────────────────────────
# TRAIN TEXT MODELS (loai + mucdo)
# ─────────────────────────────────────────────
def train_text_models(data: list[dict]) -> tuple:
    """
    Train TF-IDF + Logistic Regression for loai_su_co and muc_do.
    Only uses records where is_spam == 0 (valid incidents).
    Returns (tfidf, X, y_loai, y_mucdo).
    """
    # Filter out spam records for incident classification
    valid_data = [d for d in data if int(d.get("is_spam", 0)) == 0
                  and d.get("id_loai_su_co") and int(d["id_loai_su_co"]) > 0]

    if not valid_data:
        raise ValueError("No valid (non-spam) training samples found!")

    print(f"  📋 Using {len(valid_data)} valid incident records for text models")

    texts   = [combine_text(d) for d in valid_data]
    y_loai  = [int(d["id_loai_su_co"]) for d in valid_data]
    y_mucdo = [int(d["id_muc_do"]) for d in valid_data]

    print("\n🔧 Fitting TF-IDF vectorizer...")
    tfidf = TfidfVectorizer(ngram_range=(1, 2), min_df=1, max_features=5000, sublinear_tf=True)
    X = tfidf.fit_transform(texts)
    joblib.dump(tfidf, TFIDF_PATH)
    print(f"   Saved → {TFIDF_PATH}")

    print("\n🤖 Training text-only Loai classifier...")
    clf_loai = LogisticRegression(max_iter=500, random_state=42)
    clf_loai.fit(X, y_loai)
    if len(set(y_loai)) > 1 and len(valid_data) >= 5:
        scores = cross_val_score(clf_loai, X, y_loai, cv=min(3, len(valid_data)), scoring="accuracy")
        print(f"   CV Accuracy: {scores.mean():.2%}")
    joblib.dump(clf_loai, LOAI_MODEL_PATH)

    print("\n🤖 Training text-only Muc Do classifier...")
    clf_mucdo = LogisticRegression(max_iter=500, random_state=42)
    clf_mucdo.fit(X, y_mucdo)
    if len(set(y_mucdo)) > 1 and len(valid_data) >= 5:
        scores = cross_val_score(clf_mucdo, X, y_mucdo, cv=min(3, len(valid_data)), scoring="accuracy")
        print(f"   CV Accuracy: {scores.mean():.2%}")
    joblib.dump(clf_mucdo, MUCDO_MODEL_PATH)

    # Save training texts for duplicate detection
    meta = [{"id": i, "text": t} for i, t in enumerate(texts)]
    with open(os.path.join(MODELS_DIR, "training_texts.json"), "w", encoding="utf-8") as f:
        json.dump(meta, f, ensure_ascii=False, indent=2)

    print(f"\n✅ Text models saved to {MODELS_DIR}/")
    return tfidf, X, y_loai, y_mucdo


# ─────────────────────────────────────────────
# TRAIN SPAM MODEL
# ─────────────────────────────────────────────
def train_spam_models(data: list[dict]):
    """
    Train spam binary classifier using all records (spam + valid).
    Delegates to spam_model module.
    """
    from spam_model import train_spam_model

    print("\n🛡️  Training spam detection model...")
    clf = train_spam_model(data)
    if clf is None:
        print("  ⚠️  Spam model skipped (insufficient data).")
    else:
        print("  ✅ Spam model ready!")
    return clf


# ─────────────────────────────────────────────
# TRAIN IMAGE + FUSION MODELS
# ─────────────────────────────────────────────
def train_image_models(image_dataset: list[dict], tfidf=None, text_data: list[dict] = None):
    from image_model import (
        extract_features,
        load_image_from_path,
        build_prototypes,
        train_image_classifier,
        train_fusion_classifiers,
        build_fusion_features,
    )

    # 1. Build zero-shot prototypes (always useful)
    print("\n🖼️  Building image prototypes (zero-shot)...")
    build_prototypes(image_dataset)

    # 2. Fine-tune image classifier if enough samples
    if len(image_dataset) >= 4:
        print("\n📸 Training image classifiers (fine-tuned)...")
        train_image_classifier(image_dataset)
    else:
        print(f"\n⚠️  Only {len(image_dataset)} images → skipping fine-tune (need ≥4)")
        print("   Zero-shot prototypes will be used instead.")

    # 3. Fusion: only if we also have text data aligned with images
    if tfidf is not None:
        fusion_data = [d for d in image_dataset if "tieu_de" in d or "noi_dung" in d]
        if len(fusion_data) >= 4:
            print(f"\n🔀 Training fusion model with {len(fusion_data)} text+image pairs...")
            X_text_feats, X_img_feats = [], []
            y_loai, y_mucdo = [], []

            for item in fusion_data:
                try:
                    img  = load_image_from_path(item["path"])
                    img_f = extract_features(img)
                    text  = combine_text(item)
                    txt_f = tfidf.transform([text])
                    X_text_feats.append(txt_f)
                    X_img_feats.append(img_f)
                    y_loai.append(item["id_loai_su_co"])
                    y_mucdo.append(item["id_muc_do"])
                except Exception as e:
                    print(f"  ⚠️ Skip {item.get('path','?')}: {e}")

            if len(X_text_feats) >= 4:
                train_fusion_classifiers(X_text_feats, X_img_feats, y_loai, y_mucdo)
        else:
            print("  ℹ️  Images missing text fields — skipping fusion training")


# ─────────────────────────────────────────────
# MAIN
# ─────────────────────────────────────────────
def train(data_path: str = DATA_PATH, with_images: bool = False):
    os.makedirs(MODELS_DIR, exist_ok=True)

    print(f"\n{'='*55}")
    print(f"  SOS AI Training {'(Multimodal)' if with_images else '(Text + Spam)'}")
    print(f"{'='*55}")

    # Load text training data
    print(f"\n📂 Loading training data from {data_path}...")
    with open(data_path, encoding="utf-8") as f:
        data = json.load(f)

    spam_count  = sum(1 for d in data if int(d.get("is_spam", 0)) == 1)
    valid_count = len(data) - spam_count
    print(f"✅ {len(data)} total samples: {valid_count} valid, {spam_count} spam")

    # Train text models (valid incidents only)
    tfidf, X, y_loai, y_mucdo = train_text_models(data)

    # Train spam model (all samples)
    train_spam_models(data)

    # Train image models (optional)
    if with_images:
        image_dataset = discover_image_dataset(IMAGES_DIR)
        if image_dataset:
            train_image_models(image_dataset, tfidf=tfidf, text_data=data)
        else:
            print(f"\n⚠️  No images found in {IMAGES_DIR}/")
            print("   └─ Create: data/images/loai_1/mucdo_4/photo.jpg")
            print("   Then run: python train.py --images")

    print(f"\n{'='*55}")
    print("  ✅ Training complete! Run: python api.py")
    print(f"{'='*55}\n")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Train SOS AI models")
    parser.add_argument("--data",   default=DATA_PATH, help="Path to training_data.json")
    parser.add_argument("--images", action="store_true", help="Also train image models")
    args = parser.parse_args()
    train(args.data, with_images=args.images)
