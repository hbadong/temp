"""Phase 4: AI 创作助手模块测试。"""

import uuid

import pytest
from httpx import AsyncClient

from ai_employee.db.session import Base
from ai_employee.models.content import ContentTemplate, Persona, ContentDraft, ContentSchedule  # noqa: F401

pytestmark = pytest.mark.asyncio


# ==================== Content Template Tests ====================

class TestContentTemplates:
    """内容模板测试。"""

    async def test_create_template(self, client: AsyncClient, auth_headers: dict):
        """测试创建内容模板。"""
        response = await client.post(
            "/api/v1/templates",
            json={
                "name": "测试模板",
                "platform": "douyin",
                "content_type": "video_script",
                "template_body": "这是一个测试模板 ${topic}",
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["name"] == "测试模板"
        assert data["platform"] == "douyin"

    async def test_list_templates(self, client: AsyncClient, auth_headers: dict):
        """测试获取模板列表。"""
        await client.post(
            "/api/v1/templates",
            json={
                "name": "列表测试模板",
                "platform": "xiaohongshu",
                "content_type": "article",
                "template_body": "列表测试",
            },
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/templates",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["total"] >= 1

    async def test_update_template(self, client: AsyncClient, auth_headers: dict):
        """测试更新模板。"""
        create_resp = await client.post(
            "/api/v1/templates",
            json={
                "name": "更新前",
                "platform": "douyin",
                "content_type": "video_script",
                "template_body": "原始模板",
            },
            headers=auth_headers,
        )
        template_id = create_resp.json()["data"]["id"]

        response = await client.patch(
            f"/api/v1/templates/{template_id}",
            json={"name": "更新后"},
            headers=auth_headers,
        )
        assert response.status_code == 200
        assert response.json()["data"]["name"] == "更新后"

    async def test_delete_template(self, client: AsyncClient, auth_headers: dict):
        """测试删除模板。"""
        create_resp = await client.post(
            "/api/v1/templates",
            json={
                "name": "待删除模板",
                "platform": "douyin",
                "content_type": "video_script",
                "template_body": "待删除",
            },
            headers=auth_headers,
        )
        template_id = create_resp.json()["data"]["id"]

        response = await client.delete(
            f"/api/v1/templates/{template_id}",
            headers=auth_headers,
        )
        assert response.status_code == 204


# ==================== Persona Tests ====================

class TestPersonas:
    """人设测试。"""

    async def test_create_persona(self, client: AsyncClient, auth_headers: dict):
        """测试创建人设。"""
        response = await client.post(
            "/api/v1/personas",
            json={
                "name": "科技博主",
                "platform": "douyin",
                "style_config": "专业、严谨",
                "tone_config": "客观、理性",
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["name"] == "科技博主"

    async def test_list_personas(self, client: AsyncClient, auth_headers: dict):
        """测试获取人设列表。"""
        await client.post(
            "/api/v1/personas",
            json={
                "name": "列表测试人设",
                "platform": "xiaohongshu",
            },
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/personas",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["total"] >= 1


# ==================== Content Draft Tests ====================

class TestContentDrafts:
    """内容草稿测试。"""

    async def test_create_draft(self, client: AsyncClient, auth_headers: dict):
        """测试创建内容草稿。"""
        response = await client.post(
            "/api/v1/drafts",
            json={
                "title": "测试草稿",
                "content": "这是测试内容",
                "platform": "douyin",
                "content_type": "video_script",
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["title"] == "测试草稿"

    async def test_list_drafts(self, client: AsyncClient, auth_headers: dict):
        """测试获取草稿列表。"""
        await client.post(
            "/api/v1/drafts",
            json={
                "title": "列表测试草稿",
                "content": "列表测试内容",
                "platform": "douyin",
                "content_type": "video_script",
            },
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/drafts",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["total"] >= 1


# ==================== Content Generation Tests ====================

class TestContentGeneration:
    """内容生成测试。"""

    async def test_generate_content(self, client: AsyncClient, auth_headers: dict):
        """测试生成内容。"""
        response = await client.post(
            "/api/v1/generate",
            json={
                "topic": "AI 技术趋势",
                "platform": "douyin",
                "content_type": "video_script",
                "num_versions": 2,
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert len(data["drafts"]) == 2


# ==================== Compliance Check Tests ====================

class TestComplianceCheck:
    """合规检查测试。"""

    async def test_check_compliance_clean(self, client: AsyncClient, auth_headers: dict):
        """测试合规检查（干净内容）。"""
        response = await client.post(
            "/api/v1/compliance/check",
            json={
                "content": "这是一篇正常的文章内容，没有任何违规词汇。",
                "platform": "douyin",
            },
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["is_compliant"] is True
        assert data["score"] == 1.0

    async def test_check_compliance_sensitive(self, client: AsyncClient, auth_headers: dict):
        """测试合规检查（含敏感词）。"""
        response = await client.post(
            "/api/v1/compliance/check",
            json={
                "content": "这是最佳产品，国家级品质，绝对好用！",
                "platform": "douyin",
            },
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["is_compliant"] is False
        assert len(data["issues"]) > 0


# ==================== Content Schedule Tests ====================

class TestContentSchedules:
    """内容排期测试。"""

    async def test_create_schedule(self, client: AsyncClient, auth_headers: dict):
        """测试创建内容排期。"""
        # 先创建草稿
        draft_resp = await client.post(
            "/api/v1/drafts",
            json={
                "title": "排期测试草稿",
                "content": "排期测试内容",
                "platform": "douyin",
                "content_type": "video_script",
            },
            headers=auth_headers,
        )
        draft_id = draft_resp.json()["data"]["id"]

        response = await client.post(
            "/api/v1/schedules",
            json={
                "draft_id": draft_id,
                "platform": "douyin",
                "scheduled_at": "2025-01-01 10:00:00",
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["draft_id"] == draft_id

    async def test_calendar_view(self, client: AsyncClient, auth_headers: dict):
        """测试日历视图。"""
        response = await client.get(
            "/api/v1/schedules/calendar?start_date=2025-01-01&end_date=2025-12-31",
            headers=auth_headers,
        )
        assert response.status_code == 200
