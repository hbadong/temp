"""User endpoint tests."""

import uuid

import pytest
from httpx import AsyncClient


@pytest.mark.asyncio
async def test_create_user(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test creating a new user."""
    response = await client.post(
        "/api/v1/users",
        json={
            "email": "newuser@example.com",
            "username": "newuser",
            "full_name": "New User",
            "password": "SecureP@ss123",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )
    assert response.status_code == 201
    data = response.json()
    assert data["code"] == 201
    assert data["data"]["email"] == "newuser@example.com"
    assert data["data"]["username"] == "newuser"


@pytest.mark.asyncio
async def test_create_user_duplicate_email(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test creating a user with duplicate email."""
    await client.post(
        "/api/v1/users",
        json={
            "email": "dup@example.com",
            "username": "dupuser",
            "password": "SecureP@ss123",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )
    response = await client.post(
        "/api/v1/users",
        json={
            "email": "dup@example.com",
            "username": "different-user",
            "password": "SecureP@ss456",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )
    assert response.status_code == 409


@pytest.mark.asyncio
async def test_create_user_duplicate_username(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test creating a user with duplicate username."""
    await client.post(
        "/api/v1/users",
        json={
            "email": "user1@example.com",
            "username": "sameuser",
            "password": "SecureP@ss123",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )
    response = await client.post(
        "/api/v1/users",
        json={
            "email": "different@example.com",
            "username": "sameuser",
            "password": "SecureP@ss456",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )
    assert response.status_code == 409


@pytest.mark.asyncio
async def test_list_users(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test listing users."""
    for i in range(3):
        await client.post(
            "/api/v1/users",
            json={
                "email": f"list{i}@example.com",
                "username": f"listuser{i}",
                "password": "SecureP@ss123",
                "tenant_id": str(test_tenant.id),
            },
            headers=auth_headers,
        )

    response = await client.get("/api/v1/users", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["total"] >= 3


@pytest.mark.asyncio
async def test_list_users_by_tenant(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test listing users filtered by tenant."""
    await client.post(
        "/api/v1/users",
        json={
            "email": "tenant-user@example.com",
            "username": "tenantuser",
            "password": "SecureP@ss123",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )

    response = await client.get(
        f"/api/v1/users?tenant_id={test_tenant.id}",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    emails = [u["email"] for u in data["data"]["items"]]
    assert "tenant-user@example.com" in emails


@pytest.mark.asyncio
async def test_get_user(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test getting a user by ID."""
    create_resp = await client.post(
        "/api/v1/users",
        json={
            "email": "getuser@example.com",
            "username": "getuser",
            "password": "SecureP@ss123",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )
    user_id = create_resp.json()["data"]["id"]

    response = await client.get(f"/api/v1/users/{user_id}", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["email"] == "getuser@example.com"


@pytest.mark.asyncio
async def test_get_user_not_found(client: AsyncClient, auth_headers: dict) -> None:
    """Test getting a non-existent user."""
    fake_id = str(uuid.uuid4())
    response = await client.get(f"/api/v1/users/{fake_id}", headers=auth_headers)
    assert response.status_code == 404


@pytest.mark.asyncio
async def test_update_user(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test updating a user."""
    create_resp = await client.post(
        "/api/v1/users",
        json={
            "email": "updateuser@example.com",
            "username": "updateuser",
            "password": "SecureP@ss123",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )
    user_id = create_resp.json()["data"]["id"]

    response = await client.patch(
        f"/api/v1/users/{user_id}",
        json={"full_name": "Updated Name", "is_active": False},
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["full_name"] == "Updated Name"
    assert data["data"]["is_active"] is False


@pytest.mark.asyncio
async def test_delete_user(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test soft deleting a user."""
    create_resp = await client.post(
        "/api/v1/users",
        json={
            "email": "deleteuser@example.com",
            "username": "deleteuser",
            "password": "SecureP@ss123",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )
    user_id = create_resp.json()["data"]["id"]

    response = await client.delete(f"/api/v1/users/{user_id}", headers=auth_headers)
    assert response.status_code == 200

    get_resp = await client.get(f"/api/v1/users/{user_id}", headers=auth_headers)
    assert get_resp.status_code == 404


@pytest.mark.asyncio
async def test_login_and_get_profile(authenticated_client: AsyncClient, test_superuser, superuser_token: str) -> None:
    """Test getting current user profile."""
    headers = {"Authorization": f"Bearer {superuser_token}"}
    response = await authenticated_client.get("/api/v1/users/me", headers=headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["email"] == test_superuser.email


@pytest.mark.asyncio
async def test_update_profile(authenticated_client: AsyncClient, superuser_token: str) -> None:
    """Test updating current user profile."""
    headers = {"Authorization": f"Bearer {superuser_token}"}
    response = await authenticated_client.patch(
        "/api/v1/users/me",
        params={"full_name": "Updated Full Name"},
        headers=headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["full_name"] == "Updated Full Name"


@pytest.mark.asyncio
async def test_change_password(authenticated_client: AsyncClient, test_superuser, superuser_token: str) -> None:
    """Test changing password."""
    headers = {"Authorization": f"Bearer {superuser_token}"}

    response = await authenticated_client.post(
        "/api/v1/users/me/change-password",
        params={
            "current_password": "SecureP@ss123",
            "new_password": "NewSecureP@ss456",
        },
        headers=headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["message"] == "Password changed successfully"


@pytest.mark.asyncio
async def test_change_password_wrong_current(authenticated_client: AsyncClient, superuser_token: str) -> None:
    """Test changing password with wrong current password."""
    headers = {"Authorization": f"Bearer {superuser_token}"}

    response = await authenticated_client.post(
        "/api/v1/users/me/change-password",
        params={
            "current_password": "WrongP@ssword",
            "new_password": "NewSecureP@ss456",
        },
        headers=headers,
    )
    assert response.status_code == 409


@pytest.mark.asyncio
async def test_unauthorized_user_access(client: AsyncClient) -> None:
    """Test accessing user endpoints without authentication."""
    response = await client.get("/api/v1/users")
    assert response.status_code == 401


@pytest.mark.asyncio
async def test_create_user_weak_password(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test creating a user with a weak password."""
    response = await client.post(
        "/api/v1/users",
        json={
            "email": "weak@example.com",
            "username": "weakuser",
            "password": "short",
            "tenant_id": str(test_tenant.id),
        },
        headers=auth_headers,
    )
    assert response.status_code == 422
