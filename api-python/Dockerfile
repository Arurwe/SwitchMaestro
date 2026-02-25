############################################
# Base Image
############################################
FROM python:3.12-slim AS base

WORKDIR /app

RUN addgroup --system apiuser && adduser --system --ingroup apiuser apiuser

# USER root
# RUN apt-get update && apt-get install -y ...

COPY requirements.txt .


RUN pip install --no-cache-dir -r requirements.txt

############################################
# Development Image
############################################
FROM base AS development

USER apiuser


COPY . .

# start serwera z hot-reloadingiem
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000", "--reload"]


############################################
# Production Image
############################################
FROM base AS deploy


COPY . .

RUN chown -R apiuser:apiuser /app

USER apiuser

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]