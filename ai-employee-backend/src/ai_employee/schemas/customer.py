"""AI 拓客助手模块 Schema。"""

import uuid
from datetime import datetime

from pydantic import BaseModel, Field


# ==================== Monitor Task Schemas ====================

class MonitorTaskCreate(BaseModel):
    """创建监控任务 Schema。"""
    name: str = Field(..., max_length=255)
    description: str | None = None
    platform: str = Field(..., max_length=50)
    target_type: str = Field(..., max_length=50)
    target_ids: str | None = None
    keywords: str | None = None
    schedule_config: str | None = None


class MonitorTaskUpdate(BaseModel):
    """更新监控任务 Schema。"""
    name: str | None = Field(None, max_length=255)
    description: str | None = None
    status: str | None = Field(None, max_length=50)
    keywords: str | None = None
    schedule_config: str | None = None


class MonitorTaskResponse(BaseModel):
    """监控任务响应 Schema。"""
    id: uuid.UUID
    name: str
    description: str | None = None
    platform: str
    target_type: str
    target_ids: str | None = None
    keywords: str | None = None
    schedule_config: str | None = None
    status: str
    last_run_at: str | None = None
    next_run_at: str | None = None
    run_count: int
    comment_count: int
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ==================== Comment Schemas ====================

class CommentResponse(BaseModel):
    """评论响应 Schema。"""
    id: uuid.UUID
    source_id: str
    platform: str
    target_type: str
    target_id: str
    author_id: str | None = None
    author_name: str | None = None
    content: str
    parent_id: str | None = None
    like_count: int
    reply_count: int
    comment_time: str | None = None
    sentiment: str | None = None
    intent: str | None = None
    intent_score: float | None = None
    is_replied: bool
    reply_id: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class CommentListResponse(BaseModel):
    """评论列表响应 Schema。"""
    comments: list[CommentResponse]
    total: int
    page: int
    page_size: int
    has_next: bool
    has_prev: bool


# ==================== Reply Template Schemas ====================

class ReplyTemplateCreate(BaseModel):
    """创建话术模板 Schema。"""
    name: str = Field(..., max_length=255)
    description: str | None = None
    platform: str = Field(..., max_length=50)
    intent_type: str | None = Field(None, max_length=50)
    template_body: str
    variables: str | None = None
    match_keywords: str | None = None


class ReplyTemplateUpdate(BaseModel):
    """更新话术模板 Schema。"""
    name: str | None = Field(None, max_length=255)
    description: str | None = None
    template_body: str | None = None
    variables: str | None = None
    match_keywords: str | None = None


class ReplyTemplateResponse(BaseModel):
    """话术模板响应 Schema。"""
    id: uuid.UUID
    name: str
    description: str | None = None
    platform: str
    intent_type: str | None = None
    template_body: str
    variables: str | None = None
    is_system: bool
    usage_count: int
    match_keywords: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ==================== Private Message Schemas ====================

class PrivateMessageResponse(BaseModel):
    """私信响应 Schema。"""
    id: uuid.UUID
    conversation_id: str
    platform: str
    sender_id: str
    sender_name: str | None = None
    receiver_id: str
    content: str
    direction: str
    is_read: bool
    message_time: str | None = None
    sentiment: str | None = None
    intent: str | None = None
    intent_score: float | None = None
    auto_reply_id: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class PrivateMessageListResponse(BaseModel):
    """私信列表响应 Schema。"""
    messages: list[PrivateMessageResponse]
    total: int
    page: int
    page_size: int
    has_next: bool
    has_prev: bool


class ConversationResponse(BaseModel):
    """会话响应 Schema。"""
    conversation_id: str
    platform: str
    sender_id: str
    sender_name: str | None = None
    last_message: str
    last_message_time: str | None = None
    unread_count: int
    message_count: int


# ==================== Auto Reply Rule Schemas ====================

class AutoReplyRuleCreate(BaseModel):
    """创建自动回复规则 Schema。"""
    name: str = Field(..., max_length=255)
    description: str | None = None
    platform: str = Field(..., max_length=50)
    trigger_type: str = Field(..., max_length=50)
    trigger_config: str | None = None
    response_template_id: uuid.UUID | None = None
    response_content: str | None = None
    delay_min: int = 0
    delay_max: int = 0
    priority: int = 0


class AutoReplyRuleUpdate(BaseModel):
    """更新自动回复规则 Schema。"""
    name: str | None = Field(None, max_length=255)
    description: str | None = None
    trigger_config: str | None = None
    response_template_id: uuid.UUID | None = None
    response_content: str | None = None
    delay_min: int | None = None
    delay_max: int | None = None
    is_active: bool | None = None
    priority: int | None = None


class AutoReplyRuleResponse(BaseModel):
    """自动回复规则响应 Schema。"""
    id: uuid.UUID
    name: str
    description: str | None = None
    platform: str
    trigger_type: str
    trigger_config: str | None = None
    response_template_id: str | None = None
    response_content: str | None = None
    delay_min: int
    delay_max: int
    is_active: bool
    priority: int
    match_count: int
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ==================== Intent Analysis Schemas ====================

class IntentAnalysisRequest(BaseModel):
    """意图分析请求 Schema。"""
    content: str
    platform: str = Field(..., max_length=50)


class IntentAnalysisResponse(BaseModel):
    """意图分析响应 Schema。"""
    intent: str
    intent_name: str
    confidence: float
    sentiment: str
    sentiment_name: str
    keywords: list[str]
