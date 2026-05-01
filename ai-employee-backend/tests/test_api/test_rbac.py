"""RBAC endpoint tests."""

import uuid

import pytest
from httpx import AsyncClient


@pytest.mark.asyncio
async def test_create_permission(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a new permission."""
    response = await client.post(
        "/api/v1/permissions",
        json={
            "code": "custom:permission",
            "description": "A custom permission",
        },
        headers=auth_headers,
    )
    assert response.status_code == 201
    data = response.json()
    assert data["data"]["code"] == "custom:permission"


@pytest.mark.asyncio
async def test_create_duplicate_permission(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a duplicate permission."""
    await client.post(
        "/api/v1/permissions",
        json={"code": "dup:perm", "description": "Duplicate"},
        headers=auth_headers,
    )
    response = await client.post(
        "/api/v1/permissions",
        json={"code": "dup:perm", "description": "Duplicate"},
        headers=auth_headers,
    )
    assert response.status_code == 409


@pytest.mark.asyncio
async def test_list_permissions(client: AsyncClient, auth_headers: dict) -> None:
    """Test listing permissions."""
    await client.post(
        "/api/v1/permissions",
        json={"code": "test:list:1", "description": "Test 1"},
        headers=auth_headers,
    )
    await client.post(
        "/api/v1/permissions",
        json={"code": "test:list:2", "description": "Test 2"},
        headers=auth_headers,
    )

    response = await client.get("/api/v1/permissions", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["total"] >= 2


@pytest.mark.asyncio
async def test_delete_permission(client: AsyncClient, auth_headers: dict) -> None:
    """Test deleting a permission."""
    create_resp = await client.post(
        "/api/v1/permissions",
        json={"code": "test:delete", "description": "To delete"},
        headers=auth_headers,
    )
    perm_id = create_resp.json()["data"]["id"]

    response = await client.delete(f"/api/v1/permissions/{perm_id}", headers=auth_headers)
    assert response.status_code == 200


@pytest.mark.asyncio
async def test_create_role(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a new role."""
    await client.post(
        "/api/v1/permissions",
        json={"code": "role:test:read", "description": "Read"},
        headers=auth_headers,
    )

    response = await client.post(
        "/api/v1/roles",
        json={
            "name": "Test Role",
            "description": "A test role",
            "permission_codes": ["role:test:read"],
        },
        headers=auth_headers,
    )
    assert response.status_code == 201
    data = response.json()
    assert data["data"]["name"] == "Test Role"


@pytest.mark.asyncio
async def test_create_duplicate_role(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a duplicate role."""
    await client.post(
        "/api/v1/roles",
        json={"name": "Dup Role", "description": "Duplicate"},
        headers=auth_headers,
    )
    response = await client.post(
        "/api/v1/roles",
        json={"name": "Dup Role", "description": "Duplicate"},
        headers=auth_headers,
    )
    assert response.status_code == 409


@pytest.mark.asyncio
async def test_list_roles(client: AsyncClient, auth_headers: dict) -> None:
    """Test listing roles."""
    await client.post(
        "/api/v1/roles",
        json={"name": "List Role A", "description": "A"},
        headers=auth_headers,
    )
    await client.post(
        "/api/v1/roles",
        json={"name": "List Role B", "description": "B"},
        headers=auth_headers,
    )

    response = await client.get("/api/v1/roles", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["total"] >= 2


@pytest.mark.asyncio
async def test_update_role(client: AsyncClient, auth_headers: dict) -> None:
    """Test updating a role."""
    create_resp = await client.post(
        "/api/v1/roles",
        json={"name": "Update Role", "description": "Before"},
        headers=auth_headers,
    )
    role_id = create_resp.json()["data"]["id"]

    response = await client.patch(
        f"/api/v1/roles/{role_id}",
        json={"name": "Updated Role", "description": "After"},
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["name"] == "Updated Role"


@pytest.mark.asyncio
async def test_delete_role(client: AsyncClient, auth_headers: dict) -> None:
    """Test deleting a role."""
    create_resp = await client.post(
        "/api/v1/roles",
        json={"name": "Delete Role", "description": "To delete"},
        headers=auth_headers,
    )
    role_id = create_resp.json()["data"]["id"]

    response = await client.delete(f"/api/v1/roles/{role_id}", headers=auth_headers)
    assert response.status_code == 200


@pytest.mark.asyncio
async def test_add_permissions_to_role(client: AsyncClient, auth_headers: dict) -> None:
    """Test adding permissions to a role."""
    await client.post(
        "/api/v1/permissions",
        json={"code": "add:perm:test", "description": "Test"},
        headers=auth_headers,
    )
    create_resp = await client.post(
        "/api/v1/roles",
        json={"name": "Add Perm Role", "description": "Test"},
        headers=auth_headers,
    )
    role_id = create_resp.json()["data"]["id"]

    response = await client.post(
        f"/api/v1/roles/{role_id}/permissions",
        json=["add:perm:test"],
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    codes = [p["code"] for p in data["data"]["permissions"]]
    assert "add:perm:test" in codes


@pytest.mark.asyncio
async def test_assign_role_to_user(client: AsyncClient, auth_headers: dict, test_tenant, test_superuser) -> None:
    """Test assigning a role to a user."""
    role_resp = await client.post(
        "/api/v1/roles",
        json={"name": "Assign Test Role", "description": "Test"},
        headers=auth_headers,
    )
    role_id = role_resp.json()["data"]["id"]

    response = await client.post(
        "/api/v1/user-roles",
        json={
            "user_id": str(test_superuser.id),
            "role_id": role_id,
        },
        headers=auth_headers,
    )
    assert response.status_code == 201


@pytest.mark.asyncio
async def test_get_user_roles(client: AsyncClient, auth_headers: dict, test_superuser) -> None:
    """Test getting user roles."""
    role_resp = await client.post(
        "/api/v1/roles",
        json={"name": "Get User Role", "description": "Test"},
        headers=auth_headers,
    )
    role_id = role_resp.json()["data"]["id"]

    await client.post(
        "/api/v1/user-roles",
        json={"user_id": str(test_superuser.id), "role_id": role_id},
        headers=auth_headers,
    )

    response = await client.get(
        f"/api/v1/users/{test_superuser.id}/roles",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    names = [r["name"] for r in data["data"]]
    assert "Get User Role" in names


@pytest.mark.asyncio
async def test_get_user_permissions(client: AsyncClient, auth_headers: dict, test_superuser) -> None:
    """Test getting user permissions."""
    await client.post(
        "/api/v1/permissions",
        json={"code": "user:perm:test", "description": "Test"},
        headers=auth_headers,
    )
    role_resp = await client.post(
        "/api/v1/roles",
        json={
            "name": "Perm Test Role",
            "description": "Test",
            "permission_codes": ["user:perm:test"],
        },
        headers=auth_headers,
    )
    role_id = role_resp.json()["data"]["id"]

    await client.post(
        "/api/v1/user-roles",
        json={"user_id": str(test_superuser.id), "role_id": role_id},
        headers=auth_headers,
    )

    response = await client.get(
        f"/api/v1/users/{test_superuser.id}/permissions",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    codes = [p["code"] for p in data["data"]["permissions"]]
    assert "user:perm:test" in codes


@pytest.mark.asyncio
async def test_remove_role_from_user(client: AsyncClient, auth_headers: dict, test_superuser) -> None:
    """Test removing a role from a user."""
    role_resp = await client.post(
        "/api/v1/roles",
        json={"name": "Remove Test Role", "description": "Test"},
        headers=auth_headers,
    )
    role_id = role_resp.json()["data"]["id"]

    await client.post(
        "/api/v1/user-roles",
        json={"user_id": str(test_superuser.id), "role_id": role_id},
        headers=auth_headers,
    )

    response = await client.request(
        "DELETE",
        "/api/v1/user-roles",
        params={"user_id": str(test_superuser.id), "role_id": role_id},
        headers=auth_headers,
    )
    assert response.status_code == 200
