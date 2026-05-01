"""地图拓客 API 端点。"""

import json
import uuid

from fastapi import APIRouter, Depends, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.dependencies import get_tenant_id_from_token
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse
from ai_employee.schemas.map import (
    LeadCustomerListResponse,
    LeadCustomerResponse,
    LeadScoreRequest,
    LeadScoreResponse,
    MapSearchTaskCreate,
    MapSearchTaskResponse,
    MapSearchTaskUpdate,
    POIListResponse,
    POIResponse,
    StoreVisitNoteCreate,
    StoreVisitNoteListResponse,
    StoreVisitNoteResponse,
    StoreVisitNoteUpdate,
)
from ai_employee.services.map.lead_customer_service import LeadCustomerService
from ai_employee.services.map.lead_scoring import LeadScoringEngine
from ai_employee.services.map.poi_service import POIService
from ai_employee.services.map.search_task_service import MapSearchTaskService
from ai_employee.services.map.store_visit_note_service import StoreVisitNoteService

router = APIRouter()


def _get_tenant_id(tenant_id: uuid.UUID = Depends(get_tenant_id_from_token)) -> str:
    """将 tenant_id UUID 转换为字符串。"""
    return str(tenant_id)


# ==================== Map Search Tasks ====================

@router.post("/map/tasks", response_model=ApiResponse[MapSearchTaskResponse], status_code=status.HTTP_201_CREATED)
async def create_search_task(
    data: MapSearchTaskCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[MapSearchTaskResponse]:
    """创建地图搜索任务。"""
    service = MapSearchTaskService(db)
    task = await service.create_task(
        tenant_id=tenant_id,
        name=data.name,
        description=data.description,
        platform=data.platform,
        keywords=data.keywords,
        city=data.city,
        radius=data.radius,
        center_lat=data.center_lat,
        center_lng=data.center_lng,
    )
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="搜索任务创建成功",
        data=MapSearchTaskResponse.model_validate(task),
    )


@router.get("/map/tasks", response_model=ApiResponse[list[MapSearchTaskResponse]])
async def list_search_tasks(
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
    platform: str | None = None,
    task_status: str | None = Query(None, alias="status"),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
) -> ApiResponse[list[MapSearchTaskResponse]]:
    """获取地图搜索任务列表。"""
    service = MapSearchTaskService(db)
    skip = (page - 1) * page_size
    tasks, total = await service.list_tasks(
        tenant_id,
        platform=platform,
        status=task_status,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        data=[MapSearchTaskResponse.model_validate(t) for t in tasks],
        message="获取成功",
    )


@router.get("/map/tasks/{task_id}", response_model=ApiResponse[MapSearchTaskResponse])
async def get_search_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[MapSearchTaskResponse]:
    """获取地图搜索任务详情。"""
    service = MapSearchTaskService(db)
    task = await service.get_task_by_id(task_id)
    if not task:
        return ApiResponse(data=None, message="搜索任务不存在", code=status.HTTP_404_NOT_FOUND)
    return ApiResponse(data=MapSearchTaskResponse.model_validate(task))


@router.patch("/map/tasks/{task_id}", response_model=ApiResponse[MapSearchTaskResponse])
async def update_search_task(
    task_id: uuid.UUID,
    data: MapSearchTaskUpdate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[MapSearchTaskResponse]:
    """更新地图搜索任务。"""
    service = MapSearchTaskService(db)
    task = await service.update_task(task_id, **data.model_dump(exclude_unset=True))
    if not task:
        return ApiResponse(data=None, message="搜索任务不存在", code=status.HTTP_404_NOT_FOUND)
    return ApiResponse(data=MapSearchTaskResponse.model_validate(task), message="搜索任务更新成功")


@router.delete("/map/tasks/{task_id}", response_model=ApiResponse)
async def delete_search_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse:
    """删除地图搜索任务。"""
    service = MapSearchTaskService(db)
    success = await service.delete_task(task_id)
    if not success:
        return ApiResponse(data=None, message="搜索任务不存在", code=status.HTTP_404_NOT_FOUND)
    return ApiResponse(data=None, message="搜索任务删除成功")


@router.post("/map/tasks/{task_id}/execute", response_model=ApiResponse[POIListResponse])
async def execute_search_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[POIListResponse]:
    """执行地图搜索任务（模拟）。"""
    task_service = MapSearchTaskService(db)
    task = await task_service.get_task_by_id(task_id)
    if not task:
        return ApiResponse(data=None, message="搜索任务不存在", code=status.HTTP_404_NOT_FOUND)

    # 更新状态
    await task_service.update_task(task_id, status="running")

    # 执行搜索（模拟）
    poi_service = POIService(db)
    keywords = json.loads(task.keywords) if task.keywords else ["餐饮"]
    pois = await poi_service.simulate_search(
        tenant_id,
        task.platform,
        keywords,
        task.city,
        task_id,
    )

    # 更新任务状态
    await task_service.update_task(
        task_id,
        status="completed",
        total_results=len(pois),
        processed_count=len(pois),
    )

    return ApiResponse(data=POIListResponse(pois=[POIResponse.model_validate(p) for p in pois], total=len(pois), page=1, page_size=100, has_next=False, has_prev=False))


# ==================== POIs ====================

@router.get("/map/pois", response_model=ApiResponse[POIListResponse])
async def list_pois(
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
    platform: str | None = None,
    category: str | None = None,
    is_processed: bool | None = None,
    min_score: float | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
) -> ApiResponse[POIListResponse]:
    """获取 POI 列表。"""
    service = POIService(db)
    skip = (page - 1) * page_size
    pois, total = await service.list_pois(
        tenant_id,
        platform=platform,
        category=category,
        is_processed=is_processed,
        min_score=min_score,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        data=POIListResponse(
            pois=[POIResponse.model_validate(p) for p in pois],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(page * page_size) < total,
            has_prev=page > 1,
        )
    )


@router.get("/map/pois/{poi_id}", response_model=ApiResponse[POIResponse])
async def get_poi(
    poi_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[POIResponse]:
    """获取 POI 详情。"""
    service = POIService(db)
    poi = await service.get_poi_by_id(poi_id)
    if not poi:
        return ApiResponse(data=None, message="POI 不存在", code=status.HTTP_404_NOT_FOUND)
    return ApiResponse(data=POIResponse.model_validate(poi))


# ==================== Lead Scoring ====================

@router.post("/map/leads/score", response_model=ApiResponse[LeadScoreResponse])
async def score_lead(
    data: LeadScoreRequest,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[LeadScoreResponse]:
    """潜在客户评分。"""
    engine = LeadScoringEngine()
    result = engine.score(
        name=data.name,
        category=data.category,
        address=data.address,
        phone=data.phone,
    )
    return ApiResponse(
        data=LeadScoreResponse(
            score=result["score"],
            factors=result["factors"],
            reason=result["reason"],
            level=result["level"],
        )
    )


# ==================== Lead Customers ====================

@router.post("/map/leads/{poi_id}", response_model=ApiResponse[LeadCustomerResponse], status_code=status.HTTP_201_CREATED)
async def create_lead_from_poi(
    poi_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[LeadCustomerResponse]:
    """从 POI 创建潜在客户。"""
    service = LeadCustomerService(db)
    try:
        lead = await service.create_lead(tenant_id, poi_id)
        return ApiResponse(
            code=status.HTTP_201_CREATED,
            data=LeadCustomerResponse.model_validate(lead),
            message="潜在客户创建成功",
        )
    except ValueError as e:
        return ApiResponse(data=None, message=str(e), code=status.HTTP_404_NOT_FOUND)


@router.get("/map/leads", response_model=ApiResponse[LeadCustomerListResponse])
async def list_leads(
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
    status: str | None = None,
    min_score: float | None = None,
    max_score: float | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
) -> ApiResponse[LeadCustomerListResponse]:
    """获取潜在客户列表。"""
    service = LeadCustomerService(db)
    skip = (page - 1) * page_size
    leads, total = await service.list_leads(
        tenant_id,
        status=status,
        min_score=min_score,
        max_score=max_score,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        data=LeadCustomerListResponse(
            leads=[LeadCustomerResponse.model_validate(l) for l in leads],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(page * page_size) < total,
            has_prev=page > 1,
        )
    )


@router.get("/map/leads/{lead_id}", response_model=ApiResponse[LeadCustomerResponse])
async def get_lead(
    lead_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[LeadCustomerResponse]:
    """获取潜在客户详情。"""
    service = LeadCustomerService(db)
    lead = await service.get_lead_by_id(lead_id)
    if not lead:
        return ApiResponse(data=None, message="潜在客户不存在", code=status.HTTP_404_NOT_FOUND)
    return ApiResponse(data=LeadCustomerResponse.model_validate(lead))


@router.patch("/map/leads/{lead_id}", response_model=ApiResponse[LeadCustomerResponse])
async def update_lead(
    lead_id: uuid.UUID,
    status_val: str | None = Query(None, alias="status"),
    notes: str | None = None,
    assigned_to: str | None = None,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[LeadCustomerResponse]:
    """更新潜在客户。"""
    service = LeadCustomerService(db)
    lead = await service.update_lead(
        lead_id,
        status=status_val,
        notes=notes,
        assigned_to=assigned_to,
    )
    if not lead:
        return ApiResponse(data=None, message="潜在客户不存在", code=status.HTTP_404_NOT_FOUND)
    return ApiResponse(data=LeadCustomerResponse.model_validate(lead), message="潜在客户更新成功")


# ==================== Store Visit Notes ====================

@router.post("/map/notes", response_model=ApiResponse[StoreVisitNoteResponse], status_code=status.HTTP_201_CREATED)
async def create_store_visit_note(
    data: StoreVisitNoteCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[StoreVisitNoteResponse]:
    """创建探店笔记。"""
    service = StoreVisitNoteService(db)
    note = await service.create_note(
        tenant_id=tenant_id,
        poi_id=str(data.poi_id),
        title=data.title,
        content=data.content,
        platform=data.platform,
        style=data.style,
        images=data.images,
        tags=data.tags,
    )
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        data=StoreVisitNoteResponse.model_validate(note),
        message="探店笔记创建成功",
    )


@router.post("/map/notes/generate/{poi_id}", response_model=ApiResponse[StoreVisitNoteResponse], status_code=status.HTTP_201_CREATED)
async def generate_store_visit_note(
    poi_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
    platform: str = Query(..., description="发布平台"),
    style: str = Query("professional", description="风格"),
) -> ApiResponse[StoreVisitNoteResponse]:
    """根据 POI 生成探店笔记。"""
    service = StoreVisitNoteService(db)
    try:
        note = await service.generate_note(tenant_id, poi_id, platform, style)
        return ApiResponse(
            code=status.HTTP_201_CREATED,
            data=StoreVisitNoteResponse.model_validate(note),
            message="探店笔记生成成功",
        )
    except ValueError as e:
        return ApiResponse(data=None, message=str(e), code=status.HTTP_404_NOT_FOUND)


@router.get("/map/notes", response_model=ApiResponse[StoreVisitNoteListResponse])
async def list_store_visit_notes(
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
    platform: str | None = None,
    status: str | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
) -> ApiResponse[StoreVisitNoteListResponse]:
    """获取探店笔记列表。"""
    service = StoreVisitNoteService(db)
    skip = (page - 1) * page_size
    notes, total = await service.list_notes(
        tenant_id,
        platform=platform,
        status=status,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        data=StoreVisitNoteListResponse(
            notes=[StoreVisitNoteResponse.model_validate(n) for n in notes],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(page * page_size) < total,
            has_prev=page > 1,
        )
    )


@router.get("/map/notes/{note_id}", response_model=ApiResponse[StoreVisitNoteResponse])
async def get_store_visit_note(
    note_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[StoreVisitNoteResponse]:
    """获取探店笔记详情。"""
    service = StoreVisitNoteService(db)
    note = await service.get_note_by_id(note_id)
    if not note:
        return ApiResponse(data=None, message="探店笔记不存在", code=status.HTTP_404_NOT_FOUND)
    return ApiResponse(data=StoreVisitNoteResponse.model_validate(note))


@router.patch("/map/notes/{note_id}", response_model=ApiResponse[StoreVisitNoteResponse])
async def update_store_visit_note(
    note_id: uuid.UUID,
    data: StoreVisitNoteUpdate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[StoreVisitNoteResponse]:
    """更新探店笔记。"""
    service = StoreVisitNoteService(db)
    note = await service.update_note(note_id, **data.model_dump(exclude_unset=True))
    if not note:
        return ApiResponse(data=None, message="探店笔记不存在", code=status.HTTP_404_NOT_FOUND)
    return ApiResponse(data=StoreVisitNoteResponse.model_validate(note), message="探店笔记更新成功")


@router.delete("/map/notes/{note_id}", response_model=ApiResponse)
async def delete_store_visit_note(
    note_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse:
    """删除探店笔记。"""
    service = StoreVisitNoteService(db)
    success = await service.delete_note(note_id)
    if not success:
        return ApiResponse(data=None, message="探店笔记不存在", code=status.HTTP_404_NOT_FOUND)
    return ApiResponse(data=None, message="探店笔记删除成功")
