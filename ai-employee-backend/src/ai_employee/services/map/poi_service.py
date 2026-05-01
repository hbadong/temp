"""POI 数据清洗与去重服务。"""

import json
import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.map import POI


class POIService:
    """POI 数据清洗与去重服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def add_poi(self, tenant_id: str, **kwargs) -> POI:
        """添加 POI。"""
        poi = POI(tenant_id=tenant_id, **kwargs)
        self.db.add(poi)
        await self.db.commit()
        await self.db.refresh(poi)
        return poi

    async def add_pois_batch(self, tenant_id: str, pois_data: list[dict]) -> list[POI]:
        """批量添加 POI（自动去重）。"""
        new_pois = []
        for data in pois_data:
            # 检查 source_id 是否已存在
            existing = await self.get_by_source_id(data["source_id"], data["platform"])
            if not existing:
                poi = POI(tenant_id=tenant_id, **data)
                self.db.add(poi)
                new_pois.append(poi)

        if new_pois:
            await self.db.commit()
            for poi in new_pois:
                await self.db.refresh(poi)

        return new_pois

    async def get_by_source_id(self, source_id: str, platform: str) -> POI | None:
        """根据 source_id 和平台获取 POI。"""
        result = await self.db.execute(
            select(POI).where(
                POI.source_id == source_id,
                POI.platform == platform,
            )
        )
        return result.scalar_one_or_none()

    async def get_poi_by_id(self, poi_id: uuid.UUID) -> POI | None:
        """根据 ID 获取 POI。"""
        result = await self.db.execute(
            select(POI).where(POI.id == str(poi_id))
        )
        return result.scalar_one_or_none()

    async def list_pois(
        self,
        tenant_id: str,
        platform: str | None = None,
        category: str | None = None,
        is_processed: bool | None = None,
        min_score: float | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[POI], int]:
        """获取 POI 列表。"""
        query = select(POI).where(POI.tenant_id == tenant_id)

        if platform:
            query = query.where(POI.platform == platform)
        if category:
            query = query.where(POI.category == category)
        if is_processed is not None:
            query = query.where(POI.is_processed == is_processed)
        if min_score is not None:
            query = query.where(POI.lead_score >= min_score)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(POI.lead_score.desc().nulls_last(), POI.created_at.desc())
            .offset(skip)
            .limit(limit)
        )
        return result.scalars().all(), total

    async def update_poi(self, poi_id: uuid.UUID, **kwargs) -> POI | None:
        """更新 POI。"""
        poi = await self.get_poi_by_id(poi_id)
        if not poi:
            return None
        for key, value in kwargs.items():
            if hasattr(poi, key) and value is not None:
                setattr(poi, key, value)
        await self.db.commit()
        await self.db.refresh(poi)
        return poi

    async def search_nearby(
        self,
        tenant_id: str,
        lat: float,
        lng: float,
        radius: float = 5000,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[POI], int]:
        """搜索附近的 POI（简化版，实际应使用空间索引）。"""
        # 简化实现：基于经纬度范围过滤
        lat_range = radius / 111000  # 1 度纬度约 111km
        lng_range = radius / (111000 * 0.8)  # 简化经度计算

        query = select(POI).where(
            POI.tenant_id == tenant_id,
            POI.lat.between(lat - lat_range, lat + lat_range),
            POI.lng.between(lng - lng_range, lng + lng_range),
        )

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(POI.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def simulate_search(
        self,
        tenant_id: str,
        platform: str,
        keywords: list[str],
        city: str,
        task_id: uuid.UUID,
    ) -> list[POI]:
        """模拟地图搜索（实际应调用高德/百度 API）。

        Args:
            tenant_id: 租户 ID
            platform: 平台 (amap/baidu)
            keywords: 搜索关键词
            city: 城市
            task_id: 任务 ID

        Returns:
            list[POI]: 搜索到的 POI 列表
        """
        # 模拟 POI 数据
        mock_pois = [
            {
                "source_id": f"{platform}_{city}_poi_1",
                "name": f"{keywords[0]} 旗舰店",
                "category": "购物",
                "address": f"{city}市朝阳区某某街道 123 号",
                "phone": "010-12345678",
                "lat": 39.908823,
                "lng": 116.397470,
                "platform": platform,
            },
            {
                "source_id": f"{platform}_{city}_poi_2",
                "name": f"{keywords[0]} 体验店",
                "category": "购物",
                "address": f"{city}市海淀区某某路 456 号",
                "phone": "010-87654321",
                "lat": 39.958823,
                "lng": 116.337470,
                "platform": platform,
            },
            {
                "source_id": f"{platform}_{city}_poi_3",
                "name": f"{keywords[0]} 专卖店",
                "category": "零售",
                "address": f"{city}市西城区某某街 789 号",
                "phone": None,
                "lat": 39.918823,
                "lng": 116.367470,
                "platform": platform,
            },
        ]

        return await self.add_pois_batch(tenant_id, mock_pois)
