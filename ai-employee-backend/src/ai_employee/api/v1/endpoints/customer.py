"""AI 拓客助手模块 API 端点。"""

import uuid

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.dependencies import get_tenant_id_from_token
from ai_employee.db.session import get_db
from ai_employee.schemas.base import ApiResponse
from ai_employee.schemas.customer import (
    AutoReplyRuleCreate,
    AutoReplyRuleResponse,
    AutoReplyRuleUpdate,
    CommentListResponse,
    CommentResponse,
    ConversationResponse,
    IntentAnalysisRequest,
    IntentAnalysisResponse,
    MonitorTaskCreate,
    MonitorTaskResponse,
    MonitorTaskUpdate,
    PrivateMessageListResponse,
    PrivateMessageResponse,
    ReplyTemplateCreate,
    ReplyTemplateResponse,
    ReplyTemplateUpdate,
)
from ai_employee.services.customer.auto_reply_executor import AutoReplyExecutor
from ai_employee.services.customer.comment_service import CommentService
from ai_employee.services.customer.intent_classifier import IntentClassifier
from ai_employee.services.customer.monitor_service import MonitorTaskService
from ai_employee.services.customer.private_message_service import PrivateMessageService
from ai_employee.services.customer.reply_template_service import ReplyTemplateService

router = APIRouter()

# Sub-routers
tasks_router = APIRouter(prefix="/customer/monitor/tasks")
comments_router = APIRouter(prefix="/customer/comments")
templates_router = APIRouter(prefix="/customer/reply/templates")
messages_router = APIRouter(prefix="/customer/messages")
rules_router = APIRouter(prefix="/customer/auto-reply/rules")


def _get_tenant_id(tenant_id: uuid.UUID = Depends(get_tenant_id_from_token)) -> str:
    """将 tenant_id UUID 转换为字符串。"""
    return str(tenant_id)


# ==================== Monitor Task Endpoints ====================

@tasks_router.post("", response_model=ApiResponse[MonitorTaskResponse], status_code=status.HTTP_201_CREATED)
async def create_monitor_task(
    data: MonitorTaskCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[MonitorTaskResponse]:
    """创建监控任务。"""
    service = MonitorTaskService(db)
    task = await service.create_task(tenant_id=tenant_id, **data.model_dump())
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="监控任务创建成功",
        data=MonitorTaskResponse.model_validate(task),
    )


@tasks_router.get("", response_model=ApiResponse[list[MonitorTaskResponse]])
async def list_monitor_tasks(
    platform: str | None = None,
    task_status: str | None = Query(None, alias="status"),
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[list[MonitorTaskResponse]]:
    """获取监控任务列表。"""
    service = MonitorTaskService(db)
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
        data=[MonitorTaskResponse.model_validate(t) for t in tasks],
    )


@tasks_router.get("/{task_id}", response_model=ApiResponse[MonitorTaskResponse])
async def get_monitor_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[MonitorTaskResponse]:
    """获取监控任务详情。"""
    service = MonitorTaskService(db)
    task = await service.get_task_by_id(task_id)
    if not task:
        raise HTTPException(status_code=404, detail="监控任务不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=MonitorTaskResponse.model_validate(task))


@tasks_router.patch("/{task_id}", response_model=ApiResponse[MonitorTaskResponse])
async def update_monitor_task(
    task_id: uuid.UUID,
    data: MonitorTaskUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[MonitorTaskResponse]:
    """更新监控任务。"""
    service = MonitorTaskService(db)
    task = await service.update_task(task_id, **data.model_dump(exclude_unset=True))
    if not task:
        raise HTTPException(status_code=404, detail="监控任务不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=MonitorTaskResponse.model_validate(task))


@tasks_router.delete("/{task_id}", status_code=204)
async def delete_monitor_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除监控任务。"""
    service = MonitorTaskService(db)
    deleted = await service.delete_task(task_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="监控任务不存在")


@tasks_router.post("/{task_id}/execute", response_model=ApiResponse[dict])
async def execute_monitor_task(
    task_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[dict]:
    """执行监控任务。"""
    task_service = MonitorTaskService(db)
    comment_service = CommentService(db)

    task = await task_service.get_task_by_id(task_id)
    if not task:
        raise HTTPException(status_code=404, detail="监控任务不存在")

    # 模拟抓取评论
    target_id = "test_target_1"
    comments = await comment_service.fetch_comments_from_platform(
        tenant_id=tenant_id,
        platform=task.platform,
        target_id=target_id,
        target_type=task.target_type,
    )

    await task_service.increment_run_count(task_id)
    if comments:
        await task_service.increment_comment_count(task_id, len(comments))

    return ApiResponse(
        code=status.HTTP_200_OK,
        data={"fetched_comments": len(comments)},
        message="监控任务执行成功",
    )


# ==================== Comment Endpoints ====================

@comments_router.get("", response_model=ApiResponse[CommentListResponse])
async def list_comments(
    platform: str | None = None,
    target_id: str | None = None,
    sentiment: str | None = None,
    intent: str | None = None,
    is_replied: bool | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[CommentListResponse]:
    """获取评论列表。"""
    service = CommentService(db)
    skip = (page - 1) * page_size
    comments, total = await service.list_comments(
        tenant_id=tenant_id,
        platform=platform,
        target_id=target_id,
        sentiment=sentiment,
        intent=intent,
        is_replied=is_replied,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=CommentListResponse(
            comments=[CommentResponse.model_validate(c) for c in comments],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@comments_router.get("/{comment_id}", response_model=ApiResponse[CommentResponse])
async def get_comment(
    comment_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[CommentResponse]:
    """获取评论详情。"""
    service = CommentService(db)
    comment = await service.get_comment_by_id(comment_id)
    if not comment:
        raise HTTPException(status_code=404, detail="评论不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=CommentResponse.model_validate(comment))


# ==================== Reply Template Endpoints ====================

@templates_router.post("", response_model=ApiResponse[ReplyTemplateResponse], status_code=status.HTTP_201_CREATED)
async def create_reply_template(
    data: ReplyTemplateCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[ReplyTemplateResponse]:
    """创建话术模板。"""
    service = ReplyTemplateService(db)
    template = await service.create_template(tenant_id=tenant_id, **data.model_dump())
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="话术模板创建成功",
        data=ReplyTemplateResponse.model_validate(template),
    )


@templates_router.get("", response_model=ApiResponse[list[ReplyTemplateResponse]])
async def list_reply_templates(
    platform: str | None = None,
    intent_type: str | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[list[ReplyTemplateResponse]]:
    """获取话术模板列表。"""
    service = ReplyTemplateService(db)
    skip = (page - 1) * page_size
    templates, total = await service.list_templates(
        tenant_id=tenant_id,
        platform=platform,
        intent_type=intent_type,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[ReplyTemplateResponse.model_validate(t) for t in templates],
    )


@templates_router.get("/{template_id}", response_model=ApiResponse[ReplyTemplateResponse])
async def get_reply_template(
    template_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ReplyTemplateResponse]:
    """获取话术模板详情。"""
    service = ReplyTemplateService(db)
    template = await service.get_template_by_id(template_id)
    if not template:
        raise HTTPException(status_code=404, detail="话术模板不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=ReplyTemplateResponse.model_validate(template))


@templates_router.patch("/{template_id}", response_model=ApiResponse[ReplyTemplateResponse])
async def update_reply_template(
    template_id: uuid.UUID,
    data: ReplyTemplateUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[ReplyTemplateResponse]:
    """更新话术模板。"""
    service = ReplyTemplateService(db)
    template = await service.update_template(template_id, **data.model_dump(exclude_unset=True))
    if not template:
        raise HTTPException(status_code=404, detail="话术模板不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=ReplyTemplateResponse.model_validate(template))


@templates_router.delete("/{template_id}", status_code=204)
async def delete_reply_template(
    template_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除话术模板。"""
    service = ReplyTemplateService(db)
    deleted = await service.delete_template(template_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="话术模板不存在")


# ==================== Private Message Endpoints ====================

@messages_router.get("", response_model=ApiResponse[PrivateMessageListResponse])
async def list_messages(
    platform: str | None = None,
    conversation_id: str | None = None,
    direction: str | None = None,
    is_read: bool | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[PrivateMessageListResponse]:
    """获取私信列表。"""
    service = PrivateMessageService(db)
    skip = (page - 1) * page_size
    messages, total = await service.list_messages(
        tenant_id=tenant_id,
        platform=platform,
        conversation_id=conversation_id,
        direction=direction,
        is_read=is_read,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=PrivateMessageListResponse(
            messages=[PrivateMessageResponse.model_validate(m) for m in messages],
            total=total,
            page=page,
            page_size=page_size,
            has_next=(skip + page_size) < total,
            has_prev=page > 1,
        ),
    )


@messages_router.get("/conversations", response_model=ApiResponse[list[ConversationResponse]])
async def list_conversations(
    platform: str | None = None,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[list[ConversationResponse]]:
    """获取会话列表。"""
    service = PrivateMessageService(db)
    conversations = await service.get_conversations(
        tenant_id=tenant_id,
        platform=platform,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[ConversationResponse(**c) for c in conversations],
    )


@messages_router.get("/{message_id}", response_model=ApiResponse[PrivateMessageResponse])
async def get_message(
    message_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PrivateMessageResponse]:
    """获取私信详情。"""
    service = PrivateMessageService(db)
    message = await service.get_message_by_id(message_id)
    if not message:
        raise HTTPException(status_code=404, detail="私信不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=PrivateMessageResponse.model_validate(message))


@messages_router.post("/{message_id}/read", response_model=ApiResponse[PrivateMessageResponse])
async def mark_message_as_read(
    message_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[PrivateMessageResponse]:
    """标记私信为已读。"""
    service = PrivateMessageService(db)
    message = await service.mark_as_read(message_id)
    if not message:
        raise HTTPException(status_code=404, detail="私信不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=PrivateMessageResponse.model_validate(message))


# ==================== Auto Reply Rule Endpoints ====================

@rules_router.post("", response_model=ApiResponse[AutoReplyRuleResponse], status_code=status.HTTP_201_CREATED)
async def create_auto_reply_rule(
    data: AutoReplyRuleCreate,
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[AutoReplyRuleResponse]:
    """创建自动回复规则。"""
    service = AutoReplyExecutor(db)
    rule_data = data.model_dump()
    if rule_data.get("response_template_id"):
        rule_data["response_template_id"] = str(rule_data["response_template_id"])
    rule = await service.create_rule(tenant_id=tenant_id, **rule_data)
    return ApiResponse(
        code=status.HTTP_201_CREATED,
        message="自动回复规则创建成功",
        data=AutoReplyRuleResponse.model_validate(rule),
    )


@rules_router.get("", response_model=ApiResponse[list[AutoReplyRuleResponse]])
async def list_auto_reply_rules(
    platform: str | None = None,
    trigger_type: str | None = None,
    is_active: bool | None = None,
    page: int = Query(1, ge=1),
    page_size: int = Query(20, ge=1, le=100),
    db: AsyncSession = Depends(get_db),
    tenant_id: str = Depends(_get_tenant_id),
) -> ApiResponse[list[AutoReplyRuleResponse]]:
    """获取自动回复规则列表。"""
    service = AutoReplyExecutor(db)
    skip = (page - 1) * page_size
    rules, total = await service.list_rules(
        tenant_id=tenant_id,
        platform=platform,
        trigger_type=trigger_type,
        is_active=is_active,
        skip=skip,
        limit=page_size,
    )
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=[AutoReplyRuleResponse.model_validate(r) for r in rules],
    )


@rules_router.get("/{rule_id}", response_model=ApiResponse[AutoReplyRuleResponse])
async def get_auto_reply_rule(
    rule_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[AutoReplyRuleResponse]:
    """获取自动回复规则详情。"""
    service = AutoReplyExecutor(db)
    rule = await service.get_rule_by_id(rule_id)
    if not rule:
        raise HTTPException(status_code=404, detail="自动回复规则不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=AutoReplyRuleResponse.model_validate(rule))


@rules_router.patch("/{rule_id}", response_model=ApiResponse[AutoReplyRuleResponse])
async def update_auto_reply_rule(
    rule_id: uuid.UUID,
    data: AutoReplyRuleUpdate,
    db: AsyncSession = Depends(get_db),
) -> ApiResponse[AutoReplyRuleResponse]:
    """更新自动回复规则。"""
    service = AutoReplyExecutor(db)
    rule = await service.update_rule(rule_id, **data.model_dump(exclude_unset=True))
    if not rule:
        raise HTTPException(status_code=404, detail="自动回复规则不存在")
    return ApiResponse(code=status.HTTP_200_OK, data=AutoReplyRuleResponse.model_validate(rule))


@rules_router.delete("/{rule_id}", status_code=204)
async def delete_auto_reply_rule(
    rule_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
):
    """删除自动回复规则。"""
    service = AutoReplyExecutor(db)
    deleted = await service.delete_rule(rule_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="自动回复规则不存在")


# ==================== Intent Analysis Endpoints ====================

@router.post("/customer/intent/analyze", response_model=ApiResponse[IntentAnalysisResponse])
async def analyze_intent(
    data: IntentAnalysisRequest,
) -> ApiResponse[IntentAnalysisResponse]:
    """分析文本意图。"""
    classifier = IntentClassifier()
    result = classifier.analyze(data.content)
    return ApiResponse(
        code=status.HTTP_200_OK,
        data=IntentAnalysisResponse(
            intent=result["intent"],
            intent_name=result["intent_name"],
            confidence=result["confidence"],
            sentiment=result["sentiment"],
            sentiment_name=result["sentiment_name"],
            keywords=result.get("keywords", []),
        ),
    )


# Register sub-routers
router.include_router(tasks_router)
router.include_router(comments_router)
router.include_router(templates_router)
router.include_router(messages_router)
router.include_router(rules_router)
