"""地图拓客模块 Schema。"""

import uuid
from datetime import datetime

from pydantic import BaseModel, Field


# ==================== Map Search Task Schemas ====================

class MapSearchTaskCreate(BaseModel):
    """创建地图搜索任务 Schema。"""
    name: str = Field(..., max_length=255)
    description: str | None = None
    platform: str = Field(..., max_length=50)
    keywords: str  # JSON 列表
    city: str = Field(..., max_length=100)
    radius: int = 5000
    center_lat: float | None = None
    center_lng: float | None = None


class MapSearchTaskUpdate(BaseModel):
    """更新地图搜索任务 Schema。"""
    name: str | None = Field(None, max_length=255)
    description: str | None = None
    status: str | None = Field(None, max_length=50)
    keywords: str | None = None


class MapSearchTaskResponse(BaseModel):
    """地图搜索任务响应 Schema。"""
    id: uuid.UUID
    name: str
    description: str | None = None
    platform: str
    keywords: str
    city: str
    radius: int
    center_lat: float | None = None
    center_lng: float | None = None
    status: str
    total_results: int
    processed_count: int
    error_message: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ==================== POI Schemas ====================

class POIResponse(BaseModel):
    """POI 响应 Schema。"""
    id: uuid.UUID
    source_id: str
    task_id: str | None = None
    name: str
    category: str | None = None
    address: str | None = None
    phone: str | None = None
    lat: float
    lng: float
    platform: str
    lead_score: float | None = None
    lead_score_reason: str | None = None
    is_processed: bool
    tags: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class POIListResponse(BaseModel):
    """POI 列表响应 Schema。"""
    pois: list[POIResponse]
    total: int
    page: int
    page_size: int
    has_next: bool
    has_prev: bool


# ==================== Lead Customer Schemas ====================

class LeadCustomerResponse(BaseModel):
    """潜在客户响应 Schema。"""
    id: uuid.UUID
    poi_id: str
    company_name: str
    industry: str | None = None
    contact_person: str | None = None
    phone: str | None = None
    email: str | None = None
    website: str | None = None
    score: float
    score_factors: str | None = None
    status: str
    notes: str | None = None
    assigned_to: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class LeadCustomerListResponse(BaseModel):
    """潜在客户列表响应 Schema。"""
    leads: list[LeadCustomerResponse]
    total: int
    page: int
    page_size: int
    has_next: bool
    has_prev: bool


# ==================== Store Visit Note Schemas ====================

class StoreVisitNoteCreate(BaseModel):
    """创建探店笔记 Schema。"""
    poi_id: uuid.UUID
    title: str = Field(..., max_length=500)
    content: str
    platform: str = Field(..., max_length=50)
    style: str | None = None
    images: str | None = None
    tags: str | None = Field(None, max_length=500)


class StoreVisitNoteUpdate(BaseModel):
    """更新探店笔记 Schema。"""
    title: str | None = Field(None, max_length=500)
    content: str | None = None
    style: str | None = None
    tags: str | None = Field(None, max_length=500)
    status: str | None = Field(None, max_length=50)
    publish_url: str | None = Field(None, max_length=1000)


class StoreVisitNoteResponse(BaseModel):
    """探店笔记响应 Schema。"""
    id: uuid.UUID
    poi_id: str
    title: str
    content: str
    platform: str
    style: str | None = None
    images: str | None = None
    tags: str | None = None
    status: str
    publish_url: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class StoreVisitNoteListResponse(BaseModel):
    """探店笔记列表响应 Schema。"""
    notes: list[StoreVisitNoteResponse]
    total: int
    page: int
    page_size: int
    has_next: bool
    has_prev: bool


# ==================== Lead Scoring Schemas ====================

class LeadScoreRequest(BaseModel):
    """潜在客户评分请求 Schema。"""
    name: str
    category: str | None = None
    address: str | None = None
    phone: str | None = None
    platform: str = Field(..., max_length=50)


class LeadScoreResponse(BaseModel):
    """潜在客户评分响应 Schema。"""
    score: float
    factors: list[str]
    reason: str
    level: str  # A/B/C/D
