"""Quota service tests."""

import uuid

import pytest
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.exceptions import ConflictException, NotFoundException
from ai_employee.models.quota import Quota, UsageLog
from ai_employee.models.tenant import Tenant
from ai_employee.schemas.quota import QuotaCreate, QuotaUpdate, UsageLogCreate
from ai_employee.services.quota_service import QuotaService


@pytest.fixture
async def test_tenant(db_session: AsyncSession) -> Tenant:
    """Create a test tenant."""
    t = Tenant(name="Quota Test Tenant", code="quota-test")
    db_session.add(t)
    await db_session.flush()
    await db_session.refresh(t)
    return t


@pytest.fixture
def quota_service(db_session: AsyncSession) -> QuotaService:
    """Create quota service instance."""
    return QuotaService(db_session)


@pytest.mark.asyncio
async def test_create_quota(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test creating a quota."""
    data = QuotaCreate(
        tenant_id=test_tenant.id,
        resource_type="api:calls",
        limit=1000,
        period="monthly",
    )
    quota = await quota_service.create_quota(data)

    assert quota.resource_type == "api:calls"
    assert quota.limit == 1000
    assert quota.used == 0


@pytest.mark.asyncio
async def test_create_duplicate_quota(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test creating a duplicate quota raises ConflictException."""
    data = QuotaCreate(tenant_id=test_tenant.id, resource_type="dup:test", limit=100)
    await quota_service.create_quota(data)

    with pytest.raises(ConflictException):
        await quota_service.create_quota(data)


@pytest.mark.asyncio
async def test_get_quota(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test getting a quota."""
    data = QuotaCreate(tenant_id=test_tenant.id, resource_type="get:test", limit=500)
    await quota_service.create_quota(data)

    quota = await quota_service.get_quota(test_tenant.id, "get:test")
    assert quota is not None
    assert quota.limit == 500


@pytest.mark.asyncio
async def test_get_quota_not_found(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test getting a non-existent quota returns None."""
    quota = await quota_service.get_quota(test_tenant.id, "nonexistent")
    assert quota is None


@pytest.mark.asyncio
async def test_get_all_quotas(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test getting all quotas for a tenant."""
    await quota_service.create_quota(
        QuotaCreate(tenant_id=test_tenant.id, resource_type="all:a", limit=100)
    )
    await quota_service.create_quota(
        QuotaCreate(tenant_id=test_tenant.id, resource_type="all:b", limit=200)
    )

    quotas = await quota_service.get_all_quotas(test_tenant.id)
    assert len(quotas) == 2


@pytest.mark.asyncio
async def test_update_quota(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test updating a quota limit."""
    data = QuotaCreate(tenant_id=test_tenant.id, resource_type="update:test", limit=100)
    await quota_service.create_quota(data)

    update = QuotaUpdate(limit=500)
    quota = await quota_service.update_quota(test_tenant.id, "update:test", update)
    assert quota.limit == 500


@pytest.mark.asyncio
async def test_update_quota_not_found(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test updating a non-existent quota raises NotFoundException."""
    with pytest.raises(NotFoundException):
        await quota_service.update_quota(
            test_tenant.id, "nonexistent", QuotaUpdate(limit=100)
        )


@pytest.mark.asyncio
async def test_delete_quota(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test soft deleting a quota."""
    data = QuotaCreate(tenant_id=test_tenant.id, resource_type="delete:test", limit=100)
    await quota_service.create_quota(data)

    await quota_service.delete_quota(test_tenant.id, "delete:test")

    quota = await quota_service.get_quota(test_tenant.id, "delete:test")
    assert quota is None


@pytest.mark.asyncio
async def test_record_usage(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test recording usage."""
    data = QuotaCreate(tenant_id=test_tenant.id, resource_type="usage:test", limit=1000)
    await quota_service.create_quota(data)

    usage_data = UsageLogCreate(
        tenant_id=test_tenant.id,
        resource_type="usage:test",
        amount=10,
        description="Test usage",
    )
    log = await quota_service.record_usage(usage_data)

    assert log.amount == 10

    quota = await quota_service.get_quota(test_tenant.id, "usage:test")
    assert quota.used == 10


@pytest.mark.asyncio
async def test_check_quota_available(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test checking quota availability."""
    data = QuotaCreate(tenant_id=test_tenant.id, resource_type="check:test", limit=100)
    await quota_service.create_quota(data)

    available = await quota_service.check_quota_available(test_tenant.id, "check:test", 50)
    assert available is True

    available = await quota_service.check_quota_available(test_tenant.id, "check:test", 101)
    assert available is False


@pytest.mark.asyncio
async def test_check_quota_no_quota_set(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test checking quota when no quota is set (unlimited)."""
    available = await quota_service.check_quota_available(test_tenant.id, "no:quota", 1)
    assert available is True


@pytest.mark.asyncio
async def test_reset_usage(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test resetting quota usage."""
    data = QuotaCreate(tenant_id=test_tenant.id, resource_type="reset:test", limit=1000)
    await quota_service.create_quota(data)

    usage = UsageLogCreate(tenant_id=test_tenant.id, resource_type="reset:test", amount=50)
    await quota_service.record_usage(usage)

    quota = await quota_service.reset_usage(test_tenant.id, "reset:test")
    assert quota.used == 0


@pytest.mark.asyncio
async def test_get_quota_usage(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test getting quota usage response."""
    data = QuotaCreate(tenant_id=test_tenant.id, resource_type="usage_resp:test", limit=100)
    await quota_service.create_quota(data)

    usage_data = UsageLogCreate(tenant_id=test_tenant.id, resource_type="usage_resp:test", amount=30)
    await quota_service.record_usage(usage_data)

    usage = await quota_service.get_quota_usage(test_tenant.id, "usage_resp:test")
    assert usage.limit == 100
    assert usage.used == 30
    assert usage.remaining == 70
    assert usage.is_exceeded is False


@pytest.mark.asyncio
async def test_list_usage_logs(quota_service: QuotaService, test_tenant: Tenant) -> None:
    """Test listing usage logs."""
    data = QuotaCreate(tenant_id=test_tenant.id, resource_type="log:test", limit=1000)
    await quota_service.create_quota(data)

    for i in range(3):
        usage = UsageLogCreate(tenant_id=test_tenant.id, resource_type="log:test", amount=1)
        await quota_service.record_usage(usage)

    logs = await quota_service.list_usage_logs(test_tenant.id)
    assert len(logs) == 3

    filtered = await quota_service.list_usage_logs(test_tenant.id, resource_type="log:test")
    assert len(filtered) == 3

    empty = await quota_service.list_usage_logs(test_tenant.id, resource_type="nonexistent")
    assert len(empty) == 0
