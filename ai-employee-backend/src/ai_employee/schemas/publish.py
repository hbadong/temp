"""内容发布与媒体服务模块 Schema。"""

import uuid
from datetime import datetime

from pydantic import BaseModel, Field


# ==================== Publish Task Schemas ====================

class PublishTaskCreate(BaseModel):
    """创建发布任务 Schema。"""
    draft_id: uuid.UUID
    platform: str = Field(..., max_length=50)
    title: str = Field(..., max_length=500)
    content: str
    media_files: str | None = None
    scheduled_at: str | None = None


class PublishTaskUpdate(BaseModel):
    """更新发布任务 Schema。"""
    status: str | None = Field(None, max_length=50)
    publish_url: str | None = Field(None, max_length=1000)
    error_message: str | None = Field(None, max_length=1000)
    publish_result: str | None = None


class PublishTaskResponse(BaseModel):
    """发布任务响应 Schema。"""
    id: uuid.UUID
    draft_id: uuid.UUID
    platform: str
    title: str
    content: str
    media_files: str | None = None
    scheduled_at: str | None = None
    status: str
    publish_url: str | None = None
    error_message: str | None = None
    retry_count: int
    publish_result: str | None = None
    celery_task_id: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class PublishTaskListResponse(BaseModel):
    """发布任务列表响应 Schema。"""
    tasks: list[PublishTaskResponse]
    total: int
    page: int
    page_size: int
    has_next: bool
    has_prev: bool


# ==================== Media File Schemas ====================

class MediaFileUploadResponse(BaseModel):
    """媒体文件上传响应 Schema。"""
    id: uuid.UUID
    filename: str
    original_filename: str
    file_type: str
    file_size: int
    mime_type: str
    storage_path: str
    thumbnail_path: str | None = None
    width: int | None = None
    height: int | None = None
    duration: float | None = None
    tags: str | None = None
    description: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class MediaFileListResponse(BaseModel):
    """媒体文件列表响应 Schema。"""
    files: list[MediaFileUploadResponse]
    total: int
    page: int
    page_size: int
    has_next: bool
    has_prev: bool


# ==================== BGM Schemas ====================

class BGMCreate(BaseModel):
    """创建 BGM Schema。"""
    title: str = Field(..., max_length=255)
    artist: str | None = Field(None, max_length=255)
    genre: str | None = Field(None, max_length=100)
    mood: str | None = Field(None, max_length=100)
    duration: float
    bpm: int | None = None
    storage_path: str = Field(..., max_length=500)
    tags: str | None = Field(None, max_length=500)


class BGMUpdate(BaseModel):
    """更新 BGM Schema。"""
    title: str | None = Field(None, max_length=255)
    artist: str | None = Field(None, max_length=255)
    genre: str | None = Field(None, max_length=100)
    mood: str | None = Field(None, max_length=100)
    tags: str | None = Field(None, max_length=500)
    is_popular: bool | None = None


class BGMResponse(BaseModel):
    """BGM 响应 Schema。"""
    id: uuid.UUID
    title: str
    artist: str | None = None
    genre: str | None = None
    mood: str | None = None
    duration: float
    bpm: int | None = None
    cover_image: str | None = None
    tags: str | None = None
    is_popular: bool
    usage_count: int
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class BGMRecommendRequest(BaseModel):
    """BGM 推荐请求 Schema。"""
    content_type: str = Field(..., max_length=50)
    mood: str | None = Field(None, max_length=100)
    genre: str | None = Field(None, max_length=100)
    duration_min: float | None = None
    duration_max: float | None = None


class BGMListResponse(BaseModel):
    """BGM 列表响应 Schema。"""
    bgms: list[BGMResponse]
    total: int
    page: int
    page_size: int
    has_next: bool
    has_prev: bool


# ==================== Cover Template Schemas ====================

class CoverTemplateCreate(BaseModel):
    """创建封面模板 Schema。"""
    name: str = Field(..., max_length=255)
    description: str | None = None
    platform: str = Field(..., max_length=50)
    template_type: str = Field(..., max_length=50)
    width: int
    height: int
    background_color: str | None = Field(None, max_length=50)
    font_family: str | None = Field(None, max_length=100)
    font_size: int | None = None
    text_color: str | None = Field(None, max_length=50)


class CoverTemplateUpdate(BaseModel):
    """更新封面模板 Schema。"""
    name: str | None = Field(None, max_length=255)
    description: str | None = None
    background_color: str | None = Field(None, max_length=50)
    font_family: str | None = Field(None, max_length=100)
    font_size: int | None = None
    text_color: str | None = Field(None, max_length=50)


class CoverTemplateResponse(BaseModel):
    """封面模板响应 Schema。"""
    id: uuid.UUID
    name: str
    description: str | None = None
    platform: str
    template_type: str
    width: int
    height: int
    background_color: str | None = None
    font_family: str | None = None
    font_size: int | None = None
    text_color: str | None = None
    is_system: bool
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class CoverGenerateRequest(BaseModel):
    """封面生成请求 Schema。"""
    title: str = Field(..., max_length=500)
    platform: str = Field(..., max_length=50)
    template_id: uuid.UUID | None = None
    subtitle: str | None = None
    background_image: str | None = None


class CoverGenerateResponse(BaseModel):
    """封面生成响应 Schema。"""
    cover_url: str
    thumbnail_url: str | None = None
    width: int
    height: int
