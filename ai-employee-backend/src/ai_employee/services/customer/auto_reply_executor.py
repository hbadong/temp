"""自动回复执行器。"""

import json
import random
import re
import uuid
from typing import Any

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.customer import AutoReplyRule
from ai_employee.services.customer.intent_classifier import IntentClassifier


class AutoReplyExecutor:
    """自动回复执行器。

    根据规则匹配评论/私信，执行自动回复。
    """

    def __init__(self, db: AsyncSession) -> None:
        self.db = db
        self.intent_classifier = IntentClassifier()

    async def create_rule(self, tenant_id: str, **kwargs) -> AutoReplyRule:
        """创建自动回复规则。"""
        rule = AutoReplyRule(tenant_id=tenant_id, **kwargs)
        self.db.add(rule)
        await self.db.commit()
        await self.db.refresh(rule)
        return rule

    async def get_rule_by_id(self, rule_id: uuid.UUID) -> AutoReplyRule | None:
        """根据 ID 获取规则。"""
        result = await self.db.execute(
            select(AutoReplyRule).where(AutoReplyRule.id == str(rule_id))
        )
        return result.scalar_one_or_none()

    async def list_rules(
        self,
        tenant_id: str,
        platform: str | None = None,
        trigger_type: str | None = None,
        is_active: bool | None = None,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[AutoReplyRule], int]:
        """获取规则列表。"""
        query = select(AutoReplyRule).where(AutoReplyRule.tenant_id == tenant_id)

        if platform:
            query = query.where(AutoReplyRule.platform == platform)
        if trigger_type:
            query = query.where(AutoReplyRule.trigger_type == trigger_type)
        if is_active is not None:
            query = query.where(AutoReplyRule.is_active == is_active)

        count_result = await self.db.execute(query)
        total = len(count_result.scalars().all())

        result = await self.db.execute(
            query.order_by(AutoReplyRule.priority.desc(), AutoReplyRule.created_at.desc())
            .offset(skip)
            .limit(limit)
        )
        return result.scalars().all(), total

    async def update_rule(self, rule_id: uuid.UUID, **kwargs) -> AutoReplyRule | None:
        """更新规则。"""
        rule = await self.get_rule_by_id(rule_id)
        if not rule:
            return None
        for key, value in kwargs.items():
            if hasattr(rule, key) and value is not None:
                setattr(rule, key, value)
        await self.db.commit()
        await self.db.refresh(rule)
        return rule

    async def delete_rule(self, rule_id: uuid.UUID) -> bool:
        """删除规则。"""
        rule = await self.get_rule_by_id(rule_id)
        if not rule:
            return False
        await self.db.delete(rule)
        await self.db.commit()
        return True

    async def increment_match_count(self, rule_id: uuid.UUID) -> None:
        """增加匹配次数。"""
        rule = await self.get_rule_by_id(rule_id)
        if rule:
            rule.match_count += 1
            await self.db.commit()

    async def find_matching_rule(
        self,
        tenant_id: str,
        platform: str,
        content: str,
        intent: str | None = None,
        sentiment: str | None = None,
    ) -> AutoReplyRule | None:
        """查找匹配的自动回复规则。

        Args:
            tenant_id: 租户 ID
            platform: 平台
            content: 内容
            intent: 意图（可选）
            sentiment: 情感（可选）

        Returns:
            AutoReplyRule | None: 匹配的规则
        """
        # 获取活跃规则，按优先级排序
        query = (
            select(AutoReplyRule)
            .where(
                AutoReplyRule.tenant_id == tenant_id,
                AutoReplyRule.platform == platform,
                AutoReplyRule.is_active == True,
            )
            .order_by(AutoReplyRule.priority.desc())
        )
        result = await self.db.execute(query)
        rules = result.scalars().all()

        for rule in rules:
            if self._is_rule_match(rule, content, intent, sentiment):
                return rule

        return None

    def _is_rule_match(
        self,
        rule: AutoReplyRule,
        content: str,
        intent: str | None = None,
        sentiment: str | None = None,
    ) -> bool:
        """检查规则是否匹配。"""
        if not rule.trigger_config:
            return False

        try:
            trigger_config = json.loads(rule.trigger_config)
        except (json.JSONDecodeError, TypeError):
            return False

        trigger_type = rule.trigger_type

        if trigger_type == "keyword":
            keywords = trigger_config.get("keywords", [])
            content_lower = content.lower()
            for keyword in keywords:
                if keyword.lower() in content_lower:
                    return True

        elif trigger_type == "intent":
            rule_intent = trigger_config.get("intent")
            if rule_intent and intent and rule_intent == intent:
                return True

        elif trigger_type == "sentiment":
            rule_sentiment = trigger_config.get("sentiment")
            if rule_sentiment and sentiment and rule_sentiment == sentiment:
                return True

        return False

    def generate_reply_content(
        self,
        rule: AutoReplyRule,
        variables: dict[str, str] | None = None,
    ) -> str:
        """生成回复内容。

        Args:
            rule: 匹配的规则
            variables: 变量字典

        Returns:
            str: 回复内容
        """
        if rule.response_content:
            content = rule.response_content
        else:
            content = "[未配置回复内容]"

        # 变量替换
        if variables:
            for key, value in variables.items():
                placeholder = f"${{{key}}}"
                content = content.replace(placeholder, str(value))

        return content

    def calculate_delay(self, rule: AutoReplyRule) -> int:
        """计算随机延迟时间。

        Args:
            rule: 匹配的规则

        Returns:
            int: 延迟秒数
        """
        if rule.delay_min == rule.delay_max:
            return rule.delay_min

        return random.randint(rule.delay_min, rule.delay_max)

    async def execute_reply(
        self,
        tenant_id: str,
        platform: str,
        content: str,
        variables: dict[str, str] | None = None,
    ) -> dict[str, Any] | None:
        """执行自动回复。

        Args:
            tenant_id: 租户 ID
            platform: 平台
            content: 原始内容
            variables: 变量字典

        Returns:
            dict | None: 回复结果
        """
        # 分析意图和情感
        analysis = self.intent_classifier.analyze(content)
        intent = analysis.get("intent")
        sentiment = analysis.get("sentiment")

        # 查找匹配规则
        rule = await self.find_matching_rule(
            tenant_id=tenant_id,
            platform=platform,
            content=content,
            intent=intent,
            sentiment=sentiment,
        )

        if not rule:
            return None

        # 增加匹配次数
        await self.increment_match_count(rule.id)

        # 生成回复内容
        reply_content = self.generate_reply_content(rule, variables)

        # 计算延迟
        delay = self.calculate_delay(rule)

        return {
            "rule_id": str(rule.id),
            "rule_name": rule.name,
            "reply_content": reply_content,
            "delay_seconds": delay,
            "intent": intent,
            "sentiment": sentiment,
        }
