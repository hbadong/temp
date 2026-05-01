"""Phase 5: 内容发布与媒体服务模块测试。"""

import pytest
from httpx import AsyncClient

pytestmark = pytest.mark.asyncio


# ==================== Publish Task Tests ====================

class TestPublishTasks:
    """发布任务测试。"""

    async def test_create_publish_task(self, client: AsyncClient, auth_headers: dict):
        """测试创建发布任务。"""
        # 先创建草稿
        draft_resp = await client.post(
            "/api/v1/drafts",
            json={
                "title": "发布测试草稿",
                "content": "发布测试内容",
                "platform": "douyin",
                "content_type": "video_script",
            },
            headers=auth_headers,
        )
        draft_id = draft_resp.json()["data"]["id"]

        response = await client.post(
            "/api/v1/publish/tasks",
            json={
                "draft_id": draft_id,
                "platform": "douyin",
                "title": "测试发布",
                "content": "这是测试发布内容",
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["platform"] == "douyin"
        assert data["status"] == "pending"

    async def test_list_publish_tasks(self, client: AsyncClient, auth_headers: dict):
        """测试获取发布任务列表。"""
        # 先创建草稿
        draft_resp = await client.post(
            "/api/v1/drafts",
            json={
                "title": "列表测试草稿",
                "content": "列表测试内容",
                "platform": "douyin",
                "content_type": "video_script",
            },
            headers=auth_headers,
        )
        draft_id = draft_resp.json()["data"]["id"]

        await client.post(
            "/api/v1/publish/tasks",
            json={
                "draft_id": draft_id,
                "platform": "douyin",
                "title": "列表测试发布",
                "content": "列表测试内容",
            },
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/publish/tasks",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["total"] >= 1


# ==================== Media File Tests ====================

class TestMediaFiles:
    """媒体文件测试。"""

    async def test_upload_media_file(self, client: AsyncClient, auth_headers: dict):
        """测试上传媒体文件。"""
        response = await client.post(
            "/api/v1/publish/media/upload?file_type=image",
            files={"file": ("test.png", b"fake image content", "image/png")},
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["file_type"] == "image"

    async def test_list_media_files(self, client: AsyncClient, auth_headers: dict):
        """测试获取媒体文件列表。"""
        await client.post(
            "/api/v1/publish/media/upload?file_type=image&tags=test",
            files={"file": ("test2.png", b"fake image content 2", "image/png")},
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/publish/media",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["total"] >= 1


# ==================== BGM Tests ====================

class TestBGM:
    """BGM 测试。"""

    async def test_create_bgm(self, client: AsyncClient, auth_headers: dict):
        """测试添加 BGM。"""
        response = await client.post(
            "/api/v1/publish/bgm",
            json={
                "title": "测试音乐",
                "artist": "测试艺术家",
                "genre": "流行",
                "mood": "欢快",
                "duration": 180.0,
                "bpm": 120,
                "storage_path": "bgms/test.mp3",
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["title"] == "测试音乐"

    async def test_list_bgms(self, client: AsyncClient, auth_headers: dict):
        """测试获取 BGM 列表。"""
        await client.post(
            "/api/v1/publish/bgm",
            json={
                "title": "列表测试音乐",
                "genre": "摇滚",
                "duration": 200.0,
                "storage_path": "bgms/list_test.mp3",
            },
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/publish/bgm",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["total"] >= 1

    async def test_recommend_bgm(self, client: AsyncClient, auth_headers: dict):
        """测试 BGM 推荐。"""
        response = await client.post(
            "/api/v1/publish/bgm/recommend",
            json={
                "content_type": "vlog",
                "mood": "欢快",
            },
            headers=auth_headers,
        )
        assert response.status_code == 200


# ==================== Cover Template Tests ====================

class TestCoverTemplates:
    """封面模板测试。"""

    async def test_create_cover_template(self, client: AsyncClient, auth_headers: dict):
        """测试创建封面模板。"""
        response = await client.post(
            "/api/v1/publish/covers/templates",
            json={
                "name": "抖音封面模板",
                "platform": "douyin",
                "template_type": "text_overlay",
                "width": 1080,
                "height": 1920,
                "background_color": "#000000",
                "text_color": "#FFFFFF",
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["platform"] == "douyin"

    async def test_list_cover_templates(self, client: AsyncClient, auth_headers: dict):
        """测试获取封面模板列表。"""
        await client.post(
            "/api/v1/publish/covers/templates",
            json={
                "name": "小红书封面模板",
                "platform": "xiaohongshu",
                "template_type": "gradient",
                "width": 1080,
                "height": 1440,
            },
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/publish/covers/templates?platform=xiaohongshu",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert len(data) >= 1


# ==================== Cover Generation Tests ====================

class TestCoverGeneration:
    """封面生成测试。"""

    async def test_generate_cover(self, client: AsyncClient, auth_headers: dict):
        """测试生成封面图。"""
        response = await client.post(
            "/api/v1/publish/covers/generate",
            json={
                "title": "测试封面",
                "platform": "douyin",
                "subtitle": "副标题",
            },
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert "cover_url" in data
        assert data["width"] == 1080
        assert data["height"] == 1920
