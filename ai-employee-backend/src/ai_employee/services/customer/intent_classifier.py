"""意图分类服务。

模拟 BERT 模型的意图分类能力。
"""

import re
from typing import Any


# 意图分类规则（模拟 BERT 模型）
INTENT_RULES = {
    "purchase": {
        "name": "购买意向",
        "keywords": ["价格", "多少钱", "买", "购买", "下单", "优惠", "折扣", "怎么买", "哪里买", "多少钱", "收费"],
        "patterns": [r"价格", r"多少钱", r"买", r"优惠", r"下单"],
    },
    "consult": {
        "name": "咨询",
        "keywords": ["怎么", "如何", "什么", "为什么", "能不能", "可以吗", "请问", " help", "帮助"],
        "patterns": [r"怎么", r"如何", r"什么", r"能不能", r"可以吗"],
    },
    "complaint": {
        "name": "投诉",
        "keywords": ["差", "不好", "垃圾", "骗子", "退款", "投诉", "举报", "差评", "失望"],
        "patterns": [r"差", r"不好", r"垃圾", r"退款", r"投诉"],
    },
    "praise": {
        "name": "表扬",
        "keywords": ["好", "棒", "优秀", "喜欢", "赞", "不错", "很好", "支持", "加油"],
        "patterns": [r"好", r"棒", r"优秀", r"喜欢", r"赞"],
    },
    "other": {
        "name": "其他",
        "keywords": [],
        "patterns": [],
    },
}

# 情感分类规则
SENTIMENT_RULES = {
    "positive": {
        "name": "正面",
        "keywords": ["好", "棒", "喜欢", "赞", "不错", "很好", "优秀", "加油", "支持"],
    },
    "negative": {
        "name": "负面",
        "keywords": ["差", "不好", "垃圾", "骗子", "失望", "差劲", "烂", "讨厌"],
    },
    "neutral": {
        "name": "中性",
        "keywords": ["怎么", "什么", "如何", "请问", "价格"],
    },
}


class IntentClassifier:
    """意图分类器。

    模拟 BERT 模型的意图分类能力，基于规则实现。
    """

    def __init__(self) -> None:
        self.intent_rules = INTENT_RULES
        self.sentiment_rules = SENTIMENT_RULES

    def classify_intent(self, content: str) -> dict[str, Any]:
        """分类文本意图。

        Args:
            content: 待分类的文本

        Returns:
            dict: 包含 intent、intent_name、confidence、keywords 的结果
        """
        content_lower = content.lower()
        scores = {}

        for intent, config in self.intent_rules.items():
            if intent == "other":
                continue

            score = 0
            matched_keywords = []

            # 关键词匹配
            for keyword in config["keywords"]:
                if keyword.lower() in content_lower:
                    score += 1
                    matched_keywords.append(keyword)

            # 正则模式匹配
            for pattern in config["patterns"]:
                if re.search(pattern, content_lower):
                    score += 2

            if score > 0:
                scores[intent] = {
                    "score": score,
                    "keywords": matched_keywords,
                }

        # 选择最高分的意图
        if scores:
            best_intent = max(scores, key=lambda x: scores[x]["score"])
            max_score = scores[best_intent]["score"]
            # 归一化置信度
            confidence = min(max_score / 5.0, 1.0)
            return {
                "intent": best_intent,
                "intent_name": self.intent_rules[best_intent]["name"],
                "confidence": round(confidence, 2),
                "keywords": scores[best_intent]["keywords"],
            }

        # 默认返回其他
        return {
            "intent": "other",
            "intent_name": "其他",
            "confidence": 0.5,
            "keywords": [],
        }

    def classify_sentiment(self, content: str) -> dict[str, Any]:
        """分类文本情感。

        Args:
            content: 待分类的文本

        Returns:
            dict: 包含 sentiment、sentiment_name、confidence 的结果
        """
        content_lower = content.lower()
        scores = {}

        for sentiment, config in self.sentiment_rules.items():
            score = 0
            for keyword in config["keywords"]:
                if keyword.lower() in content_lower:
                    score += 1

            if score > 0:
                scores[sentiment] = score

        if scores:
            best_sentiment = max(scores, key=lambda x: scores[x])
            max_score = scores[best_sentiment]
            confidence = min(max_score / 3.0, 1.0)
            return {
                "sentiment": best_sentiment,
                "sentiment_name": self.sentiment_rules[best_sentiment]["name"],
                "confidence": round(confidence, 2),
            }

        # 默认返回中性
        return {
            "sentiment": "neutral",
            "sentiment_name": "中性",
            "confidence": 0.5,
        }

    def analyze(self, content: str) -> dict[str, Any]:
        """综合意图和情感分析。

        Args:
            content: 待分析的文本

        Returns:
            dict: 包含 intent、sentiment 等信息的结果
        """
        intent_result = self.classify_intent(content)
        sentiment_result = self.classify_sentiment(content)

        return {
            **intent_result,
            **sentiment_result,
        }
