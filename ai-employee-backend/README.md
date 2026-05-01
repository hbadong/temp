# AI Employee Backend

A multi-tenant AI Employee System built with FastAPI, SQLAlchemy, and PostgreSQL.

## Features

- Multi-tenant architecture with tenant isolation
- JWT authentication and authorization
- Async database operations with SQLAlchemy 2.0
- Redis integration for caching and session management
- Alembic for database migrations
- Comprehensive API with OpenAPI documentation

## Requirements

- Python 3.11+
- PostgreSQL 14+
- Redis 7+

## Quick Start

### 1. Install Dependencies

```bash
pip install -e ".[dev]"
```

### 2. Configure Environment

```bash
cp .env.example .env
# Edit .env with your configuration
```

### 3. Start Development Server

```bash
./scripts/dev.sh
```

Or manually:

```bash
uvicorn src.ai_employee.main:app --reload --host 0.0.0.0 --port 8000
```

### 4. Run Database Migrations

```bash
alembic upgrade head
```

## Development

### Run Tests

```bash
pytest
```

### Lint Code

```bash
ruff check .
ruff format .
```

### Pre-commit Hooks

```bash
pre-commit install
pre-commit run --all-files
```

## Project Structure

```
src/ai_employee/
├── api/           # API endpoints and routers
├── core/          # Security, exceptions, core logic
├── models/        # SQLAlchemy models
├── schemas/       # Pydantic schemas
├── services/      # Business logic
├── db/            # Database and Redis connections
└── utils/         # Utility functions
```

## API Documentation

Once the server is running, visit:
- Swagger UI: http://localhost:8000/docs
- ReDoc: http://localhost:8000/redoc
