"""Quota endpoint tests."""

import uuid

import pytest
from httpx import AsyncClient


@pytest.mark.asyncio
async def test_create_quota(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test creating a quota."""
    response = await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "api:calls",
            "limit": 1000,
            "period": "monthly",
        },
        headers=auth_headers,
    )
    assert response.status_code == 201
    data = response.json()
    assert data["data"]["resource_type"] == "api:calls"
    assert data["data"]["limit"] == 1000


@pytest.mark.asyncio
async def test_create_duplicate_quota(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test creating a duplicate quota."""
    await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "dup:resource",
            "limit": 100,
        },
        headers=auth_headers,
    )
    response = await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "dup:resource",
            "limit": 200,
        },
        headers=auth_headers,
    )
    assert response.status_code == 409


@pytest.mark.asyncio
async def test_get_tenant_quotas(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test getting all quotas for a tenant."""
    await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "quota:test:a",
            "limit": 100,
        },
        headers=auth_headers,
    )
    await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "quota:test:b",
            "limit": 200,
        },
        headers=auth_headers,
    )

    response = await client.get(f"/api/v1/quotas/{test_tenant.id}", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert len(data["data"]) >= 2


@pytest.mark.asyncio
async def test_get_quota_usage(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test getting quota usage."""
    await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "usage:test",
            "limit": 500,
        },
        headers=auth_headers,
    )

    response = await client.get(
        f"/api/v1/quotas/{test_tenant.id}/usage:test/usage",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["limit"] == 500
    assert data["data"]["used"] == 0
    assert data["data"]["remaining"] == 500


@pytest.mark.asyncio
async def test_update_quota(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test updating a quota limit."""
    await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "update:test",
            "limit": 100,
        },
        headers=auth_headers,
    )

    response = await client.patch(
        f"/api/v1/quotas/{test_tenant.id}/update:test",
        json={"limit": 500},
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["limit"] == 500


@pytest.mark.asyncio
async def test_delete_quota(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test deleting a quota."""
    await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "delete:test",
            "limit": 100,
        },
        headers=auth_headers,
    )

    response = await client.delete(
        f"/api/v1/quotas/{test_tenant.id}/delete:test",
        headers=auth_headers,
    )
    assert response.status_code == 200

    get_resp = await client.get(
        f"/api/v1/quotas/{test_tenant.id}/delete:test/usage",
        headers=auth_headers,
    )
    assert get_resp.status_code == 404


@pytest.mark.asyncio
async def test_record_usage(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test recording usage."""
    await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "record:test",
            "limit": 1000,
        },
        headers=auth_headers,
    )

    response = await client.post(
        "/api/v1/quotas/usage",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "record:test",
            "amount": 5,
            "description": "Test usage",
        },
        headers=auth_headers,
    )
    assert response.status_code == 201

    usage_resp = await client.get(
        f"/api/v1/quotas/{test_tenant.id}/record:test/usage",
        headers=auth_headers,
    )
    assert usage_resp.status_code == 200
    usage_data = usage_resp.json()
    assert usage_data["data"]["used"] == 5


@pytest.mark.asyncio
async def test_list_usage_logs(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test listing usage logs."""
    await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "log:test",
            "limit": 1000,
        },
        headers=auth_headers,
    )

    await client.post(
        "/api/v1/quotas/usage",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "log:test",
            "amount": 1,
        },
        headers=auth_headers,
    )

    response = await client.get(
        f"/api/v1/quotas/{test_tenant.id}/usage-logs",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["total"] >= 1


@pytest.mark.asyncio
async def test_reset_quota_usage(client: AsyncClient, auth_headers: dict, test_tenant) -> None:
    """Test resetting quota usage."""
    await client.post(
        "/api/v1/quotas",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "reset:test",
            "limit": 1000,
        },
        headers=auth_headers,
    )

    await client.post(
        "/api/v1/quotas/usage",
        json={
            "tenant_id": str(test_tenant.id),
            "resource_type": "reset:test",
            "amount": 50,
        },
        headers=auth_headers,
    )

    response = await client.post(
        f"/api/v1/quotas/{test_tenant.id}/reset:test/reset",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["used"] == 0
