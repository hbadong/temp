"""地图拓客模块模型。

包含地图搜索任务、POI 数据、潜在客户、探店笔记等模型。
"""

import uuid

from sqlalchemy import Float, ForeignKey, Integer, String, Text
from sqlalchemy.orm import Mapped, mapped_column, relationship

from ai_employee.models.base import BaseModel


class MapSearchTask(BaseModel):
    """地图搜索任务模型。

    存储地图搜索任务的配置和状态。
    """

    __tablename__ = "map_search_tasks"

    name: Mapped[str] = mapped_column(String(255), nullable=False)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)
    platform: Mapped[str] = mapped_column(String(50), nullable=False)  # amap/baidu
    keywords: Mapped[str] = mapped_column(Text, nullable=False)  # JSON 列表
    city: Mapped[str] = mapped_column(String(100), nullable=False)
    radius: Mapped[int] = mapped_column(Integer, default=5000, nullable=False)  # 米
    center_lat: Mapped[float | None] = mapped_column(Float, nullable=True)
    center_lng: Mapped[float | None] = mapped_column(Float, nullable=True)
    status: Mapped[str] = mapped_column(String(50), default="pending", nullable=False, index=True)
    total_results: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    processed_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    error_message: Mapped[str | None] = mapped_column(String(1000), nullable=True)

    def __repr__(self) -> str:
        return f"<MapSearchTask(id={self.id}, name={self.name}, city={self.city})>"


class POI(BaseModel):
    """POI (Point of Interest) 模型。

    存储地图搜索到的地点信息。
    """

    __tablename__ = "pois"

    source_id: Mapped[str] = mapped_column(String(255), nullable=False, index=True)
    task_id: Mapped[str | None] = mapped_column(String(36), ForeignKey("map_search_tasks.id"), nullable=True)
    name: Mapped[str] = mapped_column(String(255), nullable=False)
    category: Mapped[str | None] = mapped_column(String(100), nullable=True, index=True)
    address: Mapped[str | None] = mapped_column(Text, nullable=True)
    phone: Mapped[str | None] = mapped_column(String(50), nullable=True)
    lat: Mapped[float] = mapped_column(Float, nullable=False)
    lng: Mapped[float] = mapped_column(Float, nullable=False)
    platform: Mapped[str] = mapped_column(String(50), nullable=False)
    raw_data: Mapped[str | None] = mapped_column(Text, nullable=True)
    # 评分字段
    lead_score: Mapped[float | None] = mapped_column(Float, nullable=True)
    lead_score_reason: Mapped[str | None] = mapped_column(Text, nullable=True)
    is_processed: Mapped[bool] = mapped_column(default=False, nullable=False)
    tags: Mapped[str | None] = mapped_column(String(500), nullable=True)

    def __repr__(self) -> str:
        return f"<POI(id={self.id}, name={self.name}, category={self.category})>"


class LeadCustomer(BaseModel):
    """潜在客户模型。

    存储从 POI 数据中提取的潜在客户信息。
    """

    __tablename__ = "lead_customers"

    poi_id: Mapped[str] = mapped_column(String(36), ForeignKey("pois.id"), nullable=False, index=True)
    company_name: Mapped[str] = mapped_column(String(255), nullable=False)
    industry: Mapped[str | None] = mapped_column(String(100), nullable=True)
    contact_person: Mapped[str | None] = mapped_column(String(100), nullable=True)
    phone: Mapped[str | None] = mapped_column(String(50), nullable=True)
    email: Mapped[str | None] = mapped_column(String(255), nullable=True)
    website: Mapped[str | None] = mapped_column(String(500), nullable=True)
    score: Mapped[float] = mapped_column(Float, default=0.0, nullable=False)
    score_factors: Mapped[str | None] = mapped_column(Text, nullable=True)  # JSON
    status: Mapped[str] = mapped_column(String(50), default="new", nullable=False, index=True)
    notes: Mapped[str | None] = mapped_column(Text, nullable=True)
    assigned_to: Mapped[str | None] = mapped_column(String(36), nullable=True)

    def __repr__(self) -> str:
        return f"<LeadCustomer(id={self.id}, company={self.company_name}, score={self.score})>"


class StoreVisitNote(BaseModel):
    """探店笔记模型。

    存储 AI 生成的探店笔记内容。
    """

    __tablename__ = "store_visit_notes"

    poi_id: Mapped[str] = mapped_column(String(36), ForeignKey("pois.id"), nullable=False, index=True)
    title: Mapped[str] = mapped_column(String(500), nullable=False)
    content: Mapped[str] = mapped_column(Text, nullable=False)
    platform: Mapped[str] = mapped_column(String(50), nullable=False, index=True)
    style: Mapped[str | None] = mapped_column(String(50), nullable=True)
    images: Mapped[str | None] = mapped_column(Text, nullable=True)  # JSON 列表
    tags: Mapped[str | None] = mapped_column(String(500), nullable=True)
    status: Mapped[str] = mapped_column(String(50), default="draft", nullable=False)
    publish_url: Mapped[str | None] = mapped_column(String(1000), nullable=True)

    def __repr__(self) -> str:
        return f"<StoreVisitNote(id={self.id}, title={self.title}, platform={self.platform})>"
