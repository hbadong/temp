"""Audit log endpoint tests."""

import uuid

import pytest
from httpx import AsyncClient


@pytest.mark.asyncio
async def test_list_audit_logs_empty(client: AsyncClient, auth_headers: dict) -> None:
    """Test listing audit logs when empty."""
    response = await client.get("/api/v1/audit-logs", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["code"] == 200
    assert data["data"]["total"] == 0


@pytest.mark.asyncio
async def test_list_audit_logs_with_filters(client: AsyncClient, auth_headers: dict) -> None:
    """Test listing audit logs with filters."""
    response = await client.get(
        "/api/v1/audit-logs?action=create&resource_type=User",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["code"] == 200


@pytest.mark.asyncio
async def test_get_audit_log_not_found(client: AsyncClient, auth_headers: dict) -> None:
    """Test getting a non-existent audit log."""
    fake_id = str(uuid.uuid4())
    response = await client.get(f"/api/v1/audit-logs/{fake_id}", headers=auth_headers)
    assert response.status_code == 404


@pytest.mark.asyncio
async def test_get_resource_audit_logs(client: AsyncClient, auth_headers: dict) -> None:
    """Test getting audit logs for a specific resource."""
    response = await client.get(
        "/api/v1/audit-logs/resources/Tenant/some-id",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["code"] == 200


@pytest.mark.asyncio
async def test_unauthorized_audit_access(client: AsyncClient) -> None:
    """Test accessing audit logs without authentication."""
    response = await client.get("/api/v1/audit-logs")
    assert response.status_code == 401


@pytest.mark.asyncio
async def test_list_audit_logs_pagination(client: AsyncClient, auth_headers: dict) -> None:
    """Test audit log pagination."""
    response = await client.get(
        "/api/v1/audit-logs?page=1&page_size=5",
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["page"] == 1
    assert data["data"]["page_size"] == 5
