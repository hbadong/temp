"""API router aggregation."""

from fastapi import APIRouter

from ai_employee.api.v1.endpoints import (
    audit,
    auth,
    content,
    publish,
    quotas,
    rbac,
    tenants,
    trends,
    users,
)

api_router = APIRouter()

# Include v1 endpoints
api_router.include_router(auth.router, prefix="/v1")
api_router.include_router(users.router, prefix="/v1")
api_router.include_router(tenants.router, prefix="/v1")
api_router.include_router(rbac.router, prefix="/v1")
api_router.include_router(quotas.router, prefix="/v1")
api_router.include_router(audit.router, prefix="/v1")
api_router.include_router(trends.router, prefix="/v1")
api_router.include_router(content.router, prefix="/v1")
api_router.include_router(publish.router, prefix="/v1")
