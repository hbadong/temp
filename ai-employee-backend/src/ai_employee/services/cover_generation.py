"""封面图生成服务。"""

import uuid
from typing import Any

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.publish import CoverTemplate


class CoverGenerationService:
    """封面图生成服务。

    支持模板化封面生成和 SDXL AI 生成（模拟实现）。
    """

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_template(self, **kwargs) -> CoverTemplate:
        """创建封面模板。"""
        template = CoverTemplate(**kwargs)
        self.db.add(template)
        await self.db.commit()
        await self.db.refresh(template)
        return template

    async def get_template_by_id(self, template_id: uuid.UUID) -> CoverTemplate | None:
        """根据 ID 获取模板。"""
        result = await self.db.execute(
            select(CoverTemplate).where(CoverTemplate.id == str(template_id))
        )
        return result.scalar_one_or_none()

    async def list_templates(
        self,
        platform: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[CoverTemplate], int]:
        """获取模板列表。"""
        query = select(CoverTemplate)
        if platform:
            query = query.where(CoverTemplate.platform == platform)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(CoverTemplate.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def update_template(
        self,
        template_id: uuid.UUID,
        **kwargs,
    ) -> CoverTemplate | None:
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

    async def generate_cover(
        self,
        title: str,
        platform: str,
        template_id: uuid.UUID | None = None,
        subtitle: str | None = None,
        background_image: str | None = None,
    ) -> dict[str, Any]:
        """生成封面图。

        Args:
            title: 标题
            platform: 平台
            template_id: 模板 ID（可选）
            subtitle: 副标题
            background_image: 背景图

        Returns:
            dict: 封面图信息
        """
        # 获取模板配置
        template = None
        if template_id:
            template = await self.get_template_by_id(template_id)

        # 根据平台设置默认尺寸
        dimensions = self._get_platform_dimensions(platform, template)

        # 模拟封面生成（实际应调用 SDXL 或图像处理库）
        cover_url = f"/covers/{platform}_{uuid.uuid4().hex[:8]}.png"
        thumbnail_url = f"/covers/{platform}_{uuid.uuid4().hex[:8]}_thumb.png"

        return {
            "cover_url": cover_url,
            "thumbnail_url": thumbnail_url,
            "width": dimensions["width"],
            "height": dimensions["height"],
        }

    def _get_platform_dimensions(
        self,
        platform: str,
        template: CoverTemplate | None = None,
    ) -> dict[str, int]:
        """获取平台推荐的封面尺寸。"""
        if template:
            return {"width": template.width, "height": template.height}

        platform_dimensions = {
            "douyin": {"width": 1080, "height": 1920},  # 9:16
            "xiaohongshu": {"width": 1080, "height": 1440},  # 3:4
            "wechat": {"width": 900, "height": 383},  # 2.35:1
            "bilibili": {"width": 1920, "height": 1080},  # 16:9
        }

        return platform_dimensions.get(platform, {"width": 1080, "height": 1080})
