"""FastAPI application entry point."""

from collections.abc import AsyncGenerator
from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from ai_employee.api.router import api_router
from ai_employee.config import settings
from ai_employee.db.redis import close_redis, init_redis
from ai_employee.db.session import close_db, init_db


@asynccontextmanager
async def lifespan(app: FastAPI) -> AsyncGenerator[None, None]:
    """Manage application startup and shutdown events."""
    # Startup
    try:
        await init_db()
    except Exception:
        pass  # Allow tests to override DB
    try:
        await init_redis()
    except Exception:
        pass  # Redis is optional for some operations
    yield
    # Shutdown
    await close_db()
    await close_redis()


def create_app() -> FastAPI:
    """Create and configure FastAPI application."""
    app = FastAPI(
        title="AI Employee System",
        description="A multi-tenant AI Employee management system",
        version="0.1.0",
        lifespan=lifespan,
        docs_url="/docs",
        redoc_url="/redoc",
    )

    # CORS middleware
    app.add_middleware(
        CORSMiddleware,
        allow_origins=settings.allowed_origins,
        allow_credentials=True,
        allow_methods=["*"],
        allow_headers=["*"],
    )

    # Include API router
    app.include_router(api_router, prefix="/api")

    # Health check endpoint
    @app.get("/health", tags=["Health"])
    async def health_check() -> dict[str, str]:
        """Health check endpoint."""
        return {"status": "ok", "environment": settings.environment}

    return app


app = create_app()


def main() -> None:
    """Entry point for uvicorn."""
    import uvicorn

    uvicorn.run(
        "ai_employee.main:app",
        host="0.0.0.0",
        port=8000,
        reload=settings.environment == "development",
    )


if __name__ == "__main__":
    main()
