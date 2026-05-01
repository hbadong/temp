"""话术模板服务。"""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.customer import ReplyTemplate


class ReplyTemplateService:
    """话术模板服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_template(self, tenant_id: str, **kwargs) -> ReplyTemplate:
        """创建话术模板。"""
        template = ReplyTemplate(tenant_id=tenant_id, **kwargs)
        self.db.add(template)
        await self.db.commit()
        await self.db.refresh(template)
        return template

    async def get_template_by_id(self, template_id: uuid.UUID) -> ReplyTemplate | None:
        """根据 ID 获取模板。"""
        result = await self.db.execute(
            select(ReplyTemplate).where(ReplyTemplate.id == str(template_id))
        )
        return result.scalar_one_or_none()

    async def list_templates(
        self,
        tenant_id: str,
        platform: str | None = None,
        intent_type: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[ReplyTemplate], int]:
        """获取模板列表。"""
        query = select(ReplyTemplate).where(ReplyTemplate.tenant_id == tenant_id)

        if platform:
            query = query.where(ReplyTemplate.platform == platform)
        if intent_type:
            query = query.where(ReplyTemplate.intent_type == intent_type)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(ReplyTemplate.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def update_template(self, template_id: uuid.UUID, **kwargs) -> ReplyTemplate | None:
        """更新模板。"""
        template = await self.get_template_by_id(template_id)
        if not template:
            return None
        for key, value in kwargs.items():
            if hasattr(template, key) and value is not None:
                setattr(template, key, value)
        await self.db.commit()
        await self.db.refresh(template)
        return template

    async def delete_template(self, template_id: uuid.UUID) -> bool:
        """删除模板。"""
        template = await self.get_template_by_id(template_id)
        if not template:
            return False
        await self.db.delete(template)
        await self.db.commit()
        return True

    async def increment_usage(self, template_id: uuid.UUID) -> None:
        """增加使用次数。"""
        template = await self.get_template_by_id(template_id)
        if template:
            template.usage_count += 1
            await self.db.commit()

    async def find_matching_template(
        self,
        tenant_id: str,
        platform: str,
        intent_type: str | None = None,
    ) -> ReplyTemplate | None:
        """查找匹配的话术模板。"""
        query = select(ReplyTemplate).where(
            ReplyTemplate.tenant_id == tenant_id,
            ReplyTemplate.platform == platform,
        )

        if intent_type:
            query = query.where(ReplyTemplate.intent_type == intent_type)

        query = query.order_by(ReplyTemplate.is_system.desc(), ReplyTemplate.usage_count.desc())
        result = await self.db.execute(query)
        return result.scalar_one_or_none()
