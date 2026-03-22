from fastapi import APIRouter
from pydantic import BaseModel
from app.services.bazi_service import analyze_bazi

router = APIRouter(prefix="/api/bazi", tags=["八字分析"])

class BazaiRequest(BaseModel):
    birth_year: int
    birth_month: int
    birth_day: int
    birth_hour: int
    gender: str = "male"

@router.post("/analyze")
async def analyze_bazi_endpoint(request: BazaiRequest):
    result = analyze_bazi(
        request.birth_year,
        request.birth_month,
        request.birth_day,
        request.birth_hour
    )
    return result
