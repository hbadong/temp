"""内容生成服务。"""

import uuid
from datetime import datetime, timezone
from typing import Any

from sqlalchemy.ext.asyncio import AsyncSession

from ai_employee.models.content import ContentDraft, ContentTemplate, Persona
from ai_employee.services.compliance_engine import ComplianceCheckEngine
from ai_employee.services.content_draft_service import ContentDraftService
from ai_employee.services.content_template_service import ContentTemplateService
from ai_employee.services.persona_service import PersonaService
from ai_employee.services.prompt_engine import PromptTemplateEngine


class ContentGenerationService:
    """内容生成服务。

    协调 Prompt 引擎、LLM 调用、合规检查、草稿保存。
    """

    def __init__(self, db: AsyncSession) -> None:
        self.db = db
        self.template_service = ContentTemplateService(db)
        self.persona_service = PersonaService(db)
        self.draft_service = ContentDraftService(db)
        self.prompt_engine = PromptTemplateEngine()
        self.compliance_engine = ComplianceCheckEngine()

    async def generate_content(
        self,
        tenant_id: str,
        topic: str,
        platform: str,
        content_type: str,
        template_id: uuid.UUID | None = None,
        persona_id: uuid.UUID | None = None,
        additional_context: str | None = None,
        num_versions: int = 1,
    ) -> list[ContentDraft]:
        """生成内容草稿。

        Args:
            tenant_id: 租户 ID
            topic: 主题
            platform: 平台
            content_type: 内容类型
            template_id: 模板 ID（可选）
            persona_id: 人设 ID（可选）
            additional_context: 额外上下文
            num_versions: 生成版本数

        Returns:
            list[ContentDraft]: 生成的草稿列表
        """
        # 1. 获取模板（如果有）
        template = None
        template_body = None
        if template_id:
            template = await self.template_service.get_by_id(template_id)
            if template:
                template_body = template.template_body
                await self.template_service.increment_usage(template_id)

        # 2. 获取人设（如果有）
        persona = None
        persona_config = None
        if persona_id:
            persona = await self.persona_service.get_by_id(persona_id)
            if persona:
                persona_config = {
                    "style_config": persona.style_config,
                    "tone_config": persona.tone_config,
                    "prompt_template": persona.prompt_template,
                    "sample_contents": persona.sample_contents,
                    "forbidden_words": persona.forbidden_words,
                    "preferred_topics": persona.preferred_topics,
                }

        # 3. 创建 Prompt
        prompt = self.prompt_engine.create_generation_prompt(
            topic=topic,
            platform=platform,
            content_type=content_type,
            template_body=template_body,
            persona_config=persona_config,
            additional_context=additional_context,
        )

        # 4. 调用 LLM 生成内容（模拟实现，实际应调用 LLM API）
        generated_contents = await self._call_llm(
            prompt=prompt,
            num_versions=num_versions,
            content_type=content_type,
        )

        # 5. 合规检查并保存草稿
        drafts_data = []
        for i, content in enumerate(generated_contents, start=1):
            # 合规检查
            compliance_result = self.compliance_engine.check(content, platform)

            draft_data = {
                "title": f"{topic} - 版本 {i}",
                "content": content,
                "platform": platform,
                "content_type": content_type,
                "template_id": str(template_id) if template_id else None,
                "persona_id": str(persona_id) if persona_id else None,
                "version": i,
                "compliance_score": compliance_result["score"],
                "compliance_issues": str(compliance_result["issues"]) if compliance_result["issues"] else None,
            }
            drafts_data.append(draft_data)

        drafts = await self.draft_service.create_multiple(tenant_id, drafts_data)
        return drafts

    async def _call_llm(
        self,
        prompt: str,
        num_versions: int = 1,
        content_type: str = "article",
    ) -> list[str]:
        """调用 LLM 生成内容。

        这里是模拟实现，实际应接入 OpenAI/通义千问等 LLM API。
        """
        # 模拟生成结果
        contents = []
        for i in range(num_versions):
            if content_type == "video_script":
                content = (
                    f"【视频脚本 v{i + 1}】\n\n"
                    f"开头：大家好，今天我们来聊聊{prompt[:50]}...\n\n"
                    f"正文：这个话题非常有意思...\n\n"
                    f"结尾：觉得有用的话点个赞吧！"
                )
            elif content_type == "article":
                content = (
                    f"【文章 v{i + 1}】\n\n"
                    f"# 标题\n\n"
                    f"正文内容：{prompt[:100]}...\n\n"
                    f"## 总结\n\n"
                    f"希望这篇文章对你有帮助！"
                )
            else:
                content = f"【{content_type} v{i + 1}】\n\n{prompt[:100]}..."
            contents.append(content)

        return contents
