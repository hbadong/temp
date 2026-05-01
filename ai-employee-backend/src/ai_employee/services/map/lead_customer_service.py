"""潜在客户服务。"""

import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.map import LeadCustomer, POI
from ai_employee.services.map.lead_scoring import LeadScoringEngine


class LeadCustomerService:
    """潜在客户服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db
        self.scoring_engine = LeadScoringEngine()

    async def create_lead(self, tenant_id: str, poi_id: uuid.UUID) -> LeadCustomer:
        """从 POI 创建潜在客户。"""
        result = await self.db.execute(select(POI).where(POI.id == str(poi_id)))
        poi = result.scalar_one_or_none()
        if not poi:
            raise ValueError(f"POI not found: {poi_id}")

        # 评分
        score_result = self.scoring_engine.score(
            name=poi.name,
            category=poi.category,
            address=poi.address,
            phone=poi.phone,
        )

        lead = LeadCustomer(
            tenant_id=tenant_id,
            poi_id=str(poi_id),
            company_name=poi.name,
            industry=poi.category,
            phone=poi.phone,
            score=score_result["score"],
            score_factors=score_result["reason"],
            status="new",
        )
        self.db.add(lead)
        await self.db.commit()
        await self.db.refresh(lead)

        # 更新 POI 的评分
        poi.lead_score = score_result["score"]
        poi.lead_score_reason = score_result["reason"]
        await self.db.commit()

        return lead

    async def get_lead_by_id(self, lead_id: uuid.UUID) -> LeadCustomer | None:
        """根据 ID 获取潜在客户。"""
        result = await self.db.execute(
            select(LeadCustomer).where(LeadCustomer.id == str(lead_id))
        )
        return result.scalar_one_or_none()

    async def list_leads(
        self,
        tenant_id: str,
        status: str | None = None,
        min_score: float | None = None,
        max_score: float | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[LeadCustomer], int]:
        """获取潜在客户列表。"""
        query = select(LeadCustomer).where(LeadCustomer.tenant_id == tenant_id)

        if status:
            query = query.where(LeadCustomer.status == status)
        if min_score is not None:
            query = query.where(LeadCustomer.score >= min_score)
        if max_score is not None:
            query = query.where(LeadCustomer.score <= max_score)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(LeadCustomer.score.desc(), LeadCustomer.created_at.desc())
            .offset(skip)
            .limit(limit)
        )
        return result.scalars().all(), total

    async def update_lead(self, lead_id: uuid.UUID, **kwargs) -> LeadCustomer | None:
        """更新潜在客户。"""
        lead = await self.get_lead_by_id(lead_id)
        if not lead:
            return None
        for key, value in kwargs.items():
            if hasattr(lead, key) and value is not None:
                setattr(lead, key, value)
        await self.db.commit()
        await self.db.refresh(lead)
        return lead
