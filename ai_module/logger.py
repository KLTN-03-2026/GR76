"""
SOS AI Prediction Logger
=========================
Logs predictions and misclassified samples to JSONL files.
- logs/predictions.jsonl    → all predictions
- logs/misclassified.jsonl  → samples flagged for retraining

Files rotate at MAX_LOG_BYTES (default 10 MB).
"""

import os
import json
import threading
from datetime import datetime, timezone

LOGS_DIR        = os.path.join(os.path.dirname(__file__), "logs")
PREDICTIONS_LOG = os.path.join(LOGS_DIR, "predictions.jsonl")
MISMATCH_LOG    = os.path.join(LOGS_DIR, "misclassified.jsonl")
MAX_LOG_BYTES   = 10 * 1024 * 1024  # 10 MB

_lock = threading.Lock()


def _ensure_logs_dir():
    os.makedirs(LOGS_DIR, exist_ok=True)


def _rotate_if_needed(path: str):
    """Rename file to .bak if it exceeds max size."""
    if os.path.exists(path) and os.path.getsize(path) >= MAX_LOG_BYTES:
        bak = path + ".bak"
        if os.path.exists(bak):
            os.remove(bak)
        os.rename(path, bak)


def _append_jsonl(path: str, record: dict):
    """Thread-safe append a record to a JSONL file."""
    _ensure_logs_dir()
    with _lock:
        _rotate_if_needed(path)
        record["_ts"] = datetime.now(timezone.utc).isoformat()
        with open(path, "a", encoding="utf-8") as f:
            f.write(json.dumps(record, ensure_ascii=False) + "\n")


# ─────────────────────────────────────────────
# PUBLIC API
# ─────────────────────────────────────────────

def log_prediction(request_payload: dict, result: dict):
    """
    Log every prediction made by the AI.

    Args:
        request_payload: The input data (tieu_de, noi_dung, has_image, etc.)
        result: The full prediction result dict
    """
    record = {
        "input":  {k: v for k, v in request_payload.items() if k != "image_base64"},
        "output": result,
    }
    _append_jsonl(PREDICTIONS_LOG, record)


def log_misclassified(request_payload: dict, expected: dict, got: dict, reason: str = ""):
    """
    Log a sample where prediction was wrong (for future retraining).

    Args:
        request_payload: The original input
        expected: What the correct answer should be
        got: What the AI predicted
        reason: Optional human note (e.g. "admin corrected")
    """
    record = {
        "input":    {k: v for k, v in request_payload.items() if k != "image_base64"},
        "expected": expected,
        "got":      got,
        "reason":   reason,
    }
    _append_jsonl(MISMATCH_LOG, record)


def get_stats() -> dict:
    """Return basic statistics from the prediction log."""
    _ensure_logs_dir()
    stats = {
        "predictions_count":    0,
        "misclassified_count":  0,
        "log_size_kb":          0.0,
        "mismatch_size_kb":     0.0,
    }
    if os.path.exists(PREDICTIONS_LOG):
        stats["log_size_kb"] = round(os.path.getsize(PREDICTIONS_LOG) / 1024, 2)
        with open(PREDICTIONS_LOG, encoding="utf-8") as f:
            stats["predictions_count"] = sum(1 for _ in f)
    if os.path.exists(MISMATCH_LOG):
        stats["mismatch_size_kb"] = round(os.path.getsize(MISMATCH_LOG) / 1024, 2)
        with open(MISMATCH_LOG, encoding="utf-8") as f:
            stats["misclassified_count"] = sum(1 for _ in f)
    return stats


def get_recent_predictions(n: int = 20) -> list[dict]:
    """Return the last N predictions from the log."""
    _ensure_logs_dir()
    if not os.path.exists(PREDICTIONS_LOG):
        return []
    records = []
    with open(PREDICTIONS_LOG, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if line:
                try:
                    records.append(json.loads(line))
                except json.JSONDecodeError:
                    pass
    return records[-n:]
