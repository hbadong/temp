"""Prompt 模板引擎。

实现变量注入、人设合并、上下文组装等功能。
"""

import re
from string import Template
from typing import Any


class PromptTemplateEngine:
    """Prompt 模板引擎。

    支持变量注入、人设合并、系统提示组装。
    """

    def __init__(self) -> None:
        self._default_system_prompt = (
            "你是一个专业的内容创作者，请根据以下要求生成高质量的内容。"
        )

    def render(
        self,
        template_body: str,
        variables: dict[str, str] | None = None,
        persona_config: dict[str, str | None] | None = None,
        system_prompt: str | None = None,
    ) -> str:
        """渲染 Prompt 模板。

        Args:
            template_body: 模板内容
            variables: 变量字典
            persona_config: 人设配置
            system_prompt: 系统提示

        Returns:
            str: 渲染后的完整 Prompt
        """
        # 1. 变量注入
        rendered = self._inject_variables(template_body, variables or {})

        # 2. 人设合并
        if persona_config:
            rendered = self._merge_persona(rendered, persona_config)

        # 3. 组装系统提示
        full_prompt = self._assemble_system_prompt(system_prompt or self._default_system_prompt, rendered)

        return full_prompt

    def _inject_variables(self, template: str, variables: dict[str, str]) -> str:
        """注入变量到模板。"""
        try:
            # 使用 string.Template 进行变量替换
            t = Template(template)
            return t.safe_substitute(variables)
        except (ValueError, KeyError):
            # 回退到正则替换
            result = template
            for key, value in variables.items():
                pattern = rf"\$\{{{key}\}}|\${key}"
                result = re.sub(pattern, str(value), result)
            return result

    def _merge_persona(self, prompt: str, persona_config: dict[str, str | None]) -> str:
        """合并人设配置到 Prompt。"""
        persona_parts = []

        if persona_config.get("style_config"):
            persona_parts.append(f"写作风格: {persona_config['style_config']}")

        if persona_config.get("tone_config"):
            persona_parts.append(f"语气语调: {persona_config['tone_config']}")

        if persona_config.get("preferred_topics"):
            persona_parts.append(f"偏好主题: {persona_config['preferred_topics']}")

        if persona_config.get("sample_contents"):
            persona_parts.append(f"参考样本:\n{persona_config['sample_contents']}")

        if persona_config.get("forbidden_words"):
            persona_parts.append(f"禁用词汇: {persona_config['forbidden_words']}")

        if persona_config.get("prompt_template"):
            # 如果人设有自己的 prompt 模板，优先使用
            return persona_config["prompt_template"] + "\n\n" + prompt

        if persona_parts:
            persona_section = "\n".join(persona_parts)
            return f"## 人设要求\n{persona_section}\n\n## 内容要求\n{prompt}"

        return prompt

    def _assemble_system_prompt(self, system_prompt: str, user_prompt: str) -> str:
        """组装系统提示和用户提示。"""
        return f"{system_prompt}\n\n{user_prompt}"

    def extract_variables(self, template_body: str) -> list[str]:
        """从模板中提取变量名。"""
        # 匹配 ${var} 或 $var 格式
        pattern = r"\$\{(\w+)\}|\$(\w+)"
        matches = re.findall(pattern, template_body)
        # 展开元组并过滤空字符串
        return [m[0] or m[1] for m in matches if m[0] or m[1]]

    def create_generation_prompt(
        self,
        topic: str,
        platform: str,
        content_type: str,
        template_body: str | None = None,
        variables: dict[str, str] | None = None,
        persona_config: dict[str, str | None] | None = None,
        additional_context: str | None = None,
    ) -> str:
        """创建完整的内容生成 Prompt。

        Args:
            topic: 主题
            platform: 平台
            content_type: 内容类型
            template_body: 模板内容（可选）
            variables: 变量字典
            persona_config: 人设配置
            additional_context: 额外上下文

        Returns:
            str: 完整的生成 Prompt
        """
        # 如果没有模板，使用默认模板
        if not template_body:
            template_body = (
                "请围绕主题「${topic}」创作一篇${content_type}类型的内容，"
                "适合发布在${platform}平台。\n\n"
                "要求：\n"
                "1. 内容有趣、有价值\n"
                "2. 符合平台调性\n"
                "3. 字数适中\n"
                "4. 包含吸引人的开头和结尾"
            )

        # 注入基础变量
        base_vars = {
            "topic": topic,
            "platform": platform,
            "content_type": content_type,
        }
        if variables:
            base_vars.update(variables)

        # 添加额外上下文
        if additional_context:
            base_vars["additional_context"] = additional_context
            template_body += "\n\n额外参考信息：${additional_context}"

        return self.render(
            template_body=template_body,
            variables=base_vars,
            persona_config=persona_config,
        )
