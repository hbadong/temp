from fastapi import APIRouter
from pydantic import BaseModel
from app.services.name_service import analyze_name

router = APIRouter(prefix="/api/name", tags=["姓名分析"])

class NameRequest(BaseModel):
    surname: str
    given_name: str
    bazi: dict = None

@router.post("/analyze")
async def analyze_name_endpoint(request: NameRequest):
    missing = []
    if request.bazi and 'missing' in request.bazi:
        missing = request.bazi['missing']
    
    result = analyze_name(request.surname, request.given_name, missing)
    return result
