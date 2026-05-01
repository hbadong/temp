"""探店笔记生成服务。"""

import json
import uuid

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.map import POI, StoreVisitNote


class StoreVisitNoteService:
    """探店笔记生成服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def create_note(self, tenant_id: str, **kwargs) -> StoreVisitNote:
        """创建探店笔记。"""
        note = StoreVisitNote(tenant_id=tenant_id, **kwargs)
        self.db.add(note)
        await self.db.commit()
        await self.db.refresh(note)
        return note

    async def get_note_by_id(self, note_id: uuid.UUID) -> StoreVisitNote | None:
        """根据 ID 获取笔记。"""
        result = await self.db.execute(
            select(StoreVisitNote).where(StoreVisitNote.id == str(note_id))
        )
        return result.scalar_one_or_none()

    async def list_notes(
        self,
        tenant_id: str,
        platform: str | None = None,
        status: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[StoreVisitNote], int]:
        """获取笔记列表。"""
        query = select(StoreVisitNote).where(StoreVisitNote.tenant_id == tenant_id)

        if platform:
            query = query.where(StoreVisitNote.platform == platform)
        if status:
            query = query.where(StoreVisitNote.status == status)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(StoreVisitNote.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def update_note(self, note_id: uuid.UUID, **kwargs) -> StoreVisitNote | None:
        """更新笔记。"""
        note = await self.get_note_by_id(note_id)
        if not note:
            return None
        for key, value in kwargs.items():
            if hasattr(note, key) and value is not None:
                setattr(note, key, value)
        await self.db.commit()
        await self.db.refresh(note)
        return note

    async def delete_note(self, note_id: uuid.UUID) -> bool:
        """删除笔记。"""
        note = await self.get_note_by_id(note_id)
        if not note:
            return False
        await self.db.delete(note)
        await self.db.commit()
        return True

    async def generate_note(
        self,
        tenant_id: str,
        poi_id: uuid.UUID,
        platform: str,
        style: str = "professional",
    ) -> StoreVisitNote:
        """根据 POI 信息生成探店笔记（模拟 AI 生成）。

        Args:
            tenant_id: 租户 ID
            poi_id: POI ID
            platform: 发布平台 (xiaohongshu/douyin/kuaishou/weibo)
            style: 风格 (professional/casual/emotional/humorous)

        Returns:
            StoreVisitNote: 生成的探店笔记
        """
        result = await self.db.execute(select(POI).where(POI.id == str(poi_id)))
        poi = result.scalar_one_or_none()
        if not poi:
            raise ValueError(f"POI not found: {poi_id}")

        # 模拟 AI 生成内容
        style_templates = {
            "professional": {
                "title_template": "【探店】{name} 深度体验报告",
                "content_template": (
                    "今天探访了位于 {address} 的 {name}。\n\n"
                    "这家店主要经营 {category} 业务，整体环境非常不错。\n\n"
                    "详细体验：\n"
                    "1. 环境：整洁明亮，氛围舒适\n"
                    "2. 服务：态度专业，响应及时\n"
                    "3. 产品：品质可靠，选择丰富\n\n"
                    "总结：值得推荐的优质商户，适合各类消费群体。"
                ),
            },
            "casual": {
                "title_template": "打卡 {name}！意外的惊喜",
                "content_template": (
                    "姐妹们！今天发现了一家宝藏店铺 {name}！\n\n"
                    "位置在 {address}，主打 {category}～\n\n"
                    "整体感觉超棒的，环境好看，服务态度也很好！\n"
                    "已经安利给身边的小伙伴了，强烈推荐大家去试试！"
                ),
            },
            "emotional": {
                "title_template": "在 {name} 找到了久违的温暖",
                "content_template": (
                    "每次来到 {name}，都有一种回到家的感觉。\n\n"
                    "这家位于 {address} 的小店，专注于 {category}，\n"
                    "不仅仅是一家店铺，更像是一个温暖的港湾。\n\n"
                    "店主的热情和服务让人感动，每一个细节都充满了用心。\n"
                    "在这个快节奏的城市里，能有这样一处地方，真的很幸运。"
                ),
            },
            "humorous": {
                "title_template": "探店翻车？{name} 让我真香了！",
                "content_template": (
                    "说实话，去 {name} 之前我内心是拒绝的。\n\n"
                    "毕竟 {address} 那边 {category} 的店太多了，\n"
                    "结果去了之后...真香警告！\n\n"
                    "环境比我想象的好太多，服务态度也超nice，\n"
                    "本来只想逛逛，结果忍不住买了一大堆！\n"
                    "钱包哭了，但我笑了～"
                ),
            },
        }

        template = style_templates.get(style, style_templates["professional"])

        title = template["title_template"].format(name=poi.name or "未知店铺")
        content = template["content_template"].format(
            name=poi.name or "这家店",
            address=poi.address or "某地",
            category=poi.category or "相关",
        )

        note = StoreVisitNote(
            tenant_id=tenant_id,
            poi_id=str(poi_id),
            title=title,
            content=content,
            platform=platform,
            style=style,
            tags=json.dumps([poi.category] if poi.category else ["探店"]),
            status="draft",
        )
        self.db.add(note)
        await self.db.commit()
        await self.db.refresh(note)
        return note
