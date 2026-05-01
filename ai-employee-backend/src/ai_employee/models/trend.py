"""AI 追爆助手模块模型。

包含热点数据、账号画像、追热任务、爆款视频分析等模型。
"""

import uuid

from sqlalchemy import Float, ForeignKey, Integer, String, Text
from sqlalchemy.orm import Mapped, mapped_column, relationship

from ai_employee.models.base import BaseModel


class Trend(BaseModel):
    """热点数据模型。

    存储从各平台抓取的热点数据，已标准化和去重。
    """

    __tablename__ = "trends"

    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    title: Mapped[str] = mapped_column(String(500), nullable=False)
    content: Mapped[str | None] = mapped_column(Text, nullable=True)
    url: Mapped[str] = mapped_column(String(1000), nullable=True)
    hot_value: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    category: Mapped[str | None] = mapped_column(String(100), nullable=True, index=True)
    tags: Mapped[str | None] = mapped_column(String(500), nullable=True)
    source_id: Mapped[str] = mapped_column(String(255), nullable=False, index=True)
    published_at: Mapped[str | None] = mapped_column(String(50), nullable=True)
    vector_embedding: Mapped[str | None] = mapped_column(Text, nullable=True)

    # Relationships
    tasks = relationship("TrendTask", back_populates="trend", lazy="selectin")

    def __repr__(self) -> str:
        return f"<Trend(id={self.id}, platform={self.platform}, title={self.title})>"


class AccountProfile(BaseModel):
    """账号画像模型。

    存储账号的人设、标签、风格等配置信息。
    """

    __tablename__ = "account_profiles"

    name: Mapped[str] = mapped_column(String(255), nullable=False)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    account_id: Mapped[str] = mapped_column(String(255), nullable=False, index=True)
    avatar_url: Mapped[str | None] = mapped_column(String(1000), nullable=True)
    tags: Mapped[str | None] = mapped_column(String(1000), nullable=True)
    style_config: Mapped[str | None] = mapped_column(Text, nullable=True)
    tone_config: Mapped[str | None] = mapped_column(Text, nullable=True)
    target_audience: Mapped[str | None] = mapped_column(String(500), nullable=True)
    content_categories: Mapped[str | None] = mapped_column(String(500), nullable=True)

    # Relationships
    tasks = relationship("TrendTask", back_populates="account_profile", lazy="selectin")

    def __repr__(self) -> str:
        return f"<AccountProfile(id={self.id}, name={self.name}, platform={self.platform})>"


class TrendTask(BaseModel):
    """追热任务模型。

    追踪特定热点与账号的相关性，生成追热内容。
    """

    __tablename__ = "trend_tasks"

    trend_id: Mapped[str] = mapped_column(
        String(36),
        ForeignKey("trends.id"),
        nullable=False,
        index=True,
    )
    account_profile_id: Mapped[str] = mapped_column(
        String(36),
        ForeignKey("account_profiles.id"),
        nullable=False,
        index=True,
    )
    relevance_score: Mapped[float] = mapped_column(Float, default=0.0, nullable=False)
    status: Mapped[str] = mapped_column(String(50), default="pending", nullable=False, index=True)
    priority: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    result_content: Mapped[str | None] = mapped_column(Text, nullable=True)
    result_video_url: Mapped[str | None] = mapped_column(String(1000), nullable=True)
    error_message: Mapped[str | None] = mapped_column(String(1000), nullable=True)

    # Relationships
    trend = relationship("Trend", back_populates="tasks", lazy="selectin")
    account_profile = relationship("AccountProfile", back_populates="tasks", lazy="selectin")

    def __repr__(self) -> str:
        return f"<TrendTask(id={self.id}, status={self.status}, score={self.relevance_score})>"


class ViralVideoAnalysis(BaseModel):
    """爆款视频分析模型。

    存储爆款视频的分析结果，包括标签、结构、情感等。
    """

    __tablename__ = "viral_video_analyses"

    video_id: Mapped[str] = mapped_column(String(255), nullable=False, unique=True, index=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    title: Mapped[str] = mapped_column(String(500), nullable=False)
    url: Mapped[str] = mapped_column(String(1000), nullable=True)
    view_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    like_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    comment_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    share_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    duration: Mapped[int | None] = mapped_column(Integer, nullable=True)
    tags: Mapped[str | None] = mapped_column(String(1000), nullable=True)
    structure_analysis: Mapped[str | None] = mapped_column(Text, nullable=True)
    emotion_analysis: Mapped[str | None] = mapped_column(Text, nullable=True)
    key_elements: Mapped[str | None] = mapped_column(Text, nullable=True)
    success_factors: Mapped[str | None] = mapped_column(Text, nullable=True)
    viral_score: Mapped[float] = mapped_column(Float, default=0.0, nullable=False)

    def __repr__(self) -> str:
        return f"<ViralVideoAnalysis(id={self.id}, platform={self.platform}, views={self.view_count})>"
