"""Phase 6: AI 拓客助手模块测试。"""

import pytest
from httpx import AsyncClient

pytestmark = pytest.mark.asyncio


# ==================== Monitor Task Tests ====================

class TestMonitorTasks:
    """监控任务测试。"""

    async def test_create_monitor_task(self, client: AsyncClient, auth_headers: dict):
        """测试创建监控任务。"""
        response = await client.post(
            "/api/v1/customer/monitor/tasks",
            json={
                "name": "抖音评论监控",
                "platform": "douyin",
                "target_type": "video",
                "keywords": '["产品", "价格"]',
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["name"] == "抖音评论监控"
        assert data["platform"] == "douyin"

    async def test_list_monitor_tasks(self, client: AsyncClient, auth_headers: dict):
        """测试获取监控任务列表。"""
        await client.post(
            "/api/v1/customer/monitor/tasks",
            json={
                "name": "列表测试监控",
                "platform": "xiaohongshu",
                "target_type": "post",
            },
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/customer/monitor/tasks",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert len(data) >= 1


# ==================== Comment Tests ====================

class TestComments:
    """评论测试。"""

    async def test_list_comments(self, client: AsyncClient, auth_headers: dict):
        """测试获取评论列表。"""
        response = await client.get(
            "/api/v1/customer/comments",
            headers=auth_headers,
        )
        assert response.status_code == 200


# ==================== Reply Template Tests ====================

class TestReplyTemplates:
    """话术模板测试。"""

    async def test_create_reply_template(self, client: AsyncClient, auth_headers: dict):
        """测试创建话术模板。"""
        response = await client.post(
            "/api/v1/customer/reply/templates",
            json={
                "name": "购买意向回复",
                "platform": "douyin",
                "intent_type": "purchase",
                "template_body": "感谢您的关注！我们的产品详情请查看主页链接。",
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["name"] == "购买意向回复"
        assert data["intent_type"] == "purchase"

    async def test_list_reply_templates(self, client: AsyncClient, auth_headers: dict):
        """测试获取话术模板列表。"""
        await client.post(
            "/api/v1/customer/reply/templates",
            json={
                "name": "咨询回复",
                "platform": "douyin",
                "intent_type": "consult",
                "template_body": "您好，有什么可以帮助您的？",
            },
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/customer/reply/templates",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert len(data) >= 1


# ==================== Private Message Tests ====================

class TestPrivateMessages:
    """私信测试。"""

    async def test_list_messages(self, client: AsyncClient, auth_headers: dict):
        """测试获取私信列表。"""
        response = await client.get(
            "/api/v1/customer/messages",
            headers=auth_headers,
        )
        assert response.status_code == 200

    async def test_list_conversations(self, client: AsyncClient, auth_headers: dict):
        """测试获取会话列表。"""
        response = await client.get(
            "/api/v1/customer/messages/conversations",
            headers=auth_headers,
        )
        assert response.status_code == 200


# ==================== Auto Reply Rule Tests ====================

class TestAutoReplyRules:
    """自动回复规则测试。"""

    async def test_create_auto_reply_rule(self, client: AsyncClient, auth_headers: dict):
        """测试创建自动回复规则。"""
        response = await client.post(
            "/api/v1/customer/auto-reply/rules",
            json={
                "name": "购买意向自动回复",
                "platform": "douyin",
                "trigger_type": "intent",
                "trigger_config": '{"intent": "purchase"}',
                "response_content": "感谢关注！我们的产品正在促销中，详情请私信。",
                "delay_min": 5,
                "delay_max": 30,
                "priority": 10,
            },
            headers=auth_headers,
        )
        assert response.status_code == 201
        data = response.json()["data"]
        assert data["name"] == "购买意向自动回复"
        assert data["trigger_type"] == "intent"

    async def test_list_auto_reply_rules(self, client: AsyncClient, auth_headers: dict):
        """测试获取自动回复规则列表。"""
        await client.post(
            "/api/v1/customer/auto-reply/rules",
            json={
                "name": "咨询自动回复",
                "platform": "douyin",
                "trigger_type": "keyword",
                "trigger_config": '{"keywords": ["怎么", "如何"]}',
                "response_content": "您好，请问有什么可以帮助的？",
                "delay_min": 3,
                "delay_max": 15,
            },
            headers=auth_headers,
        )
        response = await client.get(
            "/api/v1/customer/auto-reply/rules",
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert len(data) >= 1


# ==================== Intent Analysis Tests ====================

class TestIntentAnalysis:
    """意图分析测试。"""

    async def test_analyze_intent_purchase(self, client: AsyncClient, auth_headers: dict):
        """测试购买意图分析。"""
        response = await client.post(
            "/api/v1/customer/intent/analyze",
            json={
                "content": "这个产品多少钱？怎么买？",
                "platform": "douyin",
            },
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["intent"] == "purchase"

    async def test_analyze_intent_consult(self, client: AsyncClient, auth_headers: dict):
        """测试咨询意图分析。"""
        response = await client.post(
            "/api/v1/customer/intent/analyze",
            json={
                "content": "请问这个功能怎么使用？",
                "platform": "douyin",
            },
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["intent"] == "consult"

    async def test_analyze_intent_complaint(self, client: AsyncClient, auth_headers: dict):
        """测试投诉意图分析。"""
        response = await client.post(
            "/api/v1/customer/intent/analyze",
            json={
                "content": "质量太差了，我要退款！",
                "platform": "douyin",
            },
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["intent"] == "complaint"

    async def test_analyze_intent_praise(self, client: AsyncClient, auth_headers: dict):
        """测试表扬意图分析。"""
        response = await client.post(
            "/api/v1/customer/intent/analyze",
            json={
                "content": "内容很棒，很喜欢！加油！",
                "platform": "douyin",
            },
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["intent"] == "praise"

    async def test_analyze_sentiment(self, client: AsyncClient, auth_headers: dict):
        """测试情感分析。"""
        response = await client.post(
            "/api/v1/customer/intent/analyze",
            json={
                "content": "这个产品真好用，非常满意！",
                "platform": "douyin",
            },
            headers=auth_headers,
        )
        assert response.status_code == 200
        data = response.json()["data"]
        assert data["sentiment"] == "positive"
