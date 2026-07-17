FROM python:3.12-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        build-essential \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY apps/research-agent/requirements.txt ./
# Playwright is heavy and opt-in (WEBSITE_AUDIT_USE_PLAYWRIGHT); skip its
# browser download at build time unless explicitly requested.
RUN pip install --no-cache-dir -r requirements.txt

COPY apps/research-agent/ ./

HEALTHCHECK --interval=15s --timeout=5s --retries=5 \
    CMD curl -f http://localhost:8000/internal/v1/health || exit 1

EXPOSE 8000
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
