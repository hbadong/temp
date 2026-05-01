"""媒体文件存储服务。"""

import uuid
from pathlib import Path
from typing import Any

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.publish import MediaFile


class MediaStorageService:
    """媒体文件存储服务。

    支持本地存储和 MinIO 存储（模拟实现）。
    """

    def __init__(self, db: AsyncSession, storage_type: str = "local") -> None:
        self.db = db
        self.storage_type = storage_type
        self._upload_dir = Path("./uploads")
        self._upload_dir.mkdir(exist_ok=True)

    async def upload_file(
        self,
        filename: str,
        content: bytes,
        file_type: str,
        mime_type: str,
        metadata: dict[str, Any] | None = None,
    ) -> MediaFile:
        """上传文件。

        Args:
            filename: 文件名
            content: 文件内容
            file_type: 文件类型 (image/video/audio)
            mime_type: MIME 类型
            metadata: 额外元数据

        Returns:
            MediaFile: 媒体文件记录
        """
        # 生成唯一文件名
        ext = Path(filename).suffix
        unique_filename = f"{uuid.uuid4().hex}{ext}"
        storage_path = f"{file_type}s/{unique_filename}"

        # 保存到本地（模拟 MinIO）
        full_path = self._upload_dir / storage_path
        full_path.parent.mkdir(parents=True, exist_ok=True)
        full_path.write_bytes(content)

        # 创建数据库记录
        media_file = MediaFile(
            filename=unique_filename,
            original_filename=filename,
            file_type=file_type,
            file_size=len(content),
            mime_type=mime_type,
            storage_path=storage_path,
        )

        # 填充元数据
        if metadata:
            if "width" in metadata:
                media_file.width = metadata["width"]
            if "height" in metadata:
                media_file.height = metadata["height"]
            if "duration" in metadata:
                media_file.duration = metadata["duration"]
            if "tags" in metadata:
                media_file.tags = metadata["tags"]
            if "description" in metadata:
                media_file.description = metadata["description"]

        self.db.add(media_file)
        await self.db.commit()
        await self.db.refresh(media_file)

        return media_file

    async def get_file_by_id(self, file_id: uuid.UUID) -> MediaFile | None:
        """根据 ID 获取文件。"""
        result = await self.db.execute(
            select(MediaFile).where(MediaFile.id == str(file_id))
        )
        return result.scalar_one_or_none()

    async def list_files(
        self,
        file_type: str | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[MediaFile], int]:
        """获取文件列表。"""
        query = select(MediaFile)
        if file_type:
            query = query.where(MediaFile.file_type == file_type)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(MediaFile.created_at.desc()).offset(skip).limit(limit)
        )
        return result.scalars().all(), total

    async def delete_file(self, file_id: uuid.UUID) -> bool:
        """删除文件。"""
        media_file = await self.get_file_by_id(file_id)
        if not media_file:
            return False

        # 删除物理文件
        file_path = self._upload_dir / media_file.storage_path
        if file_path.exists():
            file_path.unlink()

        await self.db.delete(media_file)
        await self.db.commit()
        return True

    def get_file_url(self, storage_path: str) -> str:
        """获取文件访问 URL。"""
        return f"/uploads/{storage_path}"
