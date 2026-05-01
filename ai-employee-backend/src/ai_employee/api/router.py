"""API router aggregation."""

from fastapi import APIRouter

from ai_employee.api.v1.endpoints import auth, tenants, users

api_router = APIRouter()

# Include v1 endpoints
api_router.include_router(auth.router, prefix="/v1")
api_router.include_router(users.router, prefix="/v1")
api_router.include_router(tenants.router, prefix="/v1")
