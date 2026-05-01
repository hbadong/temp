"""人设服务。"""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.content import Persona


class PersonaService:
    """人设服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create(self, tenant_id: str, **kwargs) -> Persona:
        """创建人设。"""
        persona = Persona(tenant_id=tenant_id, **kwargs)
        self.db.add(persona)
        await self.db.commit()
        await self.db.refresh(persona)
        return persona

    async def get_by_id(self, persona_id: uuid.UUID) -> Persona | None:
        """根据 ID 获取人设。"""
        result = await self.db.execute(
            select(Persona).where(Persona.id == str(persona_id))
        )
        return result.scalar_one_or_none()

    async def get_list(
        self,
        tenant_id: str,
        platform: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[Persona], int]:
        """获取人设列表。"""
        query = select(Persona).where(Persona.tenant_id == tenant_id)

        if platform:
            query = query.where(Persona.platform == platform)

        count_query = select(Persona).where(Persona.tenant_id == tenant_id)
        if platform:
            count_query = count_query.where(Persona.platform == platform)

        count_result = await self.db.execute(count_query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(query.order_by(Persona.created_at.desc()).offset(skip).limit(limit))
        return result.scalars().all(), total

    async def update(self, persona_id: uuid.UUID, **kwargs) -> Persona | None:
        """更新人设。"""
        persona = await self.get_by_id(persona_id)
        if not persona:
            return None
        for key, value in kwargs.items():
            if hasattr(persona, key) and value is not None:
                setattr(persona, key, value)
        await self.db.commit()
        await self.db.refresh(persona)
        return persona

    async def delete(self, persona_id: uuid.UUID) -> bool:
        """删除人设。"""
        persona = await self.get_by_id(persona_id)
        if not persona:
            return False
        await self.db.delete(persona)
        await self.db.commit()
        return True
