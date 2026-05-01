"""AI 追爆助手模块 Schema。

包含热点数据、账号画像、追热任务、爆款视频分析等 Schema。
"""

import uuid
from datetime import datetime

from pydantic import BaseModel, Field


# ==================== Trend Schemas ====================

class TrendBase(BaseModel):
    """热点数据基础 Schema。"""
    platform: str = Field(..., max_length=50)
    title: str = Field(..., max_length=500)
    content: str | None = None
    url: str | None = Field(None, max_length=1000)
    hot_value: int = Field(default=0, ge=0)
    category: str | None = Field(None, max_length=100)
    tags: str | None = Field(None, max_length=500)
    source_id: str = Field(..., max_length=255)
    published_at: str | None = None


class TrendCreate(TrendBase):
    """创建热点数据 Schema。"""
    pass


class TrendUpdate(BaseModel):
    """更新热点数据 Schema。"""
    title: str | None = Field(None, max_length=500)
    content: str | None = None
    hot_value: int | None = Field(None, ge=0)
    category: str | None = Field(None, max_length=100)
    tags: str | None = Field(None, max_length=500)


class TrendResponse(TrendBase):
    """热点数据响应 Schema。"""
    id: uuid.UUID
    vector_embedding: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ==================== Account Profile Schemas ====================

class AccountProfileBase(BaseModel):
    """账号画像基础 Schema。"""
    name: str = Field(..., max_length=255)
    description: str | None = None
    platform: str = Field(..., max_length=50)
    account_id: str = Field(..., max_length=255)
    avatar_url: str | None = Field(None, max_length=1000)
    tags: str | None = Field(None, max_length=1000)
    style_config: str | None = None
    tone_config: str | None = None
    target_audience: str | None = Field(None, max_length=500)
    content_categories: str | None = Field(None, max_length=500)


class AccountProfileCreate(AccountProfileBase):
    """创建账号画像 Schema。"""
    pass


class AccountProfileUpdate(BaseModel):
    """更新账号画像 Schema。"""
    name: str | None = Field(None, max_length=255)
    description: str | None = None
    avatar_url: str | None = Field(None, max_length=1000)
    tags: str | None = Field(None, max_length=1000)
    style_config: str | None = None
    tone_config: str | None = None
    target_audience: str | None = Field(None, max_length=500)
    content_categories: str | None = Field(None, max_length=500)


class AccountProfileResponse(AccountProfileBase):
    """账号画像响应 Schema。"""
    id: uuid.UUID
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ==================== Trend Task Schemas ====================

class TrendTaskCreate(BaseModel):
    """创建追热任务 Schema。"""
    trend_id: uuid.UUID
    account_profile_id: uuid.UUID
    priority: int = Field(default=0, ge=0, le=100)


class TrendTaskUpdate(BaseModel):
    """更新追热任务 Schema。"""
    status: str | None = Field(None, max_length=50)
    result_content: str | None = None
    result_video_url: str | None = Field(None, max_length=1000)
    error_message: str | None = Field(None, max_length=1000)


class TrendTaskResponse(BaseModel):
    """追热任务响应 Schema。"""
    id: uuid.UUID
    trend_id: uuid.UUID
    account_profile_id: uuid.UUID
    relevance_score: float
    status: str
    priority: int
    result_content: str | None = None
    result_video_url: str | None = None
    error_message: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class TrendTaskDetailResponse(TrendTaskResponse):
    """追热任务详情响应（包含关联数据）。"""
    trend: TrendResponse | None = None
    account_profile: AccountProfileResponse | None = None


# ==================== Viral Video Analysis Schemas ====================

class ViralVideoAnalysisBase(BaseModel):
    """爆款视频分析基础 Schema。"""
    video_id: str = Field(..., max_length=255)
    platform: str = Field(..., max_length=50)
    title: str = Field(..., max_length=500)
    url: str | None = Field(None, max_length=1000)
    view_count: int = Field(default=0, ge=0)
    like_count: int = Field(default=0, ge=0)
    comment_count: int = Field(default=0, ge=0)
    share_count: int = Field(default=0, ge=0)
    duration: int | None = None
    tags: str | None = Field(None, max_length=1000)
    structure_analysis: str | None = None
    emotion_analysis: str | None = None
    key_elements: str | None = None
    success_factors: str | None = None
    viral_score: float = Field(default=0.0, ge=0.0, le=1.0)


class ViralVideoAnalysisCreate(ViralVideoAnalysisBase):
    """创建爆款视频分析 Schema。"""
    pass


class ViralVideoAnalysisUpdate(BaseModel):
    """更新爆款视频分析 Schema。"""
    structure_analysis: str | None = None
    emotion_analysis: str | None = None
    key_elements: str | None = None
    success_factors: str | None = None
    viral_score: float | None = Field(None, ge=0.0, le=1.0)


class ViralVideoAnalysisResponse(ViralVideoAnalysisBase):
    """爆款视频分析响应 Schema。"""
    id: uuid.UUID
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}
