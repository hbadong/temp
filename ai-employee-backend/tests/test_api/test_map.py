"""Phase 7: 地图拓客模块测试。"""

import json

import pytest
from httpx import AsyncClient

pytestmark = pytest.mark.asyncio


# ==================== Map Search Task Tests ====================

class TestMapSearchTasks:
    """地图搜索任务测试。"""

    async def test_create_search_task(self, client: AsyncClient, auth_headers: dict):
        """测试创建地图搜索任务。"""
        data = {
            "name": "北京餐饮搜索",
            "platform": "amap",
            "keywords": json.dumps(["餐饮", "美食"]),
            "city": "北京",
            "radius": 5000,
        }
        response = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        assert response.status_code == 201
        result = response.json()
        assert result["data"]["name"] == "北京餐饮搜索"
        assert result["data"]["platform"] == "amap"
        assert result["data"]["city"] == "北京"
        assert result["data"]["status"] == "pending"

    async def test_list_search_tasks(self, client: AsyncClient, auth_headers: dict):
        """测试获取地图搜索任务列表。"""
        response = await client.get("/api/v1/map/tasks", headers=auth_headers)
        assert response.status_code == 200

    async def test_get_search_task(self, client: AsyncClient, auth_headers: dict):
        """测试获取地图搜索任务详情。"""
        # 先创建
        data = {
            "name": "详情测试任务",
            "platform": "baidu",
            "keywords": json.dumps(["测试"]),
            "city": "上海",
        }
        resp = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        task_id = resp.json()["data"]["id"]

        response = await client.get(f"/api/v1/map/tasks/{task_id}", headers=auth_headers)
        assert response.status_code == 200
        assert response.json()["data"]["id"] == task_id

    async def test_update_search_task(self, client: AsyncClient, auth_headers: dict):
        """测试更新地图搜索任务。"""
        data = {
            "name": "更新测试任务",
            "platform": "amap",
            "keywords": json.dumps(["测试"]),
            "city": "北京",
        }
        resp = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        task_id = resp.json()["data"]["id"]

        response = await client.patch(
            f"/api/v1/map/tasks/{task_id}",
            json={"status": "running"},
            headers=auth_headers,
        )
        assert response.status_code == 200
        assert response.json()["data"]["status"] == "running"

    async def test_delete_search_task(self, client: AsyncClient, auth_headers: dict):
        """测试删除地图搜索任务。"""
        data = {
            "name": "删除测试任务",
            "platform": "amap",
            "keywords": json.dumps(["测试"]),
            "city": "北京",
        }
        resp = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        task_id = resp.json()["data"]["id"]

        response = await client.delete(f"/api/v1/map/tasks/{task_id}", headers=auth_headers)
        assert response.status_code == 200

    async def test_execute_search_task(self, client: AsyncClient, auth_headers: dict):
        """测试执行地图搜索任务。"""
        data = {
            "name": "执行测试任务",
            "platform": "amap",
            "keywords": json.dumps(["餐饮"]),
            "city": "北京",
        }
        resp = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        task_id = resp.json()["data"]["id"]

        response = await client.post(f"/api/v1/map/tasks/{task_id}/execute", headers=auth_headers)
        assert response.status_code == 200
        result = response.json()
        assert result["data"]["total"] > 0


# ==================== POI Tests ====================

class TestPOIs:
    """POI 测试。"""

    async def test_list_pois(self, client: AsyncClient, auth_headers: dict):
        """测试获取 POI 列表。"""
        # 先执行一个任务创建 POI
        data = {
            "name": "POI 列表测试任务",
            "platform": "amap",
            "keywords": json.dumps(["餐饮"]),
            "city": "北京",
        }
        resp = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        task_id = resp.json()["data"]["id"]
        await client.post(f"/api/v1/map/tasks/{task_id}/execute", headers=auth_headers)

        response = await client.get("/api/v1/map/pois", headers=auth_headers)
        assert response.status_code == 200
        result = response.json()
        assert len(result["data"]["pois"]) > 0

    async def test_get_poi(self, client: AsyncClient, auth_headers: dict):
        """测试获取 POI 详情。"""
        # 先执行一个任务创建 POI
        data = {
            "name": "POI 详情测试任务",
            "platform": "amap",
            "keywords": json.dumps(["餐饮"]),
            "city": "北京",
        }
        resp = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        task_id = resp.json()["data"]["id"]
        await client.post(f"/api/v1/map/tasks/{task_id}/execute", headers=auth_headers)

        # 获取第一个 POI
        resp = await client.get("/api/v1/map/pois", headers=auth_headers)
        poi_id = resp.json()["data"]["pois"][0]["id"]

        response = await client.get(f"/api/v1/map/pois/{poi_id}", headers=auth_headers)
        assert response.status_code == 200
        assert response.json()["data"]["id"] == poi_id


# ==================== Lead Customer Tests ====================

class TestLeadCustomers:
    """潜在客户测试。"""

    async def test_create_lead_from_poi(self, client: AsyncClient, auth_headers: dict):
        """测试从 POI 创建潜在客户。"""
        # 先执行任务创建 POI
        data = {
            "name": "潜在客户测试任务",
            "platform": "amap",
            "keywords": json.dumps(["餐饮"]),
            "city": "北京",
        }
        resp = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        task_id = resp.json()["data"]["id"]
        await client.post(f"/api/v1/map/tasks/{task_id}/execute", headers=auth_headers)

        # 获取第一个 POI
        resp = await client.get("/api/v1/map/pois", headers=auth_headers)
        poi_id = resp.json()["data"]["pois"][0]["id"]

        response = await client.post(f"/api/v1/map/leads/{poi_id}", headers=auth_headers)
        assert response.status_code == 201
        result = response.json()
        assert result["data"]["score"] > 0

    async def test_list_leads(self, client: AsyncClient, auth_headers: dict):
        """测试获取潜在客户列表。"""
        response = await client.get("/api/v1/map/leads", headers=auth_headers)
        assert response.status_code == 200

    async def test_update_lead(self, client: AsyncClient, auth_headers: dict):
        """测试更新潜在客户。"""
        # 先创建 POI 和 Lead
        data = {
            "name": "更新 Lead 测试任务",
            "platform": "amap",
            "keywords": json.dumps(["餐饮"]),
            "city": "北京",
        }
        resp = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        task_id = resp.json()["data"]["id"]
        await client.post(f"/api/v1/map/tasks/{task_id}/execute", headers=auth_headers)

        resp = await client.get("/api/v1/map/pois", headers=auth_headers)
        poi_id = resp.json()["data"]["pois"][0]["id"]

        resp = await client.post(f"/api/v1/map/leads/{poi_id}", headers=auth_headers)
        lead_id = resp.json()["data"]["id"]

        response = await client.patch(
            f"/api/v1/map/leads/{lead_id}",
            params={"status": "contacted", "notes": "已联系"},
            headers=auth_headers,
        )
        assert response.status_code == 200
        assert response.json()["data"]["status"] == "contacted"


# ==================== Lead Scoring Tests ====================

class TestLeadScoring:
    """潜在客户评分测试。"""

    async def test_score_lead_basic(self, client: AsyncClient, auth_headers: dict):
        """测试基础评分。"""
        data = {
            "name": "某某科技有限公司",
            "category": "科技",
            "address": "北京市朝阳区某某路 123 号",
            "phone": "010-12345678",
            "platform": "amap",
        }
        response = await client.post("/api/v1/map/leads/score", json=data, headers=auth_headers)
        print("Response:", response.status_code, response.text)
        assert response.status_code == 200
        result = response.json()
        assert result["data"]["score"] > 0
        assert result["data"]["level"] in ["A", "B", "C", "D"]

    async def test_score_engine_complete_info(self):
        """测试完整信息的评分引擎。"""
        from ai_employee.services.map.lead_scoring import LeadScoringEngine
        engine = LeadScoringEngine()
        result = engine.score(
            name="北京某某科技有限公司",
            category="科技",
            address="北京市朝阳区某某路 123 号",
            phone="010-12345678",
            email="info@example.com",
            website="https://example.com",
        )
        assert result["score"] >= 80
        assert result["level"] == "A"

    async def test_score_engine_minimal_info(self):
        """测试信息缺失的评分引擎。"""
        from ai_employee.services.map.lead_scoring import LeadScoringEngine
        engine = LeadScoringEngine()
        result = engine.score(name="小店")
        assert result["score"] < 60
        assert result["level"] in ["C", "D"]


# ==================== Store Visit Note Tests ====================

class TestStoreVisitNotes:
    """探店笔记测试。"""

    async def _create_poi(self, client, auth_headers):
        """辅助方法：创建 POI 并返回 ID。"""
        data = {
            "name": "探店笔记测试任务",
            "platform": "amap",
            "keywords": json.dumps(["餐饮"]),
            "city": "北京",
        }
        resp = await client.post("/api/v1/map/tasks", json=data, headers=auth_headers)
        task_id = resp.json()["data"]["id"]
        await client.post(f"/api/v1/map/tasks/{task_id}/execute", headers=auth_headers)

        resp = await client.get("/api/v1/map/pois", headers=auth_headers)
        return resp.json()["data"]["pois"][0]["id"]

    async def test_create_store_visit_note(self, client: AsyncClient, auth_headers: dict):
        """测试创建探店笔记。"""
        poi_id = await self._create_poi(client, auth_headers)
        data = {
            "poi_id": poi_id,
            "title": "探店笔记标题",
            "content": "探店笔记内容",
            "platform": "xiaohongshu",
            "style": "casual",
        }
        response = await client.post("/api/v1/map/notes", json=data, headers=auth_headers)
        assert response.status_code == 201
        assert response.json()["data"]["title"] == "探店笔记标题"

    async def test_generate_store_visit_note(self, client: AsyncClient, auth_headers: dict):
        """测试生成探店笔记。"""
        poi_id = await self._create_poi(client, auth_headers)
        response = await client.post(
            f"/api/v1/map/notes/generate/{poi_id}",
            params={"platform": "xiaohongshu", "style": "professional"},
            headers=auth_headers,
        )
        assert response.status_code == 201
        result = response.json()
        assert "探店" in result["data"]["title"]
        assert result["data"]["platform"] == "xiaohongshu"

    async def test_list_store_visit_notes(self, client: AsyncClient, auth_headers: dict):
        """测试获取探店笔记列表。"""
        poi_id = await self._create_poi(client, auth_headers)
        await client.post(
            f"/api/v1/map/notes/generate/{poi_id}",
            params={"platform": "xiaohongshu"},
            headers=auth_headers,
        )

        response = await client.get("/api/v1/map/notes", headers=auth_headers)
        assert response.status_code == 200
        result = response.json()
        assert len(result["data"]["notes"]) > 0

    async def test_update_store_visit_note(self, client: AsyncClient, auth_headers: dict):
        """测试更新探店笔记。"""
        poi_id = await self._create_poi(client, auth_headers)
        resp = await client.post(
            f"/api/v1/map/notes/generate/{poi_id}",
            params={"platform": "xiaohongshu"},
            headers=auth_headers,
        )
        note_id = resp.json()["data"]["id"]

        response = await client.patch(
            f"/api/v1/map/notes/{note_id}",
            json={"status": "published"},
            headers=auth_headers,
        )
        assert response.status_code == 200
        assert response.json()["data"]["status"] == "published"

    async def test_delete_store_visit_note(self, client: AsyncClient, auth_headers: dict):
        """测试删除探店笔记。"""
        poi_id = await self._create_poi(client, auth_headers)
        resp = await client.post(
            f"/api/v1/map/notes/generate/{poi_id}",
            params={"platform": "xiaohongshu"},
            headers=auth_headers,
        )
        note_id = resp.json()["data"]["id"]

        response = await client.delete(f"/api/v1/map/notes/{note_id}", headers=auth_headers)
        assert response.status_code == 200
