"""AI 创作助手模块 API 端点。"""

import uuid

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.dependencies import get_current_user_id, get_tenant_id_from_token
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse, PaginatedResponse
from ai_employee.schemas.content import (
    ComplianceCheckRequest,
    ComplianceCheckResponse,
    ComplianceIssue,
    ContentDraftCreate,
    ContentDraftDetailResponse,
    ContentDraftResponse,
    ContentDraftUpdate,
    ContentGenerationRequest,
    ContentGenerationResponse,
    ContentScheduleCreate,
    ContentScheduleResponse,
    ContentScheduleUpdate,
    ContentTemplateCreate,
    ContentTemplateResponse,
    ContentTemplateUpdate,
    PersonaCreate,
    PersonaResponse,
    PersonaUpdate,
)
from ai_employee.services.compliance_engine import ComplianceCheckEngine
from ai_employee.services.content_draft_service import ContentDraftService
from ai_employee.services.content_generation_service import ContentGenerationService
from ai_employee.services.content_schedule_service import ContentScheduleService
from ai_employee.services.content_template_service import ContentTemplateService
from ai_employee.services.persona_service import PersonaService

# Main router
router = APIRouter()

# Sub-routers for each resource to avoid route conflicts
templates_router = APIRouter(prefix="/templates")
personas_router = APIRouter(prefix="/personas")
drafts_router = APIRouter(prefix="/drafts")
schedules_router = APIRouter(prefix="/schedules")


def _get_tenant_id(tenant_id: uuid.UUID = Depends(get_tenant_id_from_token)) -> str:
    """将 tenant_id UUID 转换为字符串。"""
    return str(tenant_id)


# ==================== Content Template Endpoints ====================

@templates_router.post("", response_model=ApiResponse[ContentTemplateResponse], status_code=status.HTTP_201_CREATED)
async def create_template(
    data: ContentTemplateCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[ContentTemplateResponse]:
    """创建内容模板。"""
    service = ContentTemplateService(db)
    template = await service.create(tenant_id=tenant_id, **data.model_dump())
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="内容模板创建成功",
        data=ContentTemplateResponse.model_validate(template),
    )


@templates_router.get("", response_model=ApiResponse[PaginatedResponse[ContentTemplateResponse]])
async def list_templates(
    platform: str | None = None,
    content_type: str | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[PaginatedResponse[ContentTemplateResponse]]:
    """获取内容模板列表。"""
    service = ContentTemplateService(db)
    skip = (page - 1) * page_size
    templates, total = await service.get_list(
        tenant_id=tenant_id,
        platform=platform,
        content_type=content_type,
        skip=(page - 1) * page_size,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[ContentTemplateResponse.model_validate(t) for t in templates],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@templates_router.get("/{template_id}", response_model=ApiResponse[ContentTemplateResponse])
async def get_template(
    template_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ContentTemplateResponse]:
    """获取内容模板详情。"""
    service = ContentTemplateService(db)
    template = await service.get_by_id(template_id)
    if not template:
        raise HTTPException(status_code=404, detail="内容模板不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=ContentTemplateResponse.model_validate(template))


@templates_router.patch("/{template_id}", response_model=ApiResponse[ContentTemplateResponse])
async def update_template(
    template_id: uuid.UUID,
    data: ContentTemplateUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ContentTemplateResponse]:
    """更新内容模板。"""
    service = ContentTemplateService(db)
    template = await service.update(template_id, **data.model_dump(exclude_unset=True))
    if not template:
        raise HTTPException(status_code=404, detail="内容模板不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=ContentTemplateResponse.model_validate(template))


@templates_router.delete("/{template_id}", status_code=204)
async def delete_template(
    template_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除内容模板。"""
    service = ContentTemplateService(db)
    deleted = await service.delete(template_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="内容模板不存在")


# ==================== Persona Endpoints ====================

@personas_router.post("", response_model=ApiResponse[PersonaResponse], status_code=status.HTTP_201_CREATED)
async def create_persona(
    data: PersonaCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[PersonaResponse]:
    """创建人设。"""
    service = PersonaService(db)
    persona = await service.create(tenant_id=tenant_id, **data.model_dump())
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="人设创建成功",
        data=PersonaResponse.model_validate(persona),
    )


@personas_router.get("", response_model=ApiResponse[PaginatedResponse[PersonaResponse]])
async def list_personas(
    platform: str | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[PaginatedResponse[PersonaResponse]]:
    """获取人设列表。"""
    service = PersonaService(db)
    skip = (page - 1) * page_size
    personas, total = await service.get_list(
        tenant_id=tenant_id,
        platform=platform,
        skip=(page - 1) * page_size,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[PersonaResponse.model_validate(p) for p in personas],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@personas_router.get("/{persona_id}", response_model=ApiResponse[PersonaResponse])
async def get_persona(
    persona_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PersonaResponse]:
    """获取人设详情。"""
    service = PersonaService(db)
    persona = await service.get_by_id(persona_id)
    if not persona:
        raise HTTPException(status_code=404, detail="人设不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=PersonaResponse.model_validate(persona))


@personas_router.patch("/{persona_id}", response_model=ApiResponse[PersonaResponse])
async def update_persona(
    persona_id: uuid.UUID,
    data: PersonaUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PersonaResponse]:
    """更新人设。"""
    service = PersonaService(db)
    persona = await service.update(persona_id, **data.model_dump(exclude_unset=True))
    if not persona:
        raise HTTPException(status_code=404, detail="人设不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=PersonaResponse.model_validate(persona))


@personas_router.delete("/{persona_id}", status_code=204)
async def delete_persona(
    persona_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除人设。"""
    service = PersonaService(db)
    deleted = await service.delete(persona_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="人设不存在")


# ==================== Content Draft Endpoints ====================

@drafts_router.post("", response_model=ApiResponse[ContentDraftResponse], status_code=status.HTTP_201_CREATED)
async def create_draft(
    data: ContentDraftCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[ContentDraftResponse]:
    """创建内容草稿。"""
    service = ContentDraftService(db)
    draft_data = data.model_dump()
    if draft_data.get("template_id"):
        draft_data["template_id"] = str(draft_data["template_id"])
    if draft_data.get("persona_id"):
        draft_data["persona_id"] = str(draft_data["persona_id"])
    draft = await service.create(tenant_id=tenant_id, **draft_data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="内容草稿创建成功",
        data=ContentDraftResponse.model_validate(draft),
    )


@drafts_router.get("", response_model=ApiResponse[PaginatedResponse[ContentDraftResponse]])
async def list_drafts(
    platform: str | None = None,
    draft_status: str | None = Query(None, alias="status"),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[PaginatedResponse[ContentDraftResponse]]:
    """获取内容草稿列表。"""
    service = ContentDraftService(db)
    skip = (page - 1) * page_size
    drafts, total = await service.get_list(
        tenant_id=tenant_id,
        platform=platform,
        status=draft_status,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[ContentDraftResponse.model_validate(d) for d in drafts],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@drafts_router.get("/{draft_id}", response_model=ApiResponse[ContentDraftDetailResponse])
async def get_draft(
    draft_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ContentDraftDetailResponse]:
    """获取内容草稿详情。"""
    service = ContentDraftService(db)
    draft = await service.get_by_id_with_relations(draft_id)
    if not draft:
        raise HTTPException(status_code=404, detail="内容草稿不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=ContentDraftDetailResponse.model_validate(draft))


@drafts_router.patch("/{draft_id}", response_model=ApiResponse[ContentDraftResponse])
async def update_draft(
    draft_id: uuid.UUID,
    data: ContentDraftUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ContentDraftResponse]:
    """更新内容草稿。"""
    service = ContentDraftService(db)
    draft = await service.update(draft_id, **data.model_dump(exclude_unset=True))
    if not draft:
        raise HTTPException(status_code=404, detail="内容草稿不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=ContentDraftResponse.model_validate(draft))


@drafts_router.delete("/{draft_id}", status_code=204)
async def delete_draft(
    draft_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除内容草稿。"""
    service = ContentDraftService(db)
    deleted = await service.delete(draft_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="内容草稿不存在")


# ==================== Content Generation Endpoints ====================

@router.post("/generate", response_model=ApiResponse[ContentGenerationResponse], status_code=status.HTTP_201_CREATED)
async def generate_content(
    data: ContentGenerationRequest,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[ContentGenerationResponse]:
    """生成内容。"""
    service = ContentGenerationService(db)
    drafts = await service.generate_content(
        tenant_id=tenant_id,
        topic=data.topic,
        platform=data.platform,
        content_type=data.content_type,
        template_id=data.template_id,
        persona_id=data.persona_id,
        additional_context=data.additional_context,
        num_versions=data.num_versions,
    )
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="内容生成成功",
        data=ContentGenerationResponse(drafts=drafts),
    )


# ==================== Compliance Check Endpoints ====================

@router.post("/compliance/check", response_model=ApiResponse[ComplianceCheckResponse])
async def check_compliance(
    data: ComplianceCheckRequest,
) -> ApiResponse[ComplianceCheckResponse]:
    """检查内容合规性。"""
    engine = ComplianceCheckEngine()
    result = engine.check(data.content, data.platform)
    issues = [ComplianceIssue(**issue) for issue in result["issues"]]
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=ComplianceCheckResponse(
            is_compliant=result["is_compliant"],
            score=result["score"],
            issues=issues,
        ),
    )


# ==================== Content Schedule Endpoints ====================

@schedules_router.post("", response_model=ApiResponse[ContentScheduleResponse], status_code=status.HTTP_201_CREATED)
async def create_schedule(
    data: ContentScheduleCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[ContentScheduleResponse]:
    """创建内容排期。"""
    service = ContentScheduleService(db)
    schedule = await service.create(
        tenant_id=tenant_id,
        draft_id=str(data.draft_id),
        platform=data.platform,
        scheduled_at=data.scheduled_at,
    )
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="内容排期创建成功",
        data=ContentScheduleResponse.model_validate(schedule),
    )


@schedules_router.get("", response_model=ApiResponse[PaginatedResponse[ContentScheduleResponse]])
async def list_schedules(
    platform: str | None = None,
    schedule_status: str | None = Query(None, alias="status"),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[PaginatedResponse[ContentScheduleResponse]]:
    """获取内容排期列表。"""
    service = ContentScheduleService(db)
    skip = (page - 1) * page_size
    schedules, total = await service.get_list(
        tenant_id=tenant_id,
        platform=platform,
        status=schedule_status,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[ContentScheduleResponse.model_validate(s) for s in schedules],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@schedules_router.get("/calendar", response_model=ApiResponse[dict])
async def get_calendar_view(
    start_date: str,
    end_date: str,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[dict]:
    """获取日历视图排期。"""
    service = ContentScheduleService(db)
    schedules = await service.get_calendar_view(
        tenant_id=tenant_id,
        start_date=start_date,
        end_date=end_date,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data={"schedules": schedules, "count": len(schedules)},
    )


@schedules_router.get("/{schedule_id}", response_model=ApiResponse[ContentScheduleResponse])
async def get_schedule(
    schedule_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ContentScheduleResponse]:
    """获取内容排期详情。"""
    service = ContentScheduleService(db)
    schedule = await service.get_by_id(schedule_id)
    if not schedule:
        raise HTTPException(status_code=404, detail="内容排期不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=ContentScheduleResponse.model_validate(schedule))


@schedules_router.patch("/{schedule_id}", response_model=ApiResponse[ContentScheduleResponse])
async def update_schedule(
    schedule_id: uuid.UUID,
    data: ContentScheduleUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ContentScheduleResponse]:
    """更新内容排期。"""
    service = ContentScheduleService(db)
    schedule = await service.update(schedule_id, **data.model_dump(exclude_unset=True))
    if not schedule:
        raise HTTPException(status_code=404, detail="内容排期不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=ContentScheduleResponse.model_validate(schedule))


@schedules_router.delete("/{schedule_id}", status_code=204)
async def delete_schedule(
    schedule_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除内容排期。"""
    service = ContentScheduleService(db)
    deleted = await service.delete(schedule_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="内容排期不存在")


# Register sub-routers
router.include_router(templates_router)
router.include_router(personas_router)
router.include_router(drafts_router)
router.include_router(schedules_router)
