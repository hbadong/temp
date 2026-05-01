"""追热模块测试。"""

import pytest
from httpx import AsyncClient

from ai_employee.schemas.trend import TrendCreate


# ==================== 热点数据测试 ====================

@pytest.mark.asyncio
async def test_create_trend(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a new trend."""
    response = await client.post(
        "/api/v1/trends",
        json={
            "platform": "weibo",
            "title": "热门话题测试",
            "content": "这是一个测试热点内容",
            "url": "https://example.com/trend1",
            "hot_value": 1000,
            "category": "科技",
            "tags": "科技,AI,人工智能",
            "source_id": "weibo_001",
        },
        headers=auth_headers,
    )
    assert response.status_code == 201
    data = response.json()
    assert data["data"]["title"] == "热门话题测试"
    assert data["data"]["platform"] == "weibo"


@pytest.mark.asyncio
async def test_create_trend_duplicate(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a trend with duplicate source_id."""
    await client.post(
        "/api/v1/trends",
        json={
            "platform": "weibo",
            "title": "话题1",
            "source_id": "dup_001",
        },
        headers=auth_headers,
    )
    response = await client.post(
        "/api/v1/trends",
        json={
            "platform": "weibo",
            "title": "话题2",
            "source_id": "dup_001",
        },
        headers=auth_headers,
    )
    assert response.status_code == 409


@pytest.mark.asyncio
async def test_list_trends(client: AsyncClient, auth_headers: dict) -> None:
    """Test listing trends."""
    await client.post(
        "/api/v1/trends",
        json={"platform": "douyin", "title": "抖音热点1", "source_id": "dy_001", "hot_value": 500},
        headers=auth_headers,
    )
    await client.post(
        "/api/v1/trends",
        json={"platform": "douyin", "title": "抖音热点2", "source_id": "dy_002", "hot_value": 800},
        headers=auth_headers,
    )
    response = await client.get("/api/v1/trends?platform=douyin", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert len(data["data"]["items"]) == 2


@pytest.mark.asyncio
async def test_get_hot_trends(client: AsyncClient, auth_headers: dict) -> None:
    """Test getting hot trends."""
    await client.post(
        "/api/v1/trends",
        json={"platform": "xiaohongshu", "title": "小红书热点1", "source_id": "xhs_001", "hot_value": 1000},
        headers=auth_headers,
    )
    await client.post(
        "/api/v1/trends",
        json={"platform": "xiaohongshu", "title": "小红书热点2", "source_id": "xhs_002", "hot_value": 2000},
        headers=auth_headers,
    )
    response = await client.get("/api/v1/trends/hot?platform=xiaohongshu&limit=1", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert len(data["data"]) == 1
    assert data["data"][0]["title"] == "小红书热点2"


@pytest.mark.asyncio
async def test_get_trend(client: AsyncClient, auth_headers: dict) -> None:
    """Test getting a trend by ID."""
    create_resp = await client.post(
        "/api/v1/trends",
        json={"platform": "weibo", "title": "获取测试", "source_id": "get_001"},
        headers=auth_headers,
    )
    trend_id = create_resp.json()["data"]["id"]

    response = await client.get(f"/api/v1/trends/{trend_id}", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["title"] == "获取测试"


@pytest.mark.asyncio
async def test_update_trend(client: AsyncClient, auth_headers: dict) -> None:
    """Test updating a trend."""
    create_resp = await client.post(
        "/api/v1/trends",
        json={"platform": "weibo", "title": "更新测试", "source_id": "update_001"},
        headers=auth_headers,
    )
    trend_id = create_resp.json()["data"]["id"]

    response = await client.patch(
        f"/api/v1/trends/{trend_id}",
        json={"title": "更新后的标题", "hot_value": 1500},
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["title"] == "更新后的标题"
    assert data["data"]["hot_value"] == 1500


@pytest.mark.asyncio
async def test_delete_trend(client: AsyncClient, auth_headers: dict) -> None:
    """Test deleting a trend."""
    create_resp = await client.post(
        "/api/v1/trends",
        json={"platform": "weibo", "title": "删除测试", "source_id": "delete_001"},
        headers=auth_headers,
    )
    trend_id = create_resp.json()["data"]["id"]

    response = await client.delete(f"/api/v1/trends/{trend_id}", headers=auth_headers)
    assert response.status_code == 200

    response = await client.get(f"/api/v1/trends/{trend_id}", headers=auth_headers)
    assert response.status_code == 404


# ==================== 账号画像测试 ====================

@pytest.mark.asyncio
async def test_create_profile(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating an account profile."""
    response = await client.post(
        "/api/v1/trends/profiles",
        json={
            "name": "科技博主",
            "platform": "douyin",
            "account_id": "douyin_123",
            "tags": "科技,数码,AI",
            "target_audience": "科技爱好者,程序员",
            "content_categories": "科技,数码评测",
        },
        headers=auth_headers,
    )
    assert response.status_code == 201
    data = response.json()
    assert data["data"]["name"] == "科技博主"


@pytest.mark.asyncio
async def test_list_profiles(client: AsyncClient, auth_headers: dict) -> None:
    """Test listing profiles."""
    await client.post(
        "/api/v1/trends/profiles",
        json={"name": "美食博主", "platform": "xiaohongshu", "account_id": "xhs_food_01"},
        headers=auth_headers,
    )
    await client.post(
        "/api/v1/trends/profiles",
        json={"name": "旅行博主", "platform": "xiaohongshu", "account_id": "xhs_travel_01"},
        headers=auth_headers,
    )
    response = await client.get("/api/v1/trends/profiles?platform=xiaohongshu", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert len(data["data"]["items"]) == 2


@pytest.mark.asyncio
async def test_update_profile(client: AsyncClient, auth_headers: dict) -> None:
    """Test updating a profile."""
    create_resp = await client.post(
        "/api/v1/trends/profiles",
        json={"name": "更新测试", "platform": "weibo", "account_id": "wb_001"},
        headers=auth_headers,
    )
    profile_id = create_resp.json()["data"]["id"]

    response = await client.patch(
        f"/api/v1/trends/profiles/{profile_id}",
        json={"name": "更新后的名字", "tags": "科技,互联网"},
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["name"] == "更新后的名字"


# ==================== 追热任务测试 ====================

@pytest.mark.asyncio
async def test_create_task(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a trend task."""
    trend_resp = await client.post(
        "/api/v1/trends",
        json={
            "platform": "weibo",
            "title": "AI技术突破",
            "tags": "AI,科技",
            "category": "科技",
            "source_id": "task_trend_001",
        },
        headers=auth_headers,
    )
    trend_id = trend_resp.json()["data"]["id"]

    profile_resp = await client.post(
        "/api/v1/trends/profiles",
        json={
            "name": "科技号",
            "platform": "weibo",
            "account_id": "tech_account",
            "tags": "AI,科技,数码",
            "content_categories": "科技",
        },
        headers=auth_headers,
    )
    profile_id = profile_resp.json()["data"]["id"]

    response = await client.post(
        "/api/v1/trends/tasks",
        json={
            "trend_id": trend_id,
            "account_profile_id": profile_id,
            "priority": 50,
        },
        headers=auth_headers,
    )
    assert response.status_code == 201
    data = response.json()
    assert data["data"]["relevance_score"] > 0


@pytest.mark.asyncio
async def test_list_tasks(client: AsyncClient, auth_headers: dict) -> None:
    """Test listing tasks."""
    trend_resp = await client.post(
        "/api/v1/trends",
        json={"platform": "weibo", "title": "任务测试热点", "source_id": "list_task_001"},
        headers=auth_headers,
    )
    trend_id = trend_resp.json()["data"]["id"]

    profile_resp = await client.post(
        "/api/v1/trends/profiles",
        json={"name": "测试号", "platform": "weibo", "account_id": "test_acc"},
        headers=auth_headers,
    )
    profile_id = profile_resp.json()["data"]["id"]

    await client.post(
        "/api/v1/trends/tasks",
        json={"trend_id": trend_id, "account_profile_id": profile_id},
        headers=auth_headers,
    )

    response = await client.get("/api/v1/trends/tasks", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["total"] >= 1


@pytest.mark.asyncio
async def test_get_task(client: AsyncClient, auth_headers: dict) -> None:
    """Test getting a task detail."""
    trend_resp = await client.post(
        "/api/v1/trends",
        json={"platform": "weibo", "title": "详情测试", "source_id": "detail_task_001"},
        headers=auth_headers,
    )
    trend_id = trend_resp.json()["data"]["id"]

    profile_resp = await client.post(
        "/api/v1/trends/profiles",
        json={"name": "详情测试号", "platform": "weibo", "account_id": "detail_acc"},
        headers=auth_headers,
    )
    profile_id = profile_resp.json()["data"]["id"]

    task_resp = await client.post(
        "/api/v1/trends/tasks",
        json={"trend_id": trend_id, "account_profile_id": profile_id},
        headers=auth_headers,
    )
    task_id = task_resp.json()["data"]["id"]

    response = await client.get(f"/api/v1/trends/tasks/{task_id}", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["trend"] is not None
    assert data["data"]["account_profile"] is not None


# ==================== 爆款视频分析测试 ====================

@pytest.mark.asyncio
async def test_create_viral_analysis(client: AsyncClient, auth_headers: dict) -> None:
    """Test creating a viral video analysis."""
    response = await client.post(
        "/api/v1/trends/viral-videos",
        json={
            "video_id": "viral_001",
            "platform": "douyin",
            "title": "爆款视频测试",
            "view_count": 1000000,
            "like_count": 50000,
            "comment_count": 3000,
            "share_count": 10000,
            "viral_score": 0.95,
        },
        headers=auth_headers,
    )
    assert response.status_code == 201
    data = response.json()
    assert data["data"]["video_id"] == "viral_001"
    assert data["data"]["viral_score"] == 0.95


@pytest.mark.asyncio
async def test_get_top_viral_videos(client: AsyncClient, auth_headers: dict) -> None:
    """Test getting top viral videos."""
    await client.post(
        "/api/v1/trends/viral-videos",
        json={"video_id": "top_001", "platform": "douyin", "title": "视频1", "view_count": 1000, "viral_score": 0.7},
        headers=auth_headers,
    )
    await client.post(
        "/api/v1/trends/viral-videos",
        json={"video_id": "top_002", "platform": "douyin", "title": "视频2", "view_count": 2000, "viral_score": 0.9},
        headers=auth_headers,
    )
    response = await client.get("/api/v1/trends/viral-videos/top?platform=douyin&limit=1", headers=auth_headers)
    assert response.status_code == 200
    data = response.json()
    assert len(data["data"]) == 1
    assert data["data"][0]["title"] == "视频2"


@pytest.mark.asyncio
async def test_update_viral_analysis(client: AsyncClient, auth_headers: dict) -> None:
    """Test updating a viral video analysis."""
    create_resp = await client.post(
        "/api/v1/trends/viral-videos",
        json={"video_id": "update_001", "platform": "douyin", "title": "更新测试", "view_count": 100},
        headers=auth_headers,
    )
    analysis_id = create_resp.json()["data"]["id"]

    response = await client.patch(
        f"/api/v1/trends/viral-videos/{analysis_id}",
        json={"structure_analysis": "开头抓人，中间有反转", "viral_score": 0.85},
        headers=auth_headers,
    )
    assert response.status_code == 200
    data = response.json()
    assert data["data"]["structure_analysis"] == "开头抓人，中间有反转"
