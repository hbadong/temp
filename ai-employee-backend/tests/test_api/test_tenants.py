"""Tenant endpoint tests."""

import uuid

import pytest
from httpx import AsyncClient


@pytest.mark.asyncio
async def test_create_tenant(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a new tenant."""
    response = await client.post(
        "/api/v1/tenants",
        json={
            "name": "New Tenant",
            "code": "new-tenant",
            "description": "A new test tenant",
            "domain": "new.example.com",
        },
        headers=auth_headers,
    )
    assert response.status_code == 201
    data = response.json()
    assert data["code"] == 201
    assert data["data"]["name"] == "New Tenant"
    assert data["data"]["code"] == "new-tenant"


@pytest.mark.asyncio
async def test_create_tenant_duplicate_code(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a tenant with duplicate code."""
    await client.post(
        "/api/v1/tenants",
        json={
            "name": "Tenant One",
            "code": "tenant-one",
        },
        headers=auth_headers,
    )
    response = await client.post(
        "/api/v1/tenants",
        json={
            "name": "Tenant Two",
            "code": "tenant-one",
        },
        headers=auth_headers,
    )
    assert response.status_code == 409


@pytest.mark.asyncio
async def test_list_tenants(client: AsyncClient, auth_headers: dict) -> None:
    """Test listing tenants."""
    await client.post(
        "/api/v1/tenants",
        json={"name": "Tenant A", "code": "tenant-a"},
        headers=auth_headers,
    )
    await client.post(
        "/api/v1/tenants",
        json={"name": "Tenant B", "code": "tenant-b"},
        headers=auth_headers,
    )

    response = await client.get("/api/v1/tenants", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["code"] == 200
    assert data["data"]["total"] >= 2


@pytest.mark.asyncio
async def test_list_tenants_with_pagination(client: AsyncClient, auth_headers: dict) -> None:
    """Test tenant list pagination."""
    for i in range(5):
        await client.post(
            "/api/v1/tenants",
            json={"name": f"Tenant {i}", "code": f"tenant-pag-{i}"},
            headers=auth_headers,
        )

    response = await client.get("/api/v1/tenants?page=1&page_size=2", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert len(data["data"]["items"]) == 2
    assert data["data"]["page"] == 1
    assert data["data"]["has_next"] is True


@pytest.mark.asyncio
async def test_list_tenants_filter_active(client: AsyncClient, auth_headers: dict) -> None:
    """Test listing tenants filtered by active status."""
    t1 = await client.post(
        "/api/v1/tenants",
        json={"name": "Active Tenant", "code": "active-tenant"},
        headers=auth_headers,
    )
    t1_id = t1.json()["data"]["id"]

    t2 = await client.post(
        "/api/v1/tenants",
        json={"name": "Inactive Tenant", "code": "inactive-tenant"},
        headers=auth_headers,
    )
    t2_id = t2.json()["data"]["id"]

    await client.post(f"/api/v1/tenants/{t2_id}/deactivate", headers=auth_headers)

    response = await client.get("/api/v1/tenants?is_active=true", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    active_ids = [t["id"] for t in data["data"]["items"]]
    assert t1_id in active_ids
    assert t2_id not in active_ids


@pytest.mark.asyncio
async def test_get_tenant(client: AsyncClient, auth_headers: dict) -> None:
    """Test getting a tenant by ID."""
    create_resp = await client.post(
        "/api/v1/tenants",
        json={"name": "Get Test", "code": "get-test"},
        headers=auth_headers,
    )
    tenant_id = create_resp.json()["data"]["id"]

    response = await client.get(f"/api/v1/tenants/{tenant_id}", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["name"] == "Get Test"


@pytest.mark.asyncio
async def test_get_tenant_not_found(client: AsyncClient, auth_headers: dict) -> None:
    """Test getting a non-existent tenant."""
    fake_id = str(uuid.uuid4())
    response = await client.get(f"/api/v1/tenants/{fake_id}", headers=auth_headers)
    assert response.status_code == 404


@pytest.mark.asyncio
async def test_update_tenant(client: AsyncClient, auth_headers: dict) -> None:
    """Test updating a tenant."""
    create_resp = await client.post(
        "/api/v1/tenants",
        json={"name": "Update Test", "code": "update-test"},
        headers=auth_headers,
    )
    tenant_id = create_resp.json()["data"]["id"]

    response = await client.patch(
        f"/api/v1/tenants/{tenant_id}",
        json={"name": "Updated Name", "description": "Updated description"},
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["name"] == "Updated Name"
    assert data["data"]["description"] == "Updated description"


@pytest.mark.asyncio
async def test_delete_tenant(client: AsyncClient, auth_headers: dict) -> None:
    """Test soft deleting a tenant."""
    create_resp = await client.post(
        "/api/v1/tenants",
        json={"name": "Delete Test", "code": "delete-test"},
        headers=auth_headers,
    )
    tenant_id = create_resp.json()["data"]["id"]

    response = await client.delete(f"/api/v1/tenants/{tenant_id}", headers=auth_headers)
    assert response.status_code == 200

    get_resp = await client.get(f"/api/v1/tenants/{tenant_id}", headers=auth_headers)
    assert get_resp.status_code == 404


@pytest.mark.asyncio
async def test_activate_tenant(client: AsyncClient, auth_headers: dict) -> None:
    """Test activating a tenant."""
    create_resp = await client.post(
        "/api/v1/tenants",
        json={"name": "Activate Test", "code": "activate-test"},
        headers=auth_headers,
    )
    tenant_id = create_resp.json()["data"]["id"]

    await client.post(f"/api/v1/tenants/{tenant_id}/deactivate", headers=auth_headers)

    response = await client.post(f"/api/v1/tenants/{tenant_id}/activate", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["is_active"] is True


@pytest.mark.asyncio
async def test_deactivate_tenant(client: AsyncClient, auth_headers: dict) -> None:
    """Test deactivating a tenant."""
    create_resp = await client.post(
        "/api/v1/tenants",
        json={"name": "Deactivate Test", "code": "deactivate-test"},
        headers=auth_headers,
    )
    tenant_id = create_resp.json()["data"]["id"]

    response = await client.post(
        f"/api/v1/tenants/{tenant_id}/deactivate",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["is_active"] is False


@pytest.mark.asyncio
async def test_unauthorized_access(client: AsyncClient) -> None:
    """Test accessing tenant endpoints without authentication."""
    response = await client.get("/api/v1/tenants")
    assert response.status_code == 401


@pytest.mark.asyncio
async def test_create_tenant_duplicate_domain(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a tenant with duplicate domain."""
    await client.post(
        "/api/v1/tenants",
        json={
            "name": "Domain Tenant A",
            "code": "domain-a",
            "domain": "shared.example.com",
        },
        headers=auth_headers,
    )
    response = await client.post(
        "/api/v1/tenants",
        json={
            "name": "Domain Tenant B",
            "code": "domain-b",
            "domain": "shared.example.com",
        },
        headers=auth_headers,
    )
    assert response.status_code == 409
