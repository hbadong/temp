"""AI 追爆助手模块端点。

包含热点数据、账号画像、追热任务、爆款视频分析等 API 端点。
"""

import uuid

from fastapi import APIRouter, Depends, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.core.permissions import require_permission
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse, PaginatedResponse
from ai_employee.schemas.trend import (
    AccountProfileCreate,
    AccountProfileResponse,
    AccountProfileUpdate,
    TrendCreate,
    TrendResponse,
    TrendTaskCreate,
    TrendTaskDetailResponse,
    TrendTaskResponse,
    TrendTaskUpdate,
    TrendUpdate,
    ViralVideoAnalysisCreate,
    ViralVideoAnalysisResponse,
    ViralVideoAnalysisUpdate,
)
from ai_employee.services.account_profile_service import AccountProfileService
from ai_employee.services.trend_service import TrendService
from ai_employee.services.trend_task_service import TrendTaskService
from ai_employee.services.viral_video_service import ViralVideoAnalysisService

# Main router
router = APIRouter(tags=["AI 追爆助手"])

# Sub-routers for each resource to avoid route conflicts
trends_router = APIRouter(prefix="/trends")
profiles_router = APIRouter(prefix="/trends/profiles")
tasks_router = APIRouter(prefix="/trends/tasks")
viral_router = APIRouter(prefix="/trends/viral-videos")


# ==================== 热点数据端点 ====================

@trends_router.post("", response_model=ApiResponse[TrendResponse], status_code=status.HTTP_201_CREATED)
async def create_trend(
    data: TrendCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[TrendResponse]:
    """创建热点数据。"""
    service = TrendService(db)
    trend = await service.create_trend(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="热点数据创建成功",
        data=TrendResponse.model_validate(trend),
    )


@trends_router.get("", response_model=ApiResponse[PaginatedResponse[TrendResponse]])
async def list_trends(
    platform: str | None = Query(None),
    category: str | None = Query(None),
    min_hot_value: int | None = Query(None),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[TrendResponse]]:
    """获取热点数据列表。"""
    service = TrendService(db)
    skip = (page - 1) * page_size
    trends, total = await service.list_trends(
        platform=platform,
        category=category,
        min_hot_value=min_hot_value,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[TrendResponse.model_validate(t) for t in trends],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@trends_router.get("/hot", response_model=ApiResponse[list[TrendResponse]])
async def get_hot_trends(
    platform: str | None = Query(None),
    limit: int = Query(50, ge=1, le=200),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[list[TrendResponse]]:
    """获取热门热点数据。"""
    service = TrendService(db)
    trends = await service.get_hot_trends(platform=platform, limit=limit)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[TrendResponse.model_validate(t) for t in trends],
    )


@trends_router.get("/{trend_id}", response_model=ApiResponse[TrendResponse])
async def get_trend(
    trend_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[TrendResponse]:
    """获取热点数据详情。"""
    service = TrendService(db)
    trend = await service.get_by_id(trend_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=TrendResponse.model_validate(trend),
    )


@trends_router.patch("/{trend_id}", response_model=ApiResponse[TrendResponse])
async def update_trend(
    trend_id: uuid.UUID,
    data: TrendUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[TrendResponse]:
    """更新热点数据。"""
    service = TrendService(db)
    trend = await service.update_trend(trend_id, data)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="热点数据更新成功",
        data=TrendResponse.model_validate(trend),
    )


@trends_router.delete("/{trend_id}", response_model=ApiResponse[None])
async def delete_trend(
    trend_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """删除热点数据。"""
    service = TrendService(db)
    await service.delete_trend(trend_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="热点数据删除成功",
    )


# ==================== 账号画像端点 ====================

@profiles_router.post("", response_model=ApiResponse[AccountProfileResponse], status_code=status.HTTP_201_CREATED)
async def create_profile(
    data: AccountProfileCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[AccountProfileResponse]:
    """创建账号画像。"""
    service = AccountProfileService(db)
    profile = await service.create_profile(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="账号画像创建成功",
        data=AccountProfileResponse.model_validate(profile),
    )


@profiles_router.get("", response_model=ApiResponse[PaginatedResponse[AccountProfileResponse]])
async def list_profiles(
    platform: str | None = Query(None),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[AccountProfileResponse]]:
    """获取账号画像列表。"""
    service = AccountProfileService(db)
    skip = (page - 1) * page_size
    profiles, total = await service.list_profiles(
        platform=platform,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[AccountProfileResponse.model_validate(p) for p in profiles],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@profiles_router.get("/{profile_id}", response_model=ApiResponse[AccountProfileResponse])
async def get_profile(
    profile_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[AccountProfileResponse]:
    """获取账号画像详情。"""
    service = AccountProfileService(db)
    profile = await service.get_by_id(profile_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=AccountProfileResponse.model_validate(profile),
    )


@profiles_router.patch("/{profile_id}", response_model=ApiResponse[AccountProfileResponse])
async def update_profile(
    profile_id: uuid.UUID,
    data: AccountProfileUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[AccountProfileResponse]:
    """更新账号画像。"""
    service = AccountProfileService(db)
    profile = await service.update_profile(profile_id, data)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="账号画像更新成功",
        data=AccountProfileResponse.model_validate(profile),
    )


@profiles_router.delete("/{profile_id}", response_model=ApiResponse[None])
async def delete_profile(
    profile_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """删除账号画像。"""
    service = AccountProfileService(db)
    await service.delete_profile(profile_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="账号画像删除成功",
    )


# ==================== 追热任务端点 ====================

@tasks_router.post("", response_model=ApiResponse[TrendTaskResponse], status_code=status.HTTP_201_CREATED)
async def create_task(
    data: TrendTaskCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[TrendTaskResponse]:
    """创建追热任务。"""
    service = TrendTaskService(db)
    task = await service.create_task(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="追热任务创建成功",
        data=TrendTaskResponse.model_validate(task),
    )


@tasks_router.get("", response_model=ApiResponse[PaginatedResponse[TrendTaskResponse]])
async def list_tasks(
    task_status: str | None = Query(None),
    trend_id: uuid.UUID | None = Query(None),
    account_profile_id: uuid.UUID | None = Query(None),
    min_score: float | None = Query(None),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[TrendTaskResponse]]:
    """获取追热任务列表。"""
    service = TrendTaskService(db)
    skip = (page - 1) * page_size
    tasks, total = await service.list_tasks(
        status=task_status,
        trend_id=trend_id,
        account_profile_id=account_profile_id,
        min_score=min_score,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[TrendTaskResponse.model_validate(t) for t in tasks],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@tasks_router.get("/pending", response_model=ApiResponse[list[TrendTaskResponse]])
async def get_pending_tasks(
    limit: int = Query(50, ge=1, le=200),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[list[TrendTaskResponse]]:
    """获取待处理的追热任务。"""
    service = TrendTaskService(db)
    tasks = await service.get_pending_tasks(limit=limit)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[TrendTaskResponse.model_validate(t) for t in tasks],
    )


@tasks_router.get("/{task_id}", response_model=ApiResponse[TrendTaskDetailResponse])
async def get_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[TrendTaskDetailResponse]:
    """获取追热任务详情。"""
    service = TrendTaskService(db)
    task = await service.get_by_id(task_id)
    detail = TrendTaskDetailResponse.model_validate(task)
    detail.trend = TrendResponse.model_validate(task.trend) if task.trend else None
    detail.account_profile = AccountProfileResponse.model_validate(task.account_profile) if task.account_profile else None
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=detail,
    )


@tasks_router.patch("/{task_id}", response_model=ApiResponse[TrendTaskResponse])
async def update_task(
    task_id: uuid.UUID,
    data: TrendTaskUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[TrendTaskResponse]:
    """更新追热任务。"""
    service = TrendTaskService(db)
    task = await service.update_task(task_id, data)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="追热任务更新成功",
        data=TrendTaskResponse.model_validate(task),
    )


@tasks_router.delete("/{task_id}", response_model=ApiResponse[None])
async def delete_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """删除追热任务。"""
    service = TrendTaskService(db)
    await service.delete_task(task_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="追热任务删除成功",
    )


# ==================== 爆款视频分析端点 ====================

@viral_router.post("", response_model=ApiResponse[ViralVideoAnalysisResponse], status_code=status.HTTP_201_CREATED)
async def create_viral_analysis(
    data: ViralVideoAnalysisCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ViralVideoAnalysisResponse]:
    """创建爆款视频分析。"""
    service = ViralVideoAnalysisService(db)
    analysis = await service.create_analysis(data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="爆款视频分析创建成功",
        data=ViralVideoAnalysisResponse.model_validate(analysis),
    )


@viral_router.get("", response_model=ApiResponse[PaginatedResponse[ViralVideoAnalysisResponse]])
async def list_viral_analyses(
    platform: str | None = Query(None),
    min_viral_score: float | None = Query(None),
    min_views: int | None = Query(None),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PaginatedResponse[ViralVideoAnalysisResponse]]:
    """获取爆款视频分析列表。"""
    service = ViralVideoAnalysisService(db)
    skip = (page - 1) * page_size
    analyses, total = await service.list_analyses(
        platform=platform,
        min_viral_score=min_viral_score,
        min_views=min_views,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PaginatedResponse(
            items=[ViralVideoAnalysisResponse.model_validate(a) for a in analyses],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@viral_router.get("/top", response_model=ApiResponse[list[ViralVideoAnalysisResponse]])
async def get_top_viral_videos(
    platform: str | None = Query(None),
    limit: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[list[ViralVideoAnalysisResponse]]:
    """获取最热门的爆款视频分析。"""
    service = ViralVideoAnalysisService(db)
    analyses = await service.get_top_viral_videos(platform=platform, limit=limit)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[ViralVideoAnalysisResponse.model_validate(a) for a in analyses],
    )


@viral_router.get("/{analysis_id}", response_model=ApiResponse[ViralVideoAnalysisResponse])
async def get_viral_analysis(
    analysis_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ViralVideoAnalysisResponse]:
    """获取爆款视频分析详情。"""
    service = ViralVideoAnalysisService(db)
    analysis = await service.get_by_id(analysis_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=ViralVideoAnalysisResponse.model_validate(analysis),
    )


@viral_router.patch("/{analysis_id}", response_model=ApiResponse[ViralVideoAnalysisResponse])
async def update_viral_analysis(
    analysis_id: uuid.UUID,
    data: ViralVideoAnalysisUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ViralVideoAnalysisResponse]:
    """更新爆款视频分析。"""
    service = ViralVideoAnalysisService(db)
    analysis = await service.update_analysis(analysis_id, data)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="爆款视频分析更新成功",
        data=ViralVideoAnalysisResponse.model_validate(analysis),
    )


@viral_router.delete("/{analysis_id}", response_model=ApiResponse[None])
async def delete_viral_analysis(
    analysis_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[None]:
    """删除爆款视频分析。"""
    service = ViralVideoAnalysisService(db)
    await service.delete_analysis(analysis_id)
    return ApiResponse(
        code=status.HTTP_200_OK,
        message="爆款视频分析删除成功",
    )


# Register sub-routers (order matters: specific routes before parameterized ones)
router.include_router(profiles_router)
router.include_router(tasks_router)
router.include_router(viral_router)
router.include_router(trends_router)
