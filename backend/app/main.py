from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.routers import bazi, name, names

app = FastAPI(title="宝宝起名分析系统 API", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(bazi.router)
app.include_router(name.router)
app.include_router(names.router)

@app.get("/")
async def root():
    return {"message": "宝宝起名分析系统 API", "version": "1.0.0"}

@app.get("/health")
async def health():
    return {"status": "ok"}
