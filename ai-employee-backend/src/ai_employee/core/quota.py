"""配额管理系统。

实现租户配额检查、用量统计和超限拦截。
"""

from datetime import UTC, datetime

from fastapi import HTTPException, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.tenant import Tenant


class QuotaExceededError(Exception):
    """配额超出异常。"""

    def __init__(self, resource: str, limit: int, used: int) -> None:
        self.resource = resource
        self.limit = limit
        self.used = used
        super().__init__(f"配额超出: {resource} 已使用 {used}/{limit}")


async def check_quota(
    db: AsyncSession,
    tenant_id: str,
    resource: str,
    amount: int = 1,
) -> bool:
    """检查租户配额。

    Args:
        db: 数据库会话
        tenant_id: 租户ID
        resource: 资源类型 (tasks, storage_gb, agents)
        amount: 需要使用的数量

    Returns:
        bool: 配额是否充足

    Raises:
        QuotaExceededError: 配额不足时抛出
    """
    result = await db.execute(select(Tenant).where(Tenant.id == tenant_id))
    tenant = result.scalar_one_or_none()

    if tenant is None:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="租户不存在",
        )

    # 检查租户状态
    if tenant.status != "active" and tenant.status != "trial":
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail=f"租户状态为 {tenant.status}，无法使用",
        )

    # 检查试用期
    if tenant.status == "trial" and tenant.trial_ends_at:
        if datetime.now(UTC) > tenant.trial_ends_at:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="试用期已过期，请升级套餐",
            )

    # 检查订阅过期
    if tenant.expires_at and tenant.status == "active":
        if datetime.now(UTC) > tenant.expires_at:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="订阅已过期，请续费",
            )

    # 获取配额和用量
    quota = tenant.quota or {}
    usage = tenant.usage or {}

    limit = quota.get(resource)
    used = usage.get(resource, 0)

    if limit is None:
        # 没有配置配额限制，允许使用
        return True

    if used + amount > limit:
        raise QuotaExceededError(
            resource=resource,
            limit=limit,
            used=used,
        )

    return True


async def increment_usage(
    db: AsyncSession,
    tenant_id: str,
    resource: str,
    amount: int = 1,
) -> None:
    """增加资源使用量。

    Args:
        db: 数据库会话
        tenant_id: 租户ID
        resource: 资源类型
        amount: 增加的数量
    """
    result = await db.execute(select(Tenant).where(Tenant.id == tenant_id))
    tenant = result.scalar_one_or_none()

    if tenant is None:
        return

    usage = tenant.usage or {}
    current = usage.get(resource, 0)
    usage[resource] = current + amount
    tenant.usage = usage
    await db.flush()


def get_quota_checker(resource: str, amount: int = 1):
    """创建配额检查依赖。

    用于 FastAPI 依赖注入。

    Args:
        resource: 资源类型
        amount: 需要使用的数量

    Returns:
        依赖函数
    """
    async def _check_quota(
        tenant_id: str,
        db: AsyncSession,
    ) -> bool:
        return await check_quota(db, tenant_id, resource, amount)
    return _check_quota
