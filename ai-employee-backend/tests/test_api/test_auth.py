"""Authentication endpoint tests."""

import pytest
from httpx import AsyncClient


@pytest.mark.asyncio
async def test_health_check(client: AsyncClient) -> None:
    """Test health check endpoint."""
    response = await client.get("/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "ok"


@pytest.mark.asyncio
async def test_login_invalid_credentials(client: AsyncClient) -> None:
    """Test login with invalid credentials."""
    response = await client.post(
        "/api/v1/auth/login",
        json={
            "email": "nonexistent@example.com",
            "password": "wrongpassword",
        },
    )
    assert response.status_code == 401


@pytest.mark.asyncio
async def test_login_invalid_email_format(client: AsyncClient) -> None:
    """Test login with invalid email format."""
    response = await client.post(
        "/api/v1/auth/login",
        json={
            "email": "invalid-email",
            "password": "somepassword",
        },
    )
    assert response.status_code == 422


@pytest.mark.asyncio
async def test_login_missing_fields(client: AsyncClient) -> None:
    """Test login with missing required fields."""
    response = await client.post(
        "/api/v1/auth/login",
        json={"email": "test@example.com"},
    )
    assert response.status_code == 422


@pytest.mark.asyncio
async def test_logout(client: AsyncClient) -> None:
    """Test logout endpoint."""
    response = await client.post("/api/v1/auth/logout")
    assert response.status_code == 200
    data = response.json()
    assert data["message"] == "登出成功"
