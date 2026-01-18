FROM python:3.11-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

ENV API_URL=http://api/api

ENTRYPOINT ["python", "kanban_cli.py"]
