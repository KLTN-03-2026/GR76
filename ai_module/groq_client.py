"""
Groq Vision API Client
=======================
Uses Groq's LLM Vision models to generate natural-language descriptions
of incident images in Vietnamese.
"""

import os
import time
import requests
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), ".env"))

GROQ_API_URL = "https://api.groq.com/openai/v1/chat/completions"
GROQ_MODEL   = "meta-llama/llama-4-scout-17b-16e-instruct"

# Detailed Vietnamese prompt for incident analysis
_SYSTEM_PROMPT = (
    "Bạn là chuyên gia phân tích sự cố khẩn cấp tại Việt Nam. "
    "Khi nhận được hình ảnh, hãy mô tả CHÍNH XÁC và NGẮN GỌN bằng tiếng Việt (2-3 câu). "
    "Bao gồm: (1) Loại sự cố (cháy/tai nạn giao thông/ngập nước/cây đổ/y tế/an ninh/khác), "
    "(2) Mức độ nghiêm trọng (thấp/trung bình/cao/khẩn cấp), "
    "(3) Mô tả cụ thể những gì nhìn thấy. "
    "Chỉ mô tả sự thật, không đoán mò. Nếu không rõ, nói 'Không xác định được loại sự cố từ hình ảnh.'"
)


def _get_api_key() -> str:
    return os.getenv("GROQ_API_KEY", "")


def analyze_image_with_groq(image_base64: str, model: str = None, max_retries: int = 2) -> str:
    """
    Send a base64-encoded image to Groq Vision API and get a Vietnamese description.
    Returns empty string on any failure (safe fallback).
    """
    api_key = _get_api_key()
    if not api_key:
        print("  [Groq] No GROQ_API_KEY set, skipping image analysis.")
        return ""

    # Ensure proper data URI prefix
    if not image_base64.startswith("data:"):
        image_base64 = f"data:image/jpeg;base64,{image_base64}"

    payload = {
        "model": model or GROQ_MODEL,
        "messages": [
            {
                "role": "system",
                "content": _SYSTEM_PROMPT,
            },
            {
                "role": "user",
                "content": [
                    {
                        "type": "image_url",
                        "image_url": {"url": image_base64, "detail": "high"},
                    },
                    {
                        "type": "text",
                        "text": "Phân tích sự cố trong hình ảnh này và mô tả bằng tiếng Việt.",
                    },
                ],
            }
        ],
        "max_tokens": 300,
        "temperature": 0.2,
    }

    headers = {
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json",
    }

    for attempt in range(max_retries + 1):
        try:
            print(f"  [Groq] Calling {GROQ_MODEL} (attempt {attempt+1}/{max_retries+1}) key=...{api_key[-6:]}")
            resp = requests.post(GROQ_API_URL, json=payload, headers=headers, timeout=30)
            resp.raise_for_status()

            data = resp.json()
            if "choices" in data and len(data["choices"]) > 0:
                description = data["choices"][0]["message"].get("content", "").strip()
                print(f"  [Groq] OK! ({len(description)} chars): {description[:80]}...")
                return description
            else:
                print("  [Groq] Unexpected response format.")
                return ""

        except requests.exceptions.Timeout:
            print(f"  [Groq] Timeout (attempt {attempt+1})")
            if attempt < max_retries:
                time.sleep(2)
        except requests.exceptions.HTTPError as e:
            status = e.response.status_code
            body   = e.response.text[:300]
            print(f"  [Groq] HTTP {status}: {body}")
            # 429 rate limit — wait and retry
            if status == 429 and attempt < max_retries:
                retry_after = int(e.response.headers.get("Retry-After", 5))
                print(f"  [Groq] Rate limited, waiting {retry_after}s...")
                time.sleep(retry_after)
            else:
                return ""
        except Exception as e:
            print(f"  [Groq] Error: {e}")
            return ""

    return ""


def image_to_base64(pil_image) -> str:
    """Convert PIL Image to base64 string (JPEG, quality optimized for API)."""
    import io
    import base64

    buffer = io.BytesIO()
    # Resize if too large (Groq has payload limits)
    max_size = 1024
    if pil_image.width > max_size or pil_image.height > max_size:
        pil_image.thumbnail((max_size, max_size))
    pil_image.save(buffer, format="JPEG", quality=85)
    buffer.seek(0)
    return base64.b64encode(buffer.read()).decode("utf-8")

