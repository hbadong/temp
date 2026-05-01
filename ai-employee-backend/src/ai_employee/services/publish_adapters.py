"""内容发布适配器。

实现各平台的发布接口封装。
"""

from abc import ABC, abstractmethod
from typing import Any


class BasePublishAdapter(ABC):
    """发布适配器基类。"""

    @abstractmethod
    async def publish(
        self,
        title: str,
        content: str,
        media_files: list[str] | None = None,
        **kwargs: Any,
    ) -> dict[str, Any]:
        """发布内容到平台。

        Args:
            title: 内容标题
            content: 内容正文
            media_files: 媒体文件 URL 列表
            **kwargs: 其他参数

        Returns:
            dict: 发布结果，包含 publish_url 等信息
        """
        pass

    @abstractmethod
    async def get_publish_status(self, task_id: str) -> dict[str, Any]:
        """获取发布状态。

        Args:
            task_id: 发布任务 ID

        Returns:
            dict: 状态信息
        """
        pass


class DouyinPublishAdapter(BasePublishAdapter):
    """抖音发布适配器。"""

    async def publish(
        self,
        title: str,
        content: str,
        media_files: list[str] | None = None,
        **kwargs: Any,
    ) -> dict[str, Any]:
        """发布内容到抖音。"""
        # 模拟抖音 API 调用
        return {
            "success": True,
            "publish_url": f"https://www.douyin.com/video/mock_{title[:10]}",
            "video_id": f"douyin_{id(title)}",
            "message": "发布成功",
        }

    async def get_publish_status(self, task_id: str) -> dict[str, Any]:
        """获取抖音发布状态。"""
        return {
            "status": "published",
            "views": 0,
            "likes": 0,
            "comments": 0,
        }


class XiaohongshuPublishAdapter(BasePublishAdapter):
    """小红书发布适配器。"""

    async def publish(
        self,
        title: str,
        content: str,
        media_files: list[str] | None = None,
        **kwargs: Any,
    ) -> dict[str, Any]:
        """发布内容到小红书。"""
        # 模拟小红书 API 调用
        return {
            "success": True,
            "publish_url": f"https://www.xiaohongshu.com/explore/mock_{title[:10]}",
            "note_id": f"xhs_{id(title)}",
            "message": "发布成功",
        }

    async def get_publish_status(self, task_id: str) -> dict[str, Any]:
        """获取小红书发布状态。"""
        return {
            "status": "published",
            "views": 0,
            "likes": 0,
            "comments": 0,
        }


class WechatPublishAdapter(BasePublishAdapter):
    """微信公众号发布适配器。"""

    async def publish(
        self,
        title: str,
        content: str,
        media_files: list[str] | None = None,
        **kwargs: Any,
    ) -> dict[str, Any]:
        """发布内容到微信公众号。"""
        # 模拟微信公众号 API 调用
        return {
            "success": True,
            "publish_url": f"https://mp.weixin.qq.com/s/mock_{title[:10]}",
            "article_id": f"wechat_{id(title)}",
            "message": "发布成功",
        }

    async def get_publish_status(self, task_id: str) -> dict[str, Any]:
        """获取微信公众号发布状态。"""
        return {
            "status": "published",
            "reads": 0,
            "likes": 0,
            "comments": 0,
        }


class PublishAdapterFactory:
    """发布适配器工厂。"""

    _adapters = {
        "douyin": DouyinPublishAdapter,
        "xiaohongshu": XiaohongshuPublishAdapter,
        "wechat": WechatPublishAdapter,
    }

    @classmethod
    def get_adapter(cls, platform: str) -> BasePublishAdapter:
        """获取指定平台的发布适配器。

        Args:
            platform: 平台名称

        Returns:
            BasePublishAdapter: 发布适配器实例

        Raises:
            ValueError: 不支持的平台
        """
        adapter_class = cls._adapters.get(platform.lower())
        if not adapter_class:
            raise ValueError(f"不支持的平台: {platform}")
        return adapter_class()

    @classmethod
    def register_adapter(cls, platform: str, adapter_class: type[BasePublishAdapter]) -> None:
        """注册新的发布适配器。

        Args:
            platform: 平台名称
            adapter_class: 适配器类
        """
        cls._adapters[platform.lower()] = adapter_class

    @classmethod
    def get_supported_platforms(cls) -> list[str]:
        """获取支持的平台列表。"""
        return list(cls._adapters.keys())
