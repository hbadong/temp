"""Pytest fixtures for async tests."""

import asyncio
import os
import uuid
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

from ai_employee.core.security import create_access_token, hash_password
from ai_employee.db.session import Base, get_db
from ai_employee.main import create_app
from ai_employee.models import tenant, user, audit_log, quota, rbac, trend, content, publish  # noqa: F401

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
    """Create a new database session for a test with automatic rollback."""
    connection = await engine.connect()
    transaction = await connection.begin()

    session_factory = async_sessionmaker(
        bind=connection,
        class_=AsyncSession,
        expire_on_commit=False,
    )
    session = session_factory()

    try:
        yield session
    finally:
        await session.close()
        await transaction.rollback()
        await connection.close()


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
async def authenticated_client(db_session: AsyncSession) -> AsyncGenerator[AsyncClient, None]:
    """Create test client with authentication and overridden dependencies."""

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
        "code": "test-tenant",
        "description": "A test tenant",
        "domain": "test.example.com",
    }


@pytest.fixture
def test_user_data() -> dict:
    """Sample user data for tests."""
    return {
        "email": "test@example.com",
        "password": "SecureP@ss123",
        "username": "testuser",
        "full_name": "Test User",
    }


@pytest.fixture
async def test_tenant(db_session: AsyncSession, test_tenant_data: dict):
    """Create a test tenant in the database."""
    from ai_employee.models.tenant import Tenant

    t = Tenant(**test_tenant_data)
    db_session.add(t)
    await db_session.flush()
    await db_session.refresh(t)
    return t


@pytest.fixture
async def test_superuser(db_session: AsyncSession, test_tenant, test_user_data: dict):
    """Create a test superuser."""
    from ai_employee.models.user import User

    u = User(
        email=test_user_data["email"],
        username=test_user_data["username"],
        full_name=test_user_data["full_name"],
        hashed_password=hash_password(test_user_data["password"]),
        tenant_id=test_tenant.id,
        is_active=True,
        is_superuser=True,
    )
    db_session.add(u)
    await db_session.flush()
    await db_session.refresh(u)
    return u


@pytest.fixture
def superuser_token(test_superuser) -> str:
    """Generate a JWT token for the superuser."""
    return create_access_token(
        subject=test_superuser.id,
        tenant_id=test_superuser.tenant_id,
        is_superuser=True,
        role="admin",
    )


@pytest.fixture
def auth_headers(superuser_token: str) -> dict:
    """Headers with authentication token."""
    return {"Authorization": f"Bearer {superuser_token}"}
