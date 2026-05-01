"""合规检查引擎。

实现敏感词检测、广告法检查、语义分析等合规检查功能。
"""

import re
from typing import Any


# 敏感词库（示例，实际应从数据库或配置文件加载）
SENSITIVE_WORDS = [
    "违法", "暴力", "色情", "赌博", "毒品",
    "恐怖", "诈骗", "谣言", "诽谤", "歧视",
]

# 广告法禁用词（示例）
ADVERTISING_BANNED_WORDS = [
    "国家级", "最高级", "最佳", "第一", "唯一",
    "顶级", "极致", "完美", "万能", "绝对",
    "100%", "首款", "独家", "填补国内空白",
]


class ComplianceCheckEngine:
    """合规检查引擎。

    提供敏感词检测、广告法检查、语义分析等合规检查功能。
    """

    def __init__(
        self,
        sensitive_words: list[str] | None = None,
        advertising_banned_words: list[str] | None = None,
    ) -> None:
        """初始化合规检查引擎。

        Args:
            sensitive_words: 自定义敏感词列表
            advertising_banned_words: 自定义广告法禁用词列表
        """
        self.sensitive_words = sensitive_words or SENSITIVE_WORDS
        self.advertising_banned_words = advertising_banned_words or ADVERTISING_BANNED_WORDS

    def check(self, content: str, platform: str = "general") -> dict[str, Any]:
        """执行完整的合规检查。

        Args:
            content: 待检查的内容
            platform: 发布平台

        Returns:
            dict: 包含检查结果、分数和问题列表的字典
        """
        issues = []

        # 1. 敏感词检查
        sensitive_issues = self._check_sensitive_words(content)
        issues.extend(sensitive_issues)

        # 2. 广告法检查
        ad_issues = self._check_advertising(content)
        issues.extend(ad_issues)

        # 3. 平台特定规则检查
        platform_issues = self._check_platform_rules(content, platform)
        issues.extend(platform_issues)

        # 计算合规分数
        score = self._calculate_score(issues, len(content))
        is_compliant = score >= 0.8 and not any(i["severity"] == "critical" for i in issues)

        return {
            "is_compliant": is_compliant,
            "score": round(score, 2),
            "issues": issues,
        }

    def _check_sensitive_words(self, content: str) -> list[dict[str, Any]]:
        """检查敏感词。"""
        issues = []
        content_lower = content.lower()

        for word in self.sensitive_words:
            if word.lower() in content_lower:
                position = content_lower.find(word.lower())
                issues.append({
                    "type": "sensitive_word",
                    "severity": "critical",
                    "description": f"检测到敏感词: {word}",
                    "position": position,
                    "suggestion": f"建议替换或删除敏感词「{word}」",
                })

        return issues

    def _check_advertising(self, content: str) -> list[dict[str, Any]]:
        """检查广告法违规词。"""
        issues = []
        content_lower = content.lower()

        for word in self.advertising_banned_words:
            if word.lower() in content_lower:
                position = content_lower.find(word.lower())
                issues.append({
                    "type": "advertising_violation",
                    "severity": "warning",
                    "description": f"检测到广告法禁用词: {word}",
                    "position": position,
                    "suggestion": f"建议替换「{word}」为更客观的描述",
                })

        return issues

    def _check_platform_rules(self, content: str, platform: str) -> list[dict[str, Any]]:
        """检查平台特定规则。"""
        issues = []

        if platform == "douyin":
            # 抖音规则：限制外链
            if re.search(r'https?://', content):
                issues.append({
                    "type": "platform_rule",
                    "severity": "warning",
                    "description": "抖音平台不支持外链",
                    "position": None,
                    "suggestion": "建议移除链接或使用抖音允许的引导方式",
                })

        elif platform == "xiaohongshu":
            # 小红书规则：限制营销词汇
            marketing_words = ["购买", "下单", "优惠", "折扣", "限时"]
            for word in marketing_words:
                if word in content:
                    issues.append({
                        "type": "platform_rule",
                        "severity": "info",
                        "description": f"小红书对营销词汇敏感: {word}",
                        "position": None,
                        "suggestion": f"建议弱化营销语气，减少「{word}」的使用",
                    })

        elif platform == "wechat":
            # 微信公众号规则：标题长度限制
            # 这里简化处理，实际应检查标题
            pass

        return issues

    def _calculate_score(self, issues: list[dict[str, Any]], content_length: int) -> float:
        """计算合规分数。

        Args:
            issues: 问题列表
            content_length: 内容长度

        Returns:
            float: 合规分数 (0.0 - 1.0)
        """
        if not issues:
            return 1.0

        # 根据严重程度扣分
        severity_weights = {
            "critical": 0.3,
            "warning": 0.15,
            "info": 0.05,
        }

        total_deduction = sum(
            severity_weights.get(issue.get("severity", "info"), 0.05)
            for issue in issues
        )

        # 分数不低于 0
        return max(0.0, 1.0 - total_deduction)

    def add_sensitive_words(self, words: list[str]) -> None:
        """添加敏感词。"""
        self.sensitive_words.extend(words)

    def add_advertising_banned_words(self, words: list[str]) -> None:
        """添加广告法禁用词。"""
        self.advertising_banned_words.extend(words)
