"""AI 创作助手模块 Schema。

包含内容模板、内容草稿、人设、内容排期等 Schema。
"""

import uuid
from datetime import datetime

from pydantic import BaseModel, Field


# ==================== Content Template Schemas ====================

class ContentTemplateBase(BaseModel):
    """内容模板基础 Schema。"""
    name: str = Field(..., max_length=255)
    description: str | None = None
    platform: str = Field(..., max_length=50)
    content_type: str = Field(..., max_length=50)
    template_body: str
    variables: str | None = None
    is_system: bool = False


class ContentTemplateCreate(ContentTemplateBase):
    """创建内容模板 Schema。"""
    pass


class ContentTemplateUpdate(BaseModel):
    """更新内容模板 Schema。"""
    name: str | None = Field(None, max_length=255)
    description: str | None = None
    template_body: str | None = None
    variables: str | None = None


class ContentTemplateResponse(ContentTemplateBase):
    """内容模板响应 Schema。"""
    id: uuid.UUID
    usage_count: int
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ==================== Persona Schemas ====================

class PersonaBase(BaseModel):
    """人设基础 Schema。"""
    name: str = Field(..., max_length=255)
    description: str | None = None
    platform: str = Field(..., max_length=50)
    style_config: str | None = None
    tone_config: str | None = None
    prompt_template: str | None = None
    sample_contents: str | None = None
    forbidden_words: str | None = None
    preferred_topics: str | None = Field(None, max_length=1000)


class PersonaCreate(PersonaBase):
    """创建人设 Schema。"""
    pass


class PersonaUpdate(BaseModel):
    """更新人设 Schema。"""
    name: str | None = Field(None, max_length=255)
    description: str | None = None
    style_config: str | None = None
    tone_config: str | None = None
    prompt_template: str | None = None
    sample_contents: str | None = None
    forbidden_words: str | None = None
    preferred_topics: str | None = Field(None, max_length=1000)


class PersonaResponse(PersonaBase):
    """人设响应 Schema。"""
    id: uuid.UUID
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ==================== Content Draft Schemas ====================

class ContentDraftCreate(BaseModel):
    """创建内容草稿 Schema。"""
    title: str = Field(..., max_length=500)
    content: str
    platform: str = Field(..., max_length=50)
    content_type: str = Field(..., max_length=50)
    template_id: uuid.UUID | None = None
    persona_id: uuid.UUID | None = None
    source_trend_id: str | None = None
    tags: str | None = Field(None, max_length=500)
    cover_image_url: str | None = Field(None, max_length=1000)


class ContentDraftUpdate(BaseModel):
    """更新内容草稿 Schema。"""
    title: str | None = Field(None, max_length=500)
    content: str | None = None
    status: str | None = Field(None, max_length=50)
    compliance_score: float | None = None
    compliance_issues: str | None = None
    tags: str | None = Field(None, max_length=500)
    cover_image_url: str | None = Field(None, max_length=1000)


class ContentDraftResponse(BaseModel):
    """内容草稿响应 Schema。"""
    id: uuid.UUID
    title: str
    content: str
    platform: str
    content_type: str
    template_id: uuid.UUID | None = None
    persona_id: uuid.UUID | None = None
    version: int
    status: str
    compliance_score: float | None = None
    compliance_issues: str | None = None
    source_trend_id: str | None = None
    tags: str | None = None
    cover_image_url: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class ContentDraftDetailResponse(ContentDraftResponse):
    """内容草稿详情响应（包含关联数据）。"""
    template: ContentTemplateResponse | None = None
    persona: PersonaResponse | None = None


# ==================== Content Generation Schemas ====================

class ContentGenerationRequest(BaseModel):
    """内容生成请求 Schema。"""
    topic: str = Field(..., max_length=500)
    platform: str = Field(..., max_length=50)
    content_type: str = Field(..., max_length=50)
    template_id: uuid.UUID | None = None
    persona_id: uuid.UUID | None = None
    source_trend_id: uuid.UUID | None = None
    additional_context: str | None = None
    num_versions: int = Field(default=1, ge=1, le=5)


class ContentGenerationResponse(BaseModel):
    """内容生成响应 Schema。"""
    drafts: list[ContentDraftResponse]
    message: str = "内容生成成功"


# ==================== Content Schedule Schemas ====================

class ContentScheduleCreate(BaseModel):
    """创建内容排期 Schema。"""
    draft_id: uuid.UUID
    platform: str = Field(..., max_length=50)
    scheduled_at: str


class ContentScheduleUpdate(BaseModel):
    """更新内容排期 Schema。"""
    scheduled_at: str | None = None
    status: str | None = Field(None, max_length=50)


class ContentScheduleResponse(BaseModel):
    """内容排期响应 Schema。"""
    id: uuid.UUID
    draft_id: uuid.UUID
    platform: str
    scheduled_at: str
    status: str
    publish_result: str | None = None
    error_message: str | None = None
    retry_count: int
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ==================== Compliance Check Schemas ====================

class ComplianceCheckRequest(BaseModel):
    """合规检查请求 Schema。"""
    content: str
    platform: str = Field(..., max_length=50)


class ComplianceIssue(BaseModel):
    """合规问题详情。"""
    type: str
    severity: str
    description: str
    position: int | None = None
    suggestion: str | None = None


class ComplianceCheckResponse(BaseModel):
    """合规检查响应 Schema。"""
    is_compliant: bool
    score: float
    issues: list[ComplianceIssue]
    message: str = "合规检查完成"
