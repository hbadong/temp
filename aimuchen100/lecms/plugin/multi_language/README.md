# 多语言支持插件

为 LECMS 系统提供文章多语言自动翻译与展示能力。

## 功能

- 支持 66 种语言的内容翻译和展示
- AI 自动翻译（复用 ai-content-factory 的 API 配置）
- 语言路由（URL 前缀 `/en/article/123` + 子域名 `en.domain.com`）
- 语言检测优先级：URL > Cookie > Accept-Language > 默认
- 翻译状态机：pending → translating → translated → verified
- 后台语言配置管理（全局默认 + 站点级覆盖）
- 翻译记录列表和批量翻译
- 翻译审核（标记已验证 / 重新翻译）
- Cron 定时自动翻译
- SEO hreflang 标签注入

## 安装

1. 将 `plugin/multi-language/` 目录上传到服务器
2. 在 LECMS 后台插件管理页面启用 `multi-language` 插件
3. 插件会自动创建 `le_language_config` 和 `le_article_translation` 两张表

## 配置

1. 进入后台「多语言管理」页面
2. 编辑站点语言配置，启用需要的语言
3. 设置默认语言（中文 `zh`）
4. 可选：为每种语言配置独立的 prompt 模板

## 依赖

- LECMS 3.0+
- ai-content-factory 插件（提供 AI API 配置）

## 文件结构

```
plugin/multi-language/
├── conf.php
├── install.php
├── hook/
│   ├── base_control_construct_before.php   # 语言路由
│   ├── cms_article_create.php              # 文章创建自动触发翻译
│   ├── cms_template_render.php             # 模板变量注入
│   └── cms_cron.php                        # Cron 定时翻译
├── model/
│   ├── language_config_model.class.php     # 语言配置 ORM
│   ├── article_translation_model.class.php # 翻译记录 ORM
│   ├── translation_engine.class.php        # AI 翻译引擎
│   └── translation_queue.class.php         # 翻译任务队列
└── admin/
    ├── control/
    │   ├── language_control.class.php      # 语言配置管理
    │   ├── translation_control.class.php   # 翻译记录列表
    │   └── translation_batch_control.class.php  # 批量翻译 + 审核
    └── view/
        ├── language_list.htm
        ├── language_edit.htm
        ├── translation_list.htm
        ├── translation_batch.htm
        └── translation_verify.htm
```

## 使用

### 自动翻译

创建新文章后，系统会自动为每种启用的非默认语言创建翻译记录（pending 状态）。Cron 任务会自动处理待翻译的记录。

### 批量翻译

在后台「批量翻译」页面选择文章 ID 和目标语言，手动触发批量翻译。

### 审核翻译

在「翻译审核」页面查看已翻译的记录，标记为已验证或要求重新翻译。

## 翻译状态

| 状态 | 代码 | 说明 |
|------|------|------|
| 待翻译 | 0 | 翻译任务已创建，等待处理 |
| 翻译中 | 1 | AI 正在翻译 |
| 已翻译 | 2 | 翻译完成，等待审核 |
| 已验证 | 3 | 人工审核通过，锁定不自动更新 |

## License

MIT
