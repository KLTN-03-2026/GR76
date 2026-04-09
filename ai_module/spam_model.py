"""
SOS Spam Detection Module
==========================
Binary classifier: spam (1) / valid (0)

Algorithm: TF-IDF + Logistic Regression with rule-based augmentation.

Detects:
  - Advertising / promotional content
  - Irrelevant / off-topic reports
  - Repetitive meaningless content
  - Troll / fake submissions
"""

import os
import re
import joblib
import numpy as np
from typing import Optional

MODELS_DIR     = os.path.join(os.path.dirname(__file__), "models")
SPAM_CLF_PATH  = os.path.join(MODELS_DIR, "spam_model.pkl")
SPAM_TFIDF_PATH = os.path.join(MODELS_DIR, "spam_tfidf.pkl")

# ─────────────────────────────────────────────
# KEYWORD RULES (fast pre-filter)
# ─────────────────────────────────────────────
_SPAM_KEYWORDS = [
    # advertising
    r"\bkhuyến\s*mãi\b", r"\bgiảm\s*giá\b", r"\bbán\s*hàng\b",
    r"\bkm\s*\d+%", r"\bfree\s*ship\b", r"\bmua\s*ngay\b",
    r"\bpromo\b", r"\bsale\b", r"\bdiscount\b", r"\bquảng\s*cáo\b",
    r"\bliên\s*hệ\s*\d{9,}\b", r"\bhotline\b", r"\bzalo\b",
    r"\bfacebook\.com\b", r"\bshopee\b", r"\blazada\b",
    # test / meaningless
    r"^(test|thử|abc|xyz|123|asdf|hello|hi|ok)+\s*$",
    r"\btest\s*\d*\b",
    r"(.)\1{5,}",  # repeated character 6+ times: aaaaaa
    # explicit troll
    r"\bgiả\s*mạo\b", r"\blừa\s*đảo\b", r"\bscam\b",
    r"\bkhông\s*có\s*thật\b",
    r"\bvô\s*nghĩa\b",
]
_SPAM_RE = [re.compile(p, re.IGNORECASE) for p in _SPAM_KEYWORDS]


def _rule_based_spam_score(text: str) -> float:
    """Returns a heuristic score 0.0–1.0 based on keyword rules."""
    if not text.strip():
        return 1.0  # empty = spam

    hits = sum(1 for rx in _SPAM_RE if rx.search(text))

    # Repetitive word check: 3+ same words in a row
    words = text.lower().split()
    if len(words) > 4:
        for i in range(len(words) - 2):
            if words[i] == words[i + 1] == words[i + 2]:
                hits += 2
                break

    # Very short text (< 5 chars) likely not a real report
    if len(text.strip()) < 5:
        hits += 2

    return min(1.0, hits * 0.35)


# ─────────────────────────────────────────────
# LAZY MODEL CACHE
# ─────────────────────────────────────────────
_cache: dict = {}


def _load_spam_models() -> Optional[dict]:
    """Load spam  models lazily. Returns None if not trained yet."""
    if "spam_clf" not in _cache:
        if not os.path.exists(SPAM_CLF_PATH) or not os.path.exists(SPAM_TFIDF_PATH):
            return None
        _cache["spam_tfidf"] = joblib.load(SPAM_TFIDF_PATH)
        _cache["spam_clf"]   = joblib.load(SPAM_CLF_PATH)
    return _cache


# ─────────────────────────────────────────────
# PUBLIC API
# ─────────────────────────────────────────────
def predict_spam(tieu_de: str, noi_dung: str = "") -> dict:
    """
    Predict whether an incident report is spam.

    Returns:
        {
            "is_spam": 0 | 1,
            "spam_confidence": float,   # probability it's spam
            "spam_source": "ml" | "rules" | "combined"
        }
    """
    combined_text = f"{tieu_de} {noi_dung}".strip()

    # --- Rule-based score (always runs) ---
    rule_score = _rule_based_spam_score(combined_text)

    # --- ML score (if model is trained) ---
    models = _load_spam_models()
    if models is not None:
        vec    = models["spam_tfidf"].transform([combined_text.lower()])
        proba  = models["spam_clf"].predict_proba(vec)[0]
        # index 1 = spam class
        ml_score = float(proba[1]) if len(proba) > 1 else float(proba[0])

        # Combined score: weighted average (60% ML, 40% rules)
        combined_score = 0.60 * ml_score + 0.40 * rule_score
        source = "combined"
    else:
        combined_score = rule_score
        source = "rules"

    is_spam = 1 if combined_score >= 0.50 else 0
    return {
        "is_spam":          is_spam,
        "spam_confidence":  round(combined_score, 4),
        "spam_source":      source,
    }


def spam_models_loaded() -> bool:
    return os.path.exists(SPAM_CLF_PATH) and os.path.exists(SPAM_TFIDF_PATH)


# ─────────────────────────────────────────────
# TRAINING HELPER (called from train.py)
# ─────────────────────────────────────────────
def train_spam_model(data: list[dict]) -> Optional[object]:
    """
    Train a binary spam classifier on labelled data.

    Expects each record to have an 'is_spam' field (0 or 1).
    Records without 'is_spam' are treated as valid (0).

    Returns the trained classifier, or None if not enough samples.
    """
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.linear_model import LogisticRegression
    from sklearn.model_selection import cross_val_score

    labelled = []
    for d in data:
        label = int(d.get("is_spam", 0))
        text  = f"{d.get('tieu_de', '')} {d.get('noi_dung', '')}".strip().lower()
        labelled.append((text, label))

    if len(labelled) < 4:
        print("  ⚠️ Not enough spam examples to train (need ≥4). Skipping spam model.")
        return None

    texts  = [t for t, _ in labelled]
    labels = [l for _, l in labelled]

    spam_count = sum(labels)
    valid_count = len(labels) - spam_count
    print(f"  📊 Spam training data: {spam_count} spam, {valid_count} valid")

    if spam_count == 0:
        print("  ⚠️ No spam examples found in training data. Skipping spam model.")
        return None

    os.makedirs(MODELS_DIR, exist_ok=True)

    print("  🔧 Fitting spam TF-IDF vectorizer...")
    tfidf = TfidfVectorizer(
        ngram_range=(1, 2),
        min_df=1,
        max_features=3000,
        sublinear_tf=True,
        analyzer="char_wb",   # char n-grams — more robust for troll/repetition detection
    )
    X = tfidf.fit_transform(texts)
    joblib.dump(tfidf, SPAM_TFIDF_PATH)

    print("  🤖 Training spam classifier (Logistic Regression)...")
    clf = LogisticRegression(
        max_iter=500,
        random_state=42,
        class_weight="balanced",  # handle imbalanced spam/valid ratio
    )
    clf.fit(X, labels)

    n_classes = len(set(labels))
    if n_classes > 1 and len(labelled) >= 5:
        scores = cross_val_score(clf, X, labels, cv=min(3, len(labelled)), scoring="f1")
        print(f"     Spam CV F1: {scores.mean():.2%}")

    joblib.dump(clf, SPAM_CLF_PATH)
    print(f"  ✅ Spam model saved → {SPAM_CLF_PATH}")

    # Invalidate cache
    _cache.clear()

    return clf
