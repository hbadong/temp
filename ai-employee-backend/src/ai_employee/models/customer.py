"""AI 拓客助手模块模型。

包含监控任务、评论、话术模板、私信、自动回复等模型。
"""

import uuid

from sqlalchemy import Float, ForeignKey, Integer, String, Text
from sqlalchemy.orm import Mapped, mapped_column, relationship

from ai_employee.models.base import BaseModel


class MonitorTask(BaseModel):
    """监控任务模型。

    存储内容监控任务的配置和状态。
    """

    __tablename__ = "monitor_tasks"

    name: Mapped[str] = mapped_column(String(255), nullable=False)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    target_type: Mapped[str] = mapped_column(String(50), nullable=False)  # video/post/article
    target_ids: Mapped[str | None] = mapped_column(Text, nullable=True)  # JSON 列表
    keywords: Mapped[str | None] = mapped_column(Text, nullable=True)  # JSON 列表
    schedule_config: Mapped[str | None] = mapped_column(Text, nullable=True)  # cron 表达式
    status: Mapped[str] = mapped_column(String(50), default="inactive", nullable=False, index=True)
    last_run_at: Mapped[str | None] = mapped_column(String(50), nullable=True)
    next_run_at: Mapped[str | None] = mapped_column(String(50), nullable=True)
    run_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    comment_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)

    def __repr__(self) -> str:
        return f"<MonitorTask(id={self.id}, name={self.name}, platform={self.platform})>"


class Comment(BaseModel):
    """评论模型。

    存储抓取到的评论数据。
    """

    __tablename__ = "comments"

    source_id: Mapped[str] = mapped_column(String(255), nullable=False, index=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    target_type: Mapped[str] = mapped_column(String(50), nullable=False)
    target_id: Mapped[str] = mapped_column(String(255), nullable=False, index=True)
    author_id: Mapped[str | None] = mapped_column(String(255), nullable=True)
    author_name: Mapped[str | None] = mapped_column(String(255), nullable=True)
    content: Mapped[str] = mapped_column(Text, nullable=False)
    parent_id: Mapped[str | None] = mapped_column(String(255), nullable=True)
    like_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    reply_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    comment_time: Mapped[str | None] = mapped_column(String(50), nullable=True)
    raw_data: Mapped[str | None] = mapped_column(Text, nullable=True)
    # AI 分析字段
    sentiment: Mapped[str | None] = mapped_column(String(50), nullable=True)  # positive/negative/neutral
    intent: Mapped[str | None] = mapped_column(String(50), nullable=True)  # purchase/consult/complaint/other
    intent_score: Mapped[float | None] = mapped_column(Float, nullable=True)
    is_replied: Mapped[bool] = mapped_column(default=False, nullable=False)
    reply_id: Mapped[str | None] = mapped_column(String(36), nullable=True)

    def __repr__(self) -> str:
        return f"<Comment(id={self.id}, platform={self.platform}, sentiment={self.sentiment})>"


class ReplyTemplate(BaseModel):
    """回复话术模板模型。

    存储自动回复的话术模板。
    """

    __tablename__ = "reply_templates"

    name: Mapped[str] = mapped_column(String(255), nullable=False)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    intent_type: Mapped[str | None] = mapped_column(String(50), nullable=True, index=True)
    template_body: Mapped[str] = mapped_column(Text, nullable=False)
    variables: Mapped[str | None] = mapped_column(Text, nullable=True)  # JSON
    is_system: Mapped[bool] = mapped_column(default=False, nullable=False)
    usage_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    match_keywords: Mapped[str | None] = mapped_column(Text, nullable=True)  # JSON 列表

    def __repr__(self) -> str:
        return f"<ReplyTemplate(id={self.id}, name={self.name}, intent={self.intent_type})>"


class PrivateMessage(BaseModel):
    """私信模型。

    存储私信消息记录。
    """

    __tablename__ = "private_messages"

    conversation_id: Mapped[str] = mapped_column(String(255), nullable=False, index=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    sender_id: Mapped[str] = mapped_column(String(255), nullable=False, index=True)
    sender_name: Mapped[str | None] = mapped_column(String(255), nullable=True)
    receiver_id: Mapped[str] = mapped_column(String(255), nullable=False, index=True)
    content: Mapped[str] = mapped_column(Text, nullable=False)
    direction: Mapped[str] = mapped_column(String(20), nullable=False)  # inbound/outbound
    is_read: Mapped[bool] = mapped_column(default=False, nullable=False)
    message_time: Mapped[str | None] = mapped_column(String(50), nullable=True)
    raw_data: Mapped[str | None] = mapped_column(Text, nullable=True)
    # AI 分析字段
    sentiment: Mapped[str | None] = mapped_column(String(50), nullable=True)
    intent: Mapped[str | None] = mapped_column(String(50), nullable=True)
    intent_score: Mapped[float | None] = mapped_column(Float, nullable=True)
    auto_reply_id: Mapped[str | None] = mapped_column(String(36), nullable=True)

    def __repr__(self) -> str:
        return f"<PrivateMessage(id={self.id}, platform={self.platform}, direction={self.direction})>"


class AutoReplyRule(BaseModel):
    """自动回复规则模型。

    存储自动回复的触发规则和响应配置。
    """

    __tablename__ = "auto_reply_rules"

    name: Mapped[str] = mapped_column(String(255), nullable=False)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    trigger_type: Mapped[str] = mapped_column(String(50), nullable=False)  # keyword/intent/sentiment
    trigger_config: Mapped[str | None] = mapped_column(Text, nullable=True)  # JSON
    response_template_id: Mapped[str | None] = mapped_column(String(36), nullable=True)
    response_content: Mapped[str | None] = mapped_column(Text, nullable=True)
    delay_min: Mapped[int] = mapped_column(Integer, default=0, nullable=False)  # 最小延迟 (秒)
    delay_max: Mapped[int] = mapped_column(Integer, default=0, nullable=False)  # 最大延迟 (秒)
    is_active: Mapped[bool] = mapped_column(default=True, nullable=False)
    priority: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    match_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)

    def __repr__(self) -> str:
        return f"<AutoReplyRule(id={self.id}, name={self.name}, trigger={self.trigger_type})>"
