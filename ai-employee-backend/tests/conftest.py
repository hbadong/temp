"""Pytest fixtures for async tests."""

import asyncio
import os
from collections.abc import AsyncGenerator
from pathlib import Path

import pytest

# Set test environment variables BEFORE any ai_employee imports
os.environ["DATABASE_URL"] = "sqlite+aiosqlite:///./test.db"
os.environ["REDIS_URL"] = "redis://localhost:6379/0"
os.environ["SECRET_KEY"] = "test-secret-key-for-development-only"
os.environ["ENVIRONMENT"] = "testing"

# Now import ai_employee modules (which will use the env vars)
from httpx import ASGITransport, AsyncClient
from sqlalchemy.ext.asyncio import AsyncSession, async_sessionmaker, create_async_engine

from ai_employee.db.session import Base, get_db
from ai_employee.main import create_app
from ai_employee.models import tenant, user, audit_log  # noqa: F401 - ensures models are registered

# Use SQLite for tests (no external DB required)
TEST_DATABASE_URL = os.environ["DATABASE_URL"]


@pytest.fixture(scope="session")
def event_loop() -> AsyncGenerator[asyncio.AbstractEventLoop, None]:
    """Create event loop for tests."""
    loop = asyncio.new_event_loop()
    yield loop
    loop.close()


@pytest.fixture(scope="session")
async def engine():
    """Create test database engine."""
    eng = create_async_engine(TEST_DATABASE_URL, echo=False)
    async with eng.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)
    yield eng
    async with eng.begin() as conn:
        await conn.run_sync(Base.metadata.drop_all)
    await eng.dispose()


@pytest.fixture
async def db_session(engine) -> AsyncGenerator[AsyncSession, None]:
    """Create a new database session for a test."""
    session_factory = async_sessionmaker(engine, class_=AsyncSession, expire_on_commit=False)
    async with session_factory() as session:
        async with session.begin():
            yield session
        await session.rollback()


@pytest.fixture
async def client(db_session: AsyncSession) -> AsyncGenerator[AsyncClient, None]:
    """Create test client with overridden dependencies."""

    async def override_get_db() -> AsyncGenerator[AsyncSession, None]:
        yield db_session

    app = create_app()
    app.dependency_overrides[get_db] = override_get_db

    transport = ASGITransport(app=app)
    async with AsyncClient(transport=transport, base_url="http://test") as ac:
        yield ac


@pytest.fixture
def test_tenant_data() -> dict:
    """Sample tenant data for tests."""
    return {
        "name": "Test Tenant",
        "industry": "technology",
    }


@pytest.fixture
def test_user_data() -> dict:
    """Sample user data for tests."""
    return {
        "email": "test@example.com",
        "password": "SecureP@ss123",
    }
