"""潜在客户评分引擎。

模拟 XGBoost 模型的评分能力。
"""

from typing import Any


class LeadScoringEngine:
    """潜在客户评分引擎。

    基于规则模拟 XGBoost 模型的评分能力。
    """

    def __init__(self) -> None:
        self._weights = {
            "has_phone": 0.25,
            "has_email": 0.15,
            "has_website": 0.10,
            "category_score": 0.20,
            "name_quality": 0.15,
            "address_quality": 0.15,
        }

        # 行业评分映射
        self._category_scores = {
            "购物": 0.9,
            "餐饮": 0.85,
            "酒店": 0.80,
            "教育": 0.75,
            "医疗": 0.85,
            "金融": 0.90,
            "科技": 0.85,
            "房地产": 0.80,
            "汽车": 0.85,
            "美容": 0.75,
            "健身": 0.70,
            "娱乐": 0.65,
            "零售": 0.80,
        }

    def score(
        self,
        name: str,
        category: str | None = None,
        address: str | None = None,
        phone: str | None = None,
        email: str | None = None,
        website: str | None = None,
    ) -> dict[str, Any]:
        """计算潜在客户评分。

        Args:
            name: 名称
            category: 类别
            address: 地址
            phone: 电话
            email: 邮箱
            website: 网站

        Returns:
            dict: 包含 score、factors、reason、level 的结果
        """
        factors = []
        score = 0.0

        # 1. 电话评分 (25%)
        if phone and len(phone) >= 7:
            score += self._weights["has_phone"]
            factors.append("有联系电话")
        else:
            factors.append("缺少联系电话")

        # 2. 邮箱评分 (15%)
        if email and "@" in email:
            score += self._weights["has_email"]
            factors.append("有邮箱地址")
        else:
            factors.append("缺少邮箱地址")

        # 3. 网站评分 (10%)
        if website and ("http" in website or "." in website):
            score += self._weights["has_website"]
            factors.append("有官方网站")
        else:
            factors.append("缺少官方网站")

        # 4. 类别评分 (20%)
        category_score = self._category_scores.get(category, 0.5)
        score += self._weights["category_score"] * category_score
        if category_score >= 0.8:
            factors.append(f"高价值行业: {category}")
        elif category_score >= 0.6:
            factors.append(f"中等价值行业: {category}")
        else:
            factors.append(f"低价值行业: {category}")

        # 5. 名称质量评分 (15%)
        name_score = self._evaluate_name(name)
        score += self._weights["name_quality"] * name_score
        if name_score >= 0.8:
            factors.append("名称规范，疑似正规企业")
        else:
            factors.append("名称不够规范")

        # 6. 地址质量评分 (15%)
        address_score = self._evaluate_address(address)
        score += self._weights["address_quality"] * address_score
        if address_score >= 0.8:
            factors.append("地址详细完整")
        elif address_score >= 0.5:
            factors.append("地址信息一般")
        else:
            factors.append("地址信息缺失")

        # 归一化到 0-100
        final_score = round(min(score * 100, 100), 2)

        # 确定等级
        level = self._get_level(final_score)

        # 生成原因
        reason = self._generate_reason(final_score, factors)

        return {
            "score": final_score,
            "factors": factors,
            "reason": reason,
            "level": level,
        }

    def _evaluate_name(self, name: str) -> float:
        """评估名称质量。"""
        score = 0.5  # 基础分

        if len(name) >= 4:
            score += 0.2

        if any(kw in name for kw in ["公司", "企业", "集团", "中心", "店", "馆"]):
            score += 0.3

        return min(score, 1.0)

    def _evaluate_address(self, address: str | None) -> float:
        """评估地址质量。"""
        if not address:
            return 0.0

        score = 0.3  # 基础分

        if len(address) >= 10:
            score += 0.3

        if any(kw in address for kw in ["路", "街", "号", "楼", "室", "区"]):
            score += 0.4

        return min(score, 1.0)

    def _get_level(self, score: float) -> str:
        """获取评分等级。"""
        if score >= 80:
            return "A"
        elif score >= 60:
            return "B"
        elif score >= 40:
            return "C"
        else:
            return "D"

    def _generate_reason(self, score: float, factors: list[str]) -> str:
        """生成评分原因。"""
        if score >= 80:
            return "高质量潜在客户，信息完整，建议优先联系"
        elif score >= 60:
            return "中等质量潜在客户，信息较完整，建议跟进"
        elif score >= 40:
            return "一般质量潜在客户，信息不完整，可观望"
        else:
            return "低质量潜在客户，信息缺失严重，不建议投入精力"
