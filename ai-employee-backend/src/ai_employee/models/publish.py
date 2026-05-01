"""内容发布与媒体服务模块模型。

包含发布任务、媒体文件、BGM、封面图等模型。
"""

import uuid

from sqlalchemy import Float, ForeignKey, Integer, String, Text
from sqlalchemy.orm import Mapped, mapped_column, relationship

from ai_employee.models.base import BaseModel


class PublishTask(BaseModel):
    """发布任务模型。

    存储内容发布任务的详细信息。
    """

    __tablename__ = "publish_tasks"

    draft_id: Mapped[str] = mapped_column(
        String(36),
        ForeignKey("content_drafts.id"),
        nullable=False,
        index=True,
    )
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    title: Mapped[str] = mapped_column(String(500), nullable=False)
    content: Mapped[str] = mapped_column(Text, nullable=False)
    media_files: Mapped[str | None] = mapped_column(Text, nullable=True)
    scheduled_at: Mapped[str | None] = mapped_column(String(50), nullable=True)
    status: Mapped[str] = mapped_column(String(50), default="pending", nullable=False, index=True)
    publish_url: Mapped[str | None] = mapped_column(String(1000), nullable=True)
    error_message: Mapped[str | None] = mapped_column(String(1000), nullable=True)
    retry_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    publish_result: Mapped[str | None] = mapped_column(Text, nullable=True)
    celery_task_id: Mapped[str | None] = mapped_column(String(255), nullable=True)

    def __repr__(self) -> str:
        return f"<PublishTask(id={self.id}, platform={self.platform}, status={self.status})>"


class MediaFile(BaseModel):
    """媒体文件模型。

    存储上传的媒体文件信息（图片、视频、音频）。
    """

    __tablename__ = "media_files"

    filename: Mapped[str] = mapped_column(String(255), nullable=False)
    original_filename: Mapped[str] = mapped_column(String(255), nullable=False)
    file_type: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    file_size: Mapped[int] = mapped_column(Integer, nullable=False)
    mime_type: Mapped[str] = mapped_column(String(100), nullable=False)
    storage_path: Mapped[str] = mapped_column(String(500), nullable=False)
    thumbnail_path: Mapped[str | None] = mapped_column(String(500), nullable=True)
    width: Mapped[int | None] = mapped_column(Integer, nullable=True)
    height: Mapped[int | None] = mapped_column(Integer, nullable=True)
    duration: Mapped[float | None] = mapped_column(Float, nullable=True)
    tags: Mapped[str | None] = mapped_column(String(500), nullable=True)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)

    def __repr__(self) -> str:
        return f"<MediaFile(id={self.id}, filename={self.filename}, type={self.file_type})>"


class BGM(BaseModel):
    """背景音乐模型。

    存储 BGM 库信息。
    """

    __tablename__ = "bgms"

    title: Mapped[str] = mapped_column(String(255), nullable=False)
    artist: Mapped[str | None] = mapped_column(String(255), nullable=True)
    genre: Mapped[str | None] = mapped_column(String(100), nullable=True, index=True)
    mood: Mapped[str | None] = mapped_column(String(100), nullable=True)
    duration: Mapped[float] = mapped_column(Float, nullable=False)
    bpm: Mapped[int | None] = mapped_column(Integer, nullable=True)
    storage_path: Mapped[str] = mapped_column(String(500), nullable=False)
    cover_image: Mapped[str | None] = mapped_column(String(500), nullable=True)
    tags: Mapped[str | None] = mapped_column(String(500), nullable=True)
    is_popular: Mapped[bool] = mapped_column(default=False, nullable=False)
    usage_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)

    def __repr__(self) -> str:
        return f"<BGM(id={self.id}, title={self.title})>"


class CoverTemplate(BaseModel):
    """封面图模板模型。

    存储封面图生成模板。
    """

    __tablename__ = "cover_templates"

    name: Mapped[str] = mapped_column(String(255), nullable=False)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    template_type: Mapped[str] = mapped_column(String(50), nullable=False)
    width: Mapped[int] = mapped_column(Integer, nullable=False)
    height: Mapped[int] = mapped_column(Integer, nullable=False)
    background_color: Mapped[str | None] = mapped_column(String(50), nullable=True)
    font_family: Mapped[str | None] = mapped_column(String(100), nullable=True)
    font_size: Mapped[int | None] = mapped_column(Integer, nullable=True)
    text_color: Mapped[str | None] = mapped_column(String(50), nullable=True)
    is_system: Mapped[bool] = mapped_column(default=False, nullable=False)

    def __repr__(self) -> str:
        return f"<CoverTemplate(id={self.id}, name={self.name}, platform={self.platform})>"
