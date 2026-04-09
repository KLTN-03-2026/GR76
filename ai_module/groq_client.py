"""
Groq Vision API Client
=======================
Uses Groq's LLM Vision models to generate natural-language descriptions
of incident images in Vietnamese.

Usage:
    from groq_client import analyze_image_with_groq
    desc = analyze_image_with_groq(base64_image_string)
"""

import os
import requests
from dotenv import load_dotenv

# Load .env at import time
load_dotenv(os.path.join(os.path.dirname(__file__), ".env"))

GROQ_API_URL = "https://api.groq.com/openai/v1/chat/completions"
GROQ_MODEL   = "meta-llama/llama-4-scout-17b-16e-instruct"


def _get_api_key() -> str:
    """Get API key at call time (not import time)."""
    return os.getenv("GROQ_API_KEY", "")


def analyze_image_with_groq(image_base64: str, model: str = None) -> str:
    """
    Send a base64-encoded image to Groq Vision API and get a Vietnamese description.

    Returns:
        Vietnamese description string (1-2 sentences).
        Returns empty string on any failure (safe fallback).
    """
    api_key = _get_api_key()
    if not api_key:
        print("  [Groq] No GROQ_API_KEY set, skipping image analysis.")
        return ""

    try:
        # Ensure proper data URI prefix
        if not image_base64.startswith("data:"):
            image_base64 = f"data:image/jpeg;base64,{image_base64}"

        payload = {
            "model": model or GROQ_MODEL,
            "messages": [
                {
                    "role": "user",
                    "content": [
                        {
                            "type": "text",
                            "text": (
                                "Ban la mot chuyen gia phan tich su co khan cap. "
                                "Hay mo ta ngan gon noi dung hinh anh bang tieng Viet (1-2 cau). "
                                "Tap trung vao: loai su co (chay, tai nan, ngap, ...), "
                                "muc do nghiem trong, dia diem uoc luong."
                            ),
                        },
                        {
                            "type": "image_url",
                            "image_url": {
                                "url": image_base64,
                            },
                        },
                    ],
                }
            ],
            "max_tokens": 200,
            "temperature": 0.3,
        }

        headers = {
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        }

        print(f"  [Groq] Calling {GROQ_MODEL} with API key ...{api_key[-6:]}")
        resp = requests.post(GROQ_API_URL, json=payload, headers=headers, timeout=30)
        resp.raise_for_status()

        data = resp.json()
        description = data["choices"][0]["message"]["content"].strip()
        print(f"  [Groq] OK! Description ({len(description)} chars): {description[:100]}...")
        return description

    except requests.exceptions.Timeout:
        print("  [Groq] Request timed out.")
        return ""
    except requests.exceptions.HTTPError as e:
        print(f"  [Groq] HTTP error: {e.response.status_code} - {e.response.text[:300]}")
        return ""
    except Exception as e:
        print(f"  [Groq] Error: {e}")
        return ""


def image_to_base64(pil_image) -> str:
    """Convert PIL Image to base64 string."""
    import io
    import base64

    buffer = io.BytesIO()
    pil_image.save(buffer, format="JPEG", quality=85)
    buffer.seek(0)
    return base64.b64encode(buffer.read()).decode("utf-8")
