"""相关性评分引擎。

实现热点与账号画像的相关性评分，使用 BGE 向量化和余弦相似度计算。
"""

import json
import math
from typing import Any


class RelevanceScoringEngine:
    """相关性评分引擎。

    使用 BGE 向量化和余弦相似度计算热点与账号画像的相关性。
    如果没有向量模型，使用关键词匹配作为回退方案。
    """

    def __init__(self, vector_model: Any = None) -> None:
        """初始化评分引擎。

        Args:
            vector_model: BGE 向量模型实例，如果为 None 则使用关键词匹配
        """
        self.vector_model = vector_model

    def calculate_relevance(
        self,
        trend_title: str,
        trend_content: str | None,
        trend_tags: str | None,
        trend_category: str | None,
        account_tags: str | None,
        account_target_audience: str | None,
        account_content_categories: str | None,
    ) -> float:
        """计算热点与账号的相关性评分。

        Args:
            trend_title: 热点标题
            trend_content: 热点内容
            trend_tags: 热点标签（逗号分隔）
            trend_category: 热点分类
            account_tags: 账号标签（逗号分隔）
            account_target_audience: 目标受众
            account_content_categories: 内容分类（逗号分隔）

        Returns:
            float: 相关性评分 (0.0 - 1.0)
        """
        if self.vector_model:
            return self._calculate_with_vectors(
                trend_title, trend_content, trend_tags, trend_category,
                account_tags, account_target_audience, account_content_categories,
            )
        return self._calculate_with_keywords(
            trend_title, trend_content, trend_tags, trend_category,
            account_tags, account_target_audience, account_content_categories,
        )

    def _calculate_with_vectors(
        self,
        trend_title: str,
        trend_content: str | None,
        trend_tags: str | None,
        trend_category: str | None,
        account_tags: str | None,
        account_target_audience: str | None,
        account_content_categories: str | None,
    ) -> float:
        """使用向量模型计算相关性。"""
        trend_text = f"{trend_title} {trend_content or ''} {trend_tags or ''} {trend_category or ''}"
        account_text = f"{account_tags or ''} {account_target_audience or ''} {account_content_categories or ''}"

        trend_vector = self.vector_model.encode(trend_text)
        account_vector = self.vector_model.encode(account_text)

        return self._cosine_similarity(trend_vector, account_vector)

    def _calculate_with_keywords(
        self,
        trend_title: str,
        trend_content: str | None,
        trend_tags: str | None,
        trend_category: str | None,
        account_tags: str | None,
        account_target_audience: str | None,
        account_content_categories: str | None,
    ) -> float:
        """使用关键词匹配计算相关性（回退方案）。"""
        score = 0.0

        # 标签匹配（权重 40%）
        if trend_tags and account_tags:
            trend_tag_set = {t.strip().lower() for t in trend_tags.split(",") if t.strip()}
            account_tag_set = {t.strip().lower() for t in account_tags.split(",") if t.strip()}
            if trend_tag_set and account_tag_set:
                intersection = trend_tag_set & account_tag_set
                union = trend_tag_set | account_tag_set
                jaccard = len(intersection) / len(union) if union else 0
                score += jaccard * 0.4

        # 分类匹配（权重 30%）
        if trend_category and account_content_categories:
            categories = {c.strip().lower() for c in account_content_categories.split(",") if c.strip()}
            if trend_category.lower() in categories:
                score += 0.3

        # 标题关键词匹配（权重 20%）
        if account_target_audience:
            audience_keywords = {k.strip().lower() for k in account_target_audience.split(",") if k.strip()}
            title_lower = trend_title.lower()
            matched = sum(1 for kw in audience_keywords if kw in title_lower)
            if audience_keywords:
                score += (matched / len(audience_keywords)) * 0.2

        # 内容匹配（权重 10%）
        if trend_content and account_tags:
            account_tag_set = {t.strip().lower() for t in account_tags.split(",") if t.strip()}
            content_lower = trend_content.lower()
            matched = sum(1 for tag in account_tag_set if tag in content_lower)
            if account_tag_set:
                score += (matched / len(account_tag_set)) * 0.1

        return min(score, 1.0)

    @staticmethod
    def _cosine_similarity(vec1: list[float], vec2: list[float]) -> float:
        """计算两个向量的余弦相似度。"""
        if len(vec1) != len(vec2):
            return 0.0

        dot_product = sum(a * b for a, b in zip(vec1, vec2))
        norm1 = math.sqrt(sum(a * a for a in vec1))
        norm2 = math.sqrt(sum(b * b for b in vec2))

        if norm1 == 0 or norm2 == 0:
            return 0.0

        return dot_product / (norm1 * norm2)

    def batch_score(
        self,
        trends: list[dict[str, str | None]],
        account: dict[str, str | None],
    ) -> list[dict[str, Any]]:
        """批量计算多个热点与账号的相关性。

        Args:
            trends: 热点数据列表
            account: 账号画像数据

        Returns:
            list: 包含热点 ID 和评分的结果列表
        """
        results = []
        for trend in trends:
            score = self.calculate_relevance(
                trend_title=trend.get("title", ""),
                trend_content=trend.get("content"),
                trend_tags=trend.get("tags"),
                trend_category=trend.get("category"),
                account_tags=account.get("tags"),
                account_target_audience=account.get("target_audience"),
                account_content_categories=account.get("content_categories"),
            )
            results.append({
                "trend_id": trend.get("id"),
                "relevance_score": round(score, 4),
            })

        results.sort(key=lambda x: x["relevance_score"], reverse=True)
        return results
