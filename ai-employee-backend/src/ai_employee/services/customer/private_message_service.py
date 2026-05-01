"""私信管理服务。"""

import uuid

from sqlalchemy import select, func
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.customer import PrivateMessage


class PrivateMessageService:
    """私信管理服务。"""

    def __init__(self, db: AsyncSession) -> None:
        self.db = db

    async def add_message(self, tenant_id: str, **kwargs) -> PrivateMessage:
        """添加私信。"""
        message = PrivateMessage(tenant_id=tenant_id, **kwargs)
        self.db.add(message)
        await self.db.commit()
        await self.db.refresh(message)
        return message

    async def get_message_by_id(self, message_id: uuid.UUID) -> PrivateMessage | None:
        """根据 ID 获取私信。"""
        result = await self.db.execute(
            select(PrivateMessage).where(PrivateMessage.id == str(message_id))
        )
        return result.scalar_one_or_none()

    async def list_messages(
        self,
        tenant_id: str,
        platform: str | None = None,
        conversation_id: str | None = None,
        direction: str | None = None,
        is_read: bool | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[PrivateMessage], int]:
        """获取私信列表。"""
        query = select(PrivateMessage).where(PrivateMessage.tenant_id == tenant_id)

        if platform:
            query = query.where(PrivateMessage.platform == platform)
        if conversation_id:
            query = query.where(PrivateMessage.conversation_id == conversation_id)
        if direction:
            query = query.where(PrivateMessage.direction == direction)
        if is_read is not None:
            query = query.where(PrivateMessage.is_read == is_read)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(PrivateMessage.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def mark_as_read(self, message_id: uuid.UUID) -> PrivateMessage | None:
        """标记为已读。"""
        message = await self.get_message_by_id(message_id)
        if not message:
            return None
        message.is_read = True
        await self.db.commit()
        await self.db.refresh(message)
        return message

    async def mark_conversation_as_read(self, conversation_id: str) -> int:
        """标记整个会话为已读。"""
        result = await self.db.execute(
            select(PrivateMessage).where(
                PrivateMessage.conversation_id == conversation_id,
                PrivateMessage.is_read == False,
            )
        )
        messages = result.scalars().all()
        for msg in messages:
            msg.is_read = True
        await self.db.commit()
        return len(messages)

    async def get_conversations(
        self,
        tenant_id: str,
        platform: str | None = None,
    ) -> list[dict]:
        """获取会话列表。"""
        query = (
            select(
                PrivateMessage.conversation_id,
                PrivateMessage.platform,
                PrivateMessage.sender_id,
                PrivateMessage.sender_name,
                func.max(PrivateMessage.content).label("last_message"),
                func.max(PrivateMessage.message_time).label("last_message_time"),
                func.count(PrivateMessage.id).label("message_count"),
            )
            .where(PrivateMessage.tenant_id == tenant_id)
            .group_by(
                PrivateMessage.conversation_id,
                PrivateMessage.platform,
                PrivateMessage.sender_id,
                PrivateMessage.sender_name,
            )
            .order_by(func.max(PrivateMessage.message_time).desc())
        )

        if platform:
            query = query.where(PrivateMessage.platform == platform)

        result = await self.db.execute(query)
        rows = result.fetchall()

        conversations = []
        for row in rows:
            # 单独查询未读数量
            unread_result = await self.db.execute(
                select(func.count(PrivateMessage.id)).where(
                    PrivateMessage.conversation_id == row.conversation_id,
                    PrivateMessage.is_read == False,
                )
            )
            unread_count = unread_result.scalar() or 0

            conversations.append({
                "conversation_id": row.conversation_id,
                "platform": row.platform,
                "sender_id": row.sender_id,
                "sender_name": row.sender_name,
                "last_message": row.last_message,
                "last_message_time": row.last_message_time,
                "unread_count": unread_count,
                "message_count": row.message_count,
            })

        return conversations
