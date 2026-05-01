"""内容发布与媒体服务模块 API 端点。"""

import uuid

from fastapi import APIRouter, Depends, HTTPException, Query, UploadFile, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.dependencies import get_tenant_id_from_token
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse, PaginatedResponse
from ai_employee.schemas.publish import (
    BGMCreate,
    BGMListResponse,
    BGMRecommendRequest,
    BGMResponse,
    BGMUpdate,
    CoverGenerateRequest,
    CoverGenerateResponse,
    CoverTemplateCreate,
    CoverTemplateResponse,
    CoverTemplateUpdate,
    MediaFileListResponse,
    MediaFileUploadResponse,
    PublishTaskCreate,
    PublishTaskListResponse,
    PublishTaskResponse,
    PublishTaskUpdate,
)
from ai_employee.services.bgm_recommend import BGMRecommendService
from ai_employee.services.cover_generation import CoverGenerationService
from ai_employee.services.media_storage import MediaStorageService
from ai_employee.services.publish_task_service import PublishTaskService

router = APIRouter()

# Sub-routers
tasks_router = APIRouter(prefix="/publish/tasks")
media_router = APIRouter(prefix="/publish/media")
bgm_router = APIRouter(prefix="/publish/bgm")
covers_router = APIRouter(prefix="/publish/covers")


def _get_tenant_id(tenant_id: uuid.UUID = Depends(get_tenant_id_from_token)) -> str:
    """将 tenant_id UUID 转换为字符串。"""
    return str(tenant_id)


# ==================== Publish Task Endpoints ====================

@tasks_router.post("", response_model=ApiResponse[PublishTaskResponse], status_code=status.HTTP_201_CREATED)
async def create_publish_task(
    data: PublishTaskCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[PublishTaskResponse]:
    """创建发布任务。"""
    service = PublishTaskService(db)
    task_data = data.model_dump()
    task_data["draft_id"] = str(task_data["draft_id"])
    task = await service.create_task(tenant_id=tenant_id, **task_data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="发布任务创建成功",
        data=PublishTaskResponse.model_validate(task),
    )


@tasks_router.get("", response_model=ApiResponse[PublishTaskListResponse])
async def list_publish_tasks(
    platform: str | None = None,
    task_status: str | None = Query(None, alias="status"),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[PublishTaskListResponse]:
    """获取发布任务列表。"""
    service = PublishTaskService(db)
    skip = (page - 1) * page_size
    tasks, total = await service.list_tasks(
        tenant_id=tenant_id,
        platform=platform,
        status=task_status,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PublishTaskListResponse(
            tasks=[PublishTaskResponse.model_validate(t) for t in tasks],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@tasks_router.get("/{task_id}", response_model=ApiResponse[PublishTaskResponse])
async def get_publish_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PublishTaskResponse]:
    """获取发布任务详情。"""
    service = PublishTaskService(db)
    task = await service.get_task_by_id(task_id)
    if not task:
        raise HTTPException(status_code=404, detail="发布任务不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=PublishTaskResponse.model_validate(task))


@tasks_router.patch("/{task_id}", response_model=ApiResponse[PublishTaskResponse])
async def update_publish_task(
    task_id: uuid.UUID,
    data: PublishTaskUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PublishTaskResponse]:
    """更新发布任务。"""
    service = PublishTaskService(db)
    task = await service.update_task(task_id, **data.model_dump(exclude_unset=True))
    if not task:
        raise HTTPException(status_code=404, detail="发布任务不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=PublishTaskResponse.model_validate(task))


@tasks_router.delete("/{task_id}", status_code=204)
async def delete_publish_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除发布任务。"""
    service = PublishTaskService(db)
    deleted = await service.delete_task(task_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="发布任务不存在")


@tasks_router.post("/{task_id}/execute", response_model=ApiResponse[dict])
async def execute_publish_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[dict]:
    """执行发布任务。"""
    service = PublishTaskService(db)
    result = await service.execute_publish(task_id)
    return ApiResponse(code=status.HTTP_200_OK, data=result, message="发布成功")


@tasks_router.post("/{task_id}/retry", response_model=ApiResponse[dict])
async def retry_publish_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[dict]:
    """重试发布任务。"""
    service = PublishTaskService(db)
    result = await service.retry_task(task_id)
    return ApiResponse(code=status.HTTP_200_OK, data=result, message="重试成功")


# ==================== Media File Endpoints ====================

@media_router.post("/upload", response_model=ApiResponse[MediaFileUploadResponse], status_code=status.HTTP_201_CREATED)
async def upload_media_file(
    file: UploadFile,
    file_type: str = Query(..., description="文件类型: image/video/audio"),
    tags: str | None = None,
    description: str | None = None,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[MediaFileUploadResponse]:
    """上传媒体文件。"""
    content = await file.read()
    metadata = {}
    if tags:
        metadata["tags"] = tags
    if description:
        metadata["description"] = description

    service = MediaStorageService(db)
    media_file = await service.upload_file(
        filename=file.filename or "unknown",
        content=content,
        file_type=file_type,
        mime_type=file.content_type or "application/octet-stream",
        metadata=metadata,
    )
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="文件上传成功",
        data=MediaFileUploadResponse.model_validate(media_file),
    )


@media_router.get("", response_model=ApiResponse[MediaFileListResponse])
async def list_media_files(
    file_type: str | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[MediaFileListResponse]:
    """获取媒体文件列表。"""
    service = MediaStorageService(db)
    skip = (page - 1) * page_size
    files, total = await service.list_files(
        file_type=file_type,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=MediaFileListResponse(
            files=[MediaFileUploadResponse.model_validate(f) for f in files],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@media_router.get("/{file_id}", response_model=ApiResponse[MediaFileUploadResponse])
async def get_media_file(
    file_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[MediaFileUploadResponse]:
    """获取媒体文件详情。"""
    service = MediaStorageService(db)
    media_file = await service.get_file_by_id(file_id)
    if not media_file:
        raise HTTPException(status_code=404, detail="媒体文件不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=MediaFileUploadResponse.model_validate(media_file))


@media_router.delete("/{file_id}", status_code=204)
async def delete_media_file(
    file_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除媒体文件。"""
    service = MediaStorageService(db)
    deleted = await service.delete_file(file_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="媒体文件不存在")


# ==================== BGM Endpoints ====================

@bgm_router.post("", response_model=ApiResponse[BGMResponse], status_code=status.HTTP_201_CREATED)
async def create_bgm(
    data: BGMCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[BGMResponse]:
    """添加 BGM。"""
    service = BGMRecommendService(db)
    bgm = await service.add_bgm(**data.model_dump())
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="BGM 添加成功",
        data=BGMResponse.model_validate(bgm),
    )


@bgm_router.get("", response_model=ApiResponse[BGMListResponse])
async def list_bgms(
    genre: str | None = None,
    mood: str | None = None,
    is_popular: bool | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[BGMListResponse]:
    """获取 BGM 列表。"""
    service = BGMRecommendService(db)
    skip = (page - 1) * page_size
    bgms, total = await service.list_bgms(
        genre=genre,
        mood=mood,
        is_popular=is_popular,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=BGMListResponse(
            bgms=[BGMResponse.model_validate(b) for b in bgms],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@bgm_router.get("/{bgm_id}", response_model=ApiResponse[BGMResponse])
async def get_bgm(
    bgm_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[BGMResponse]:
    """获取 BGM 详情。"""
    service = BGMRecommendService(db)
    bgm = await service.get_bgm_by_id(bgm_id)
    if not bgm:
        raise HTTPException(status_code=404, detail="BGM 不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=BGMResponse.model_validate(bgm))


@bgm_router.patch("/{bgm_id}", response_model=ApiResponse[BGMResponse])
async def update_bgm(
    bgm_id: uuid.UUID,
    data: BGMUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[BGMResponse]:
    """更新 BGM。"""
    service = BGMRecommendService(db)
    bgm = await service.update_bgm(bgm_id, **data.model_dump(exclude_unset=True))
    if not bgm:
        raise HTTPException(status_code=404, detail="BGM 不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=BGMResponse.model_validate(bgm))


@bgm_router.delete("/{bgm_id}", status_code=204)
async def delete_bgm(
    bgm_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除 BGM。"""
    service = BGMRecommendService(db)
    deleted = await service.delete_bgm(bgm_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="BGM 不存在")


@bgm_router.post("/recommend", response_model=ApiResponse[list[BGMResponse]])
async def recommend_bgm(
    data: BGMRecommendRequest,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[list[BGMResponse]]:
    """推荐 BGM。"""
    service = BGMRecommendService(db)
    bgms = await service.recommend(
        content_type=data.content_type,
        mood=data.mood,
        genre=data.genre,
        duration_min=data.duration_min,
        duration_max=data.duration_max,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[BGMResponse.model_validate(b) for b in bgms],
    )


# ==================== Cover Template Endpoints ====================

@covers_router.post("/templates", response_model=ApiResponse[CoverTemplateResponse], status_code=status.HTTP_201_CREATED)
async def create_cover_template(
    data: CoverTemplateCreate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[CoverTemplateResponse]:
    """创建封面模板。"""
    service = CoverGenerationService(db)
    template = await service.create_template(**data.model_dump())
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="封面模板创建成功",
        data=CoverTemplateResponse.model_validate(template),
    )


@covers_router.get("/templates", response_model=ApiResponse[list[CoverTemplateResponse]])
async def list_cover_templates(
    platform: str | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[list[CoverTemplateResponse]]:
    """获取封面模板列表。"""
    service = CoverGenerationService(db)
    skip = (page - 1) * page_size
    templates, total = await service.list_templates(
        platform=platform,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[CoverTemplateResponse.model_validate(t) for t in templates],
    )


@covers_router.get("/templates/{template_id}", response_model=ApiResponse[CoverTemplateResponse])
async def get_cover_template(
    template_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[CoverTemplateResponse]:
    """获取封面模板详情。"""
    service = CoverGenerationService(db)
    template = await service.get_template_by_id(template_id)
    if not template:
        raise HTTPException(status_code=404, detail="封面模板不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=CoverTemplateResponse.model_validate(template))


@covers_router.patch("/templates/{template_id}", response_model=ApiResponse[CoverTemplateResponse])
async def update_cover_template(
    template_id: uuid.UUID,
    data: CoverTemplateUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[CoverTemplateResponse]:
    """更新封面模板。"""
    service = CoverGenerationService(db)
    template = await service.update_template(template_id, **data.model_dump(exclude_unset=True))
    if not template:
        raise HTTPException(status_code=404, detail="封面模板不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=CoverTemplateResponse.model_validate(template))


@covers_router.delete("/templates/{template_id}", status_code=204)
async def delete_cover_template(
    template_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除封面模板。"""
    service = CoverGenerationService(db)
    deleted = await service.delete_template(template_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="封面模板不存在")


@covers_router.post("/generate", response_model=ApiResponse[CoverGenerateResponse])
async def generate_cover(
    data: CoverGenerateRequest,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[CoverGenerateResponse]:
    """生成封面图。"""
    service = CoverGenerationService(db)
    result = await service.generate_cover(
        title=data.title,
        platform=data.platform,
        template_id=data.template_id,
        subtitle=data.subtitle,
        background_image=data.background_image,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=CoverGenerateResponse(**result),
        message="封面图生成成功",
    )


# Register sub-routers
router.include_router(tasks_router)
router.include_router(media_router)
router.include_router(bgm_router)
router.include_router(covers_router)
