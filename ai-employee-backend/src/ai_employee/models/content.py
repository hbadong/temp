"""AI 创作助手模块模型。

包含内容模板、内容草稿、人设、内容排期等模型。
"""

import uuid

from sqlalchemy import Float, ForeignKey, Integer, String, Text
from sqlalchemy.orm import Mapped, mapped_column, relationship

from ai_employee.models.base import BaseModel


class ContentTemplate(BaseModel):
    """内容模板模型。

    存储内容创作的模板，支持变量注入。
    """

    __tablename__ = "content_templates"

    name: Mapped[str] = mapped_column(String(255), nullable=False)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    content_type: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    template_body: Mapped[str] = mapped_column(Text, nullable=False)
    variables: Mapped[str | None] = mapped_column(Text, nullable=True)
    is_system: Mapped[bool] = mapped_column(default=False, nullable=False)
    usage_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)

    # Relationships
    drafts = relationship("ContentDraft", back_populates="template", lazy="selectin")

    def __repr__(self) -> str:
        return f"<ContentTemplate(id={self.id}, name={self.name}, platform={self.platform})>"


class Persona(BaseModel):
    """人设模型。

    存储 AI 创作的人设配置，包括风格、语气、样本等。
    """

    __tablename__ = "personas"

    name: Mapped[str] = mapped_column(String(255), nullable=False)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    style_config: Mapped[str | None] = mapped_column(Text, nullable=True)
    tone_config: Mapped[str | None] = mapped_column(Text, nullable=True)
    prompt_template: Mapped[str | None] = mapped_column(Text, nullable=True)
    sample_contents: Mapped[str | None] = mapped_column(Text, nullable=True)
    forbidden_words: Mapped[str | None] = mapped_column(Text, nullable=True)
    preferred_topics: Mapped[str | None] = mapped_column(String(1000), nullable=True)

    # Relationships
    drafts = relationship("ContentDraft", back_populates="persona", lazy="selectin")

    def __repr__(self) -> str:
        return f"<Persona(id={self.id}, name={self.name}, platform={self.platform})>"


class ContentDraft(BaseModel):
    """内容草稿模型。

    存储 AI 生成的内容草稿，支持多版本。
    """

    __tablename__ = "content_drafts"

    title: Mapped[str] = mapped_column(String(500), nullable=False)
    content: Mapped[str] = mapped_column(Text, nullable=False)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    content_type: Mapped[str] = mapped_column(String(50), nullable=False)
    template_id: Mapped[str | None] = mapped_column(
        String(36),
        ForeignKey("content_templates.id"),
        nullable=True,
        index=True,
    )
    persona_id: Mapped[str | None] = mapped_column(
        String(36),
        ForeignKey("personas.id"),
        nullable=True,
        index=True,
    )
    version: Mapped[int] = mapped_column(Integer, default=1, nullable=False)
    status: Mapped[str] = mapped_column(String(50), default="draft", nullable=False, index=True)
    compliance_score: Mapped[float | None] = mapped_column(Float, nullable=True)
    compliance_issues: Mapped[str | None] = mapped_column(Text, nullable=True)
    source_trend_id: Mapped[str | None] = mapped_column(String(36), nullable=True)
    tags: Mapped[str | None] = mapped_column(String(500), nullable=True)
    cover_image_url: Mapped[str | None] = mapped_column(String(1000), nullable=True)

    # Relationships
    template = relationship("ContentTemplate", back_populates="drafts", lazy="selectin")
    persona = relationship("Persona", back_populates="drafts", lazy="selectin")

    def __repr__(self) -> str:
        return f"<ContentDraft(id={self.id}, title={self.title}, version={self.version})>"


class ContentSchedule(BaseModel):
    """内容排期模型。

    存储内容的发布排期计划。
    """

    __tablename__ = "content_schedules"

    draft_id: Mapped[str] = mapped_column(
        String(36),
        ForeignKey("content_drafts.id"),
        nullable=False,
        index=True,
    )
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    scheduled_at: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    status: Mapped[str] = mapped_column(String(50), default="scheduled", nullable=False, index=True)
    publish_result: Mapped[str | None] = mapped_column(Text, nullable=True)
    error_message: Mapped[str | None] = mapped_column(String(1000), nullable=True)
    retry_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)

    def __repr__(self) -> str:
        return f"<ContentSchedule(id={self.id}, platform={self.platform}, scheduled_at={self.scheduled_at})>"
