# Requirements Document

## Introduction

本需求定义 **GEO 生成式引擎优化系统**（以下简称 System）。System 面向企业主、品牌运营与 GEO 服务商，帮助用户在主流生成式 AI 搜索平台（豆包、腾讯元宝、通义千问、文心一言、纳米 AI、智谱清言等）提升品牌可见度。

System 提供完整闭环：**AI 可见度诊断 → 关键词/问题蒸馏 → 企业素材沉淀 → AI 文章/视频创作 → 多渠道发布 → 收录与效果监测 → 点数/余额结算**。功能范围与用户后台信息架构对齐站长之家 GEO（geo.tuan.chinaz.cn）用户中心。

本期交付为可独立运行的 Web 产品，包含：官网落地页、账号登录、用户工作台（后台）。管理端（平台运营）作为配套能力一并定义最小集。

## Glossary

- **GEO**：Generative Engine Optimization，生成式引擎优化，指提升品牌在 AI 问答与 AI 搜索结果中的提及率、排名与引用质量。
- **System**：GEO 生成式引擎优化系统。
- **User**：已注册并登录的终端用户（企业客户或代理商操作员）。
- **Admin**：平台运营管理员。
- **Brand**：用户要优化的品牌或产品名称，可含全称与简称。
- **Industry Term**：描述品牌所处行业的词语，用于生成诊断问题与写作主题。
- **AI Platform**：被诊断或被优化的生成式 AI 产品，包括豆包、腾讯元宝、通义千问、文心一言、纳米 AI、智谱清言。
- **Visibility Report**：针对指定 Brand、Industry Term 与 AI Platform 生成的可见度诊断报告。
- **Optimization Advice**：在 Visibility Report 之上给出的可执行优化建议。
- **Point**：点数，用于关键词指数查询、AI 拓词等工具类消耗。
- **Balance**：账户余额（人民币），用于诊断、写作、发布等计费任务。
- **Keyword**：企业核心关键词，作为写作与发布的素材源。
- **Writing Title**：由 Keyword 蒸馏或手动维护的写作标题/意图问题。
- **Writing Instruction**：控制 AI 文章风格、结构与约束的提示词模板。
- **AI Writing Task**：按分类、指令、数量批量生成文章的任务。
- **Publish Channel**：文章或视频的分发渠道，包括 B2B 行业站、网站媒体、自媒体大 V、个人自媒体、官网 SEO、矩阵视频。
- **Inclusion Query**：查询指定 URL 或关键词在目标平台的收录情况，可附截图。
- **Real-Name Verification**：个人或企业实名认证，用于解锁写作、发布、数据中心等能力。
- **Proxy IP**：用户配置的全局代理 IP，供采集、发布脚本使用。
- **TDK**：Title / Description / Keywords 三元组，用于视频与页面元信息分组。

## Requirements

### Requirement 1: 账号登录与会话

**User Story:** AS User, I want 使用账号密码登录工作台, so that 只有授权用户能访问 GEO 能力。

#### Acceptance Criteria

1. WHEN User 打开登录页, the System SHALL 展示账号输入框、密码输入框、「记住登录状态」选项、「登录即同意用户协议」勾选项，以及「立即登录」按钮。
2. WHEN User 提交登录且账号、密码匹配且用户协议已勾选, the System SHALL 建立会话并跳转到用户工作台首页。
3. IF User 提交登录时用户协议未勾选, the System SHALL 提示「请阅读并勾选用户协议」并保持停留在登录页。
4. IF User 提交的账号或密码不匹配, the System SHALL 提示登录失败原因并保持停留在登录页。
5. WHEN User 勾选「记住登录状态」并登录成功, the System SHALL 在 86400 秒内凭本地凭证自动恢复会话。
6. WHEN User 点击「退出账号」, the System SHALL 清除会话并返回登录页。

### Requirement 2: 工作台首页与操作流程

**User Story:** AS User, I want 在首页看到资产概览、创作/发布趋势和标准操作流程, so that 能按步骤完成 GEO 闭环。

#### Acceptance Criteria

1. WHILE User 已登录, the System SHALL 在工作台展示：余额、点数、关键词数量、文章标题数量、写作数量、文章发布数量、授权账号数量、收录数量。
2. WHILE User 已登录, the System SHALL 展示近 5 日「AI 创作量」柱状图与「发布量」柱状图。
3. WHILE User 已登录, the System SHALL 按固定顺序展示操作流程入口：创建关键词、上传图片、创建提示词、创建写作任务、授权账号、创建发布任务、查看报表。
4. WHEN User 点击某个操作流程入口, the System SHALL 打开对应功能页。
5. WHILE User 已登录, the System SHALL 提供快速导航到：数据报表、拓展问题、大模型自动创作、发布任务。
6. WHILE User 已登录, the System SHALL 在侧栏按分组展示全部功能菜单（见 Requirement 3）。

### Requirement 3: 信息架构与导航

**User Story:** AS User, I want 通过分组菜单进入全部 GEO 能力, so that 能按诊断、创作、发布、监测的路径操作。

#### Acceptance Criteria

1. WHILE User 已登录, the System SHALL 提供如下菜单分组与条目：
   - 首页
   - AI 可见度诊断：AI 诊断 Pro 版、AI 可见度诊断、诊断报告
   - AI 创作文章 / AI 素材源力：关键词、写作标题、企业画像图库、企业知识库
   - AI 文章写作：写作指令、文章分类、AI 写作任务、文章列表
   - AI 流量复刻：全网爆文复刻、批量爆文复刻
   - 视频发布：智能 TDK 分组、视频混剪任务、矩阵视频发布（视频任务、视频发布记录、视频素材库）
   - 文章发布：B2B 发布（B2B 行业网站、B2B 自动发布任务、联系方式管理、发布记录）、网站媒体（单条发布媒体、AI 智能发布任务、投稿记录、收藏分组）、自媒体大 V（发布自媒体、投稿记录、收藏分组）、个人自媒体（账号授权、发布任务、发布记录）
   - AI 官网 SEO：站点管理、SEO 发布任务、发布记录
   - AI 数据中心：数据报表、查询(带截图)、查询记录
   - AI 工具助手：关键词指数、AI 拓词、手动拓词工具
   - 消耗明细：点数明细、余额明细
   - 账号：实名认证、账号权益、全局代理 IP、修改密码
2. WHEN User 点击菜单项, the System SHALL 在工作台内容区打开对应页面，并保持侧栏选中状态。

### Requirement 4: 实名认证门禁

**User Story:** AS User, I want 完成个人或企业实名认证后使用核心功能, so that 平台能满足合规要求。

#### Acceptance Criteria

1. WHEN User 打开实名认证页, the System SHALL 提供「个人实名认证」与「企业实名认证」两种入口。
2. WHEN User 提交个人实名认证, the System SHALL 校验真实姓名、身份证号码、手机号均为非空后受理，并将状态置为「审核中」。
3. WHEN User 提交企业实名认证, the System SHALL 校验企业名称、营业执照注册号、营业执照照片、管理员姓名、管理员身份证号、管理员身份证人像面均为非空后受理，并将状态置为「审核中」。
4. WHILE 实名认证状态为「审核中」或「已通过」, the System SHALL 在实名页展示当前状态。
5. WHILE 实名认证状态为「未认证」或「审核中」, the System SHALL 对写作、发布、数据中心、账号权益类页面展示遮罩，并提供「立即实名认证」跳转。
6. WHILE 实名认证状态为「已通过」, the System SHALL 允许 User 访问写作、发布、数据中心、账号权益类页面。
7. WHEN Admin 将认证审核为通过或驳回, the System SHALL 更新 User 的认证状态；驳回时展示驳回原因。

### Requirement 5: AI 可见度诊断（标准版）

**User Story:** AS User, I want 针对品牌与行业词在多个 AI 平台做可见度诊断, so that 能量化当前 GEO 表现。

#### Acceptance Criteria

1. WHEN User 打开「AI 可见度诊断」, the System SHALL 展示可选 AI Platform 列表：豆包、元宝、通义、文心、纳米、智谱。
2. WHEN User 填写品牌名称, the System SHALL 允许用英文逗号分隔多个品牌全称与简称。
3. WHEN User 输入行业词, the System SHALL 允许回车添加，单次任务最多 3 个行业词。
4. WHEN User 选择「无需优化建议」, the System SHALL 将本次任务计费金额显示为标准诊断价（默认 100.00 元）。
5. WHEN User 选择「需生成建议报告」, the System SHALL 将本次任务计费金额显示为建议诊断价（默认 102.00 元），并在完成后同时产出 Optimization Advice。
6. WHEN User 点击「创建 AI 诊断任务」, the System SHALL 二次确认「提交诊断将扣除一定的余额，不可取消」。
7. IF User 未选择任何 AI Platform, the System SHALL 提示「请至少选择一个 AI 大模型平台」并拒绝创建任务。
8. IF 品牌名称为空, the System SHALL 提示「请输入品牌」并拒绝创建任务。
9. IF 行业词数量为 0, the System SHALL 提示「请输入行业」并拒绝创建任务。
10. WHEN 校验通过且 Balance 大于或等于本次计费金额, the System SHALL 扣减 Balance、创建诊断任务、返回任务列表，并提示创建结果。
11. IF Balance 小于本次计费金额, the System SHALL 提示余额不足并保持表单内容。

### Requirement 6: AI 诊断 Pro 版

**User Story:** AS User, I want 输入一个品牌名后自动补全企业信息再做全网可见度诊断, so that 减少手工填写并得到竞品对比。

#### Acceptance Criteria

1. WHEN User 打开「AI 诊断 Pro 版」, the System SHALL 提供字段：品牌名称（仅 1 个）、企业/品牌全称、行业、服务地区、竞品公司。
2. WHEN User 点击品牌全网分析, the System SHALL 二次确认扣费后，根据品牌名称生成全称、行业、服务地区、竞品公司并回填表单。
3. WHILE 品牌全网分析进行中, the System SHALL 展示不可关闭的加载层，并轮播提示「正在搜索全网信息」等进度文案。
4. WHEN User 点击「创建 AI 诊断任务」且必填字段均非空、Balance 充足, the System SHALL 扣费、创建 Pro 诊断任务并跳转到诊断报告列表。
5. WHEN Pro 诊断任务完成, the System SHALL 在报告中包含本品牌与竞品在各 AI Platform 的可见度对比。

### Requirement 7: 诊断报告

**User Story:** AS User, I want 查看历史诊断任务与报告详情, so that 能向客户展示可见度结果。

#### Acceptance Criteria

1. WHEN User 打开诊断报告列表, the System SHALL 展示该 User 的诊断任务，字段至少包括：品牌、行业词、所选平台、类型（标准/Pro）、是否含优化建议、状态、消耗金额、创建时间。
2. WHEN 诊断任务状态为「进行中」, the System SHALL 允许 User 刷新查看最新状态。
3. WHEN 诊断任务状态为「已完成」, the System SHALL 提供报告详情入口，详情包含：各 AI Platform 是否提及品牌、提及原文摘要、可见度得分、截图（如有）。
4. WHEN 任务选择了「需生成建议报告」且状态为「已完成」, the System SHALL 在详情中展示 Optimization Advice 列表。
5. WHEN User 请求导出已完成报告, the System SHALL 生成可下载文件（PDF 或 Excel，以页面提供的格式为准）。

### Requirement 8: 关键词与写作标题

**User Story:** AS User, I want 维护企业关键词并从中蒸馏写作标题, so that AI 写作有稳定素材源。

#### Acceptance Criteria

1. WHEN User 在关键词页新增、编辑、删除或批量导入 Keyword, the System SHALL 将变更限定在该 User 名下并立即反映到列表。
2. WHEN User 打开关键词列表, the System SHALL 支持按关键词文本搜索与分页。
3. WHEN User 在写作标题页新增、编辑、删除 Writing Title, the System SHALL 允许关联已有 Keyword。
4. WHEN User 选择若干 Keyword 并触发蒸馏生成标题, the System SHALL 生成 Writing Title 列表供确认后入库。
5. WHILE 实名认证未通过, the System SHALL 对关键词与写作标题的写操作展示实名遮罩。

### Requirement 9: 企业画像图库与知识库

**User Story:** AS User, I want 上传企业图片与知识资料, so that 写作和视频能引用真实品牌素材。

#### Acceptance Criteria

1. WHEN User 在企业画像图库中创建分组并上传图片, the System SHALL 按分组保存图片，单文件类型限于 jpg、png、bmp、jpeg、gif，单文件大小上限 500MB 配置内的图片限额。
2. WHEN User 删除分组或图片, the System SHALL 仅删除该 User 自己的资源。
3. WHEN User 在企业知识库新增条目, the System SHALL 保存标题、正文或附件，并支持按标题搜索。
4. WHEN AI 写作任务引用知识库, the System SHALL 将对应条目内容纳入生成上下文。

### Requirement 10: 写作指令、文章分类与 AI 写作任务

**User Story:** AS User, I want 用指令和分类批量生成文章, so that 能规模化产出 GEO 内容。

#### Acceptance Criteria

1. WHEN User 创建 Writing Instruction, the System SHALL 保存名称、提示词正文、状态（启用/停用）。
2. WHEN User 创建文章分类, the System SHALL 保存分类名称并用于写作任务与文章列表筛选。
3. WHEN User 创建 AI Writing Task, the System SHALL 要求选择：文章分类、Writing Instruction、写作数量（正整数）、关联 Keyword 或 Writing Title。
4. WHEN 写作任务创建成功且 Balance 充足, the System SHALL 扣费并将任务状态置为「排队中」，随后转为「生成中」直至「已完成」或「失败」。
5. WHEN 写作任务状态变为「已完成」, the System SHALL 将生成的文章写入文章列表，字段包括标题、分类、正文、关联关键词、来源任务、创建时间。
6. WHEN User 在文章列表编辑、删除或按分类/关键词筛选文章, the System SHALL 仅操作该 User 的文章数据。

### Requirement 11: AI 流量复刻

**User Story:** AS User, I want 根据全网爆文生成改写稿, so that 能快速覆盖高流量选题。

#### Acceptance Criteria

1. WHEN User 在「全网爆文复刻」提交源 URL 或源标题, the System SHALL 生成改写文章草案并写入文章列表或待确认列表。
2. WHEN User 在「批量爆文复刻」上传或粘贴多条源标题/URL, the System SHALL 为每条源内容创建改写子任务并汇总进度。
3. IF 源内容无法抓取, the System SHALL 将该条子任务标记为失败并展示失败原因。

### Requirement 12: 智能 TDK 与视频能力

**User Story:** AS User, I want 管理 TDK 分组并生成、发布矩阵视频, so that GEO 覆盖短视频渠道。

#### Acceptance Criteria

1. WHEN User 创建 TDK 分组, the System SHALL 保存分组名及多条 Title、Description、Keywords 组合。
2. WHEN User 创建视频混剪任务, the System SHALL 允许选择视频素材库中的素材、TDK 分组、输出数量，并在完成后写入视频任务列表。
3. WHEN User 创建矩阵视频发布任务, the System SHALL 关联已完成的混剪结果与目标账号，并记录每条发布的状态。
4. WHEN User 打开视频素材库, the System SHALL 支持分组上传 mp4、mov 及图片封面。
5. WHEN User 打开视频发布记录, the System SHALL 展示目标平台、标题、状态、发布时间、失败原因（如有）。

### Requirement 13: B2B 发布

**User Story:** AS User, I want 向 B2B 行业网站批量发布文章, so that 扩大品牌在垂直站点的引用。

#### Acceptance Criteria

1. WHEN User 打开 B2B 行业网站列表, the System SHALL 展示可发布站点名称、行业、状态。
2. WHEN User 维护联系方式, the System SHALL 保存联系人、电话、邮箱、公司名，供发布表单回填。
3. WHEN User 创建 B2B 自动发布任务, the System SHALL 选择文章、目标站点集合、联系方式、发布时间策略（立即或定时）。
4. WHEN 发布任务执行, the System SHALL 为每个目标站点生成一条发布记录，状态为成功或失败。
5. WHEN User 打开 B2B 发布记录, the System SHALL 支持按站点、状态、时间筛选。

### Requirement 14: 网站媒体与自媒体发布

**User Story:** AS User, I want 向网站媒体、自媒体大 V 和个人自媒体投递文章, so that 覆盖新闻站与社交账号。

#### Acceptance Criteria

1. WHEN User 打开「单条发布媒体」, the System SHALL 展示可投媒体列表，并允许单篇投递。
2. WHEN User 创建「AI 智能发布任务」, the System SHALL 按选定媒体集合与文章集合批量投递，并写入投稿记录。
3. WHEN User 将媒体加入收藏分组, the System SHALL 在后续任务中可按分组筛选媒体。
4. WHEN User 打开「发布自媒体」, the System SHALL 展示可投自媒体账号/达人列表并支持投递与记录查询。
5. WHEN User 完成个人自媒体账号授权, the System SHALL 保存授权凭证状态（有效/失效）。
6. WHEN User 创建个人自媒体发布任务, the System SHALL 仅向授权状态为有效的账号发布，并写入发布记录。
7. IF 账号授权已失效, the System SHALL 在发布前提示 User 重新授权。

### Requirement 15: AI 官网 SEO

**User Story:** AS User, I want 把文章发布到自有站点, so that 官网成为 GEO 引用源。

#### Acceptance Criteria

1. WHEN User 新增站点, the System SHALL 保存站点名称、域名、发布接口或部署方式配置。
2. WHEN User 创建 SEO 发布任务, the System SHALL 选择目标站点与文章列表，执行后写入发布记录（URL、状态、时间）。
3. WHEN 发布成功, the System SHALL 记录可访问的文章 URL 供收录查询使用。

### Requirement 16: 数据报表与收录查询

**User Story:** AS User, I want 查看发布与收录数据并带截图核验, so that 能证明优化效果。

#### Acceptance Criteria

1. WHEN User 打开数据报表, the System SHALL 按日汇总创作量、发布量、成功量、失败量，并支持最近 7/30 天切换。
2. WHEN User 提交收录查询（关键词或 URL + 目标平台）, the System SHALL 创建查询任务，消耗约定点数，并在完成后保存是否收录、摘要、截图。
3. WHEN User 打开查询记录, the System SHALL 展示历史查询条件、结果、截图缩略图与时间。
4. WHEN User 点击截图缩略图, the System SHALL 以预览层展示原图。

### Requirement 17: 关键词指数

**User Story:** AS User, I want 查询关键词搜索指数与长尾词, so that 选题有数据依据。

#### Acceptance Criteria

1. WHEN User 输入关键词并点击查询, the System SHALL 扣除 10 点（若点数充足）并返回：搜索指数、移动指数、竞价价格、PC 日搜索量，以及相关长尾词表。
2. IF 点数不足 10, the System SHALL 提示点数不足并拒绝查询。
3. WHEN 查询成功, the System SHALL 允许 User 将长尾词表导出为 Excel，列包括：关键词、搜索指数、移动指数、SEM 竞价、PC 日搜索量。
4. WHILE 查询进行中, the System SHALL 展示加载状态并拒绝重复提交。

### Requirement 18: AI 拓词（蒸馏）

**User Story:** AS User, I want 用 AI 把核心词扩展成意图问题, so that 覆盖用户向 AI 提问的表达。

#### Acceptance Criteria

1. WHEN User 输入核心词并点击蒸馏, the System SHALL 扣除 10 点（若点数充足）并返回问题列表。
2. WHEN 蒸馏成功, the System SHALL 将问题列表展示在可复制文本框中，每行一条。
3. IF 点数不足 10, the System SHALL 提示点数不足并拒绝蒸馏。
4. WHILE 蒸馏进行中, the System SHALL 展示「正在蒸馏中」并拒绝重复提交。

### Requirement 19: 手动拓词工具

**User Story:** AS User, I want 按前缀/主词/通义词/疑问词组合批量生成关键词, so that 不依赖 AI 也能扩词。

#### Acceptance Criteria

1. WHEN User 打开手动拓词工具, the System SHALL 提供六个输入区：前缀 1、前缀 2、主词、通义词、推荐词、疑问词，每区一行一个词，并预填示例词。
2. WHEN User 勾选组合方式并点击「生成关键词」, the System SHALL 以主词为必需项，按所选区域笛卡尔积拼接（词之间无空格）生成去重结果。
3. IF 主词为空, the System SHALL 提示「请在 C 区输入至少一个核心关键词」。
4. IF 未勾选任何组合方式, the System SHALL 提示「请至少选择一个组合模式」。
5. WHEN User 点击「复制结果」, the System SHALL 将当前生成文本写入剪贴板。
6. WHEN User 点击「清空所有」, the System SHALL 清空六个输入区、取消组合勾选，并将结果区复位。

### Requirement 20: 点数、余额与权益

**User Story:** AS User, I want 查看点数、余额流水和账号权益, so that 能核对消耗与套餐权限。

#### Acceptance Criteria

1. WHEN 任何计费动作成功发生, the System SHALL 写入对应流水：点数明细或余额明细，字段包括时间、类型、变动值、余额/剩余点数、备注。
2. WHEN User 打开点数明细或余额明细, the System SHALL 按时间倒序分页展示该 User 的流水。
3. WHEN User 打开账号权益, the System SHALL 展示套餐名称、到期时间、可用 AI Platform、日写作配额、日发布配额。
4. WHEN 套餐到期, the System SHALL 将日配额视为 0，并在创建写作或发布任务时提示权益不足。

### Requirement 21: 全局代理 IP 与个人资料

**User Story:** AS User, I want 配置代理 IP 并修改密码, so that 采集/发布更稳定且账号可自主维护。

#### Acceptance Criteria

1. WHEN User 在全局代理 IP 页新增记录, the System SHALL 保存 IP、端口、协议、账号、密码、状态。
2. WHEN 采集或发布任务执行且 User 存在启用中的代理, the System SHALL 使用该代理发起外网请求。
3. WHEN User 在个人资料页提交新密码且旧密码正确, the System SHALL 更新密码并在下次登录时按新密码校验。
4. IF 旧密码不正确, the System SHALL 拒绝修改并提示原因。

### Requirement 22: 官网落地页

**User Story:** AS 未登录访客, I want 了解 GEO 产品并进入登录, so that 能开始使用系统。

#### Acceptance Criteria

1. WHEN 访客打开官网首页, the System SHALL 展示产品名称、价值主张、支持的 AI Platform 列表、核心优势（AI 蒸馏、排名提升、稳定高效、数据监控、流量增长、持续迭代）以及「企业登录 / 立即体验」入口。
2. WHEN 访客点击「企业登录」或「立即体验」, the System SHALL 进入登录页。

### Requirement 23: 平台管理最小集

**User Story:** AS Admin, I want 审核实名、调整账户点数/余额并查看全站任务, so that 能运营该 SaaS。

#### Acceptance Criteria

1. WHEN Admin 使用独立管理员账号登录管理端, the System SHALL 进入管理后台（与 User 工作台分离）。
2. WHEN Admin 打开实名审核列表, the System SHALL 展示待审、已通过、已驳回记录，并允许通过或驳回。
3. WHEN Admin 为指定 User 调整点数或余额, the System SHALL 写入带操作者备注的流水。
4. WHEN Admin 打开任务监控, the System SHALL 按类型查看诊断、写作、发布、收录任务的状态与失败原因。
5. WHEN Admin 配置标准诊断价、建议诊断价、指数查询点数、蒸馏点数, the System SHALL 在后续 User 下单时使用最新价格。

### Requirement 24: 权限隔离与安全

**User Story:** AS User, I want 只看到自己的数据, so that 多客户共用一套系统时资产隔离。

#### Acceptance Criteria

1. WHEN User 请求任何业务列表或详情, the System SHALL 仅返回该 User 所属数据。
2. WHEN 未登录访问者请求工作台或业务 API, the System SHALL 返回登录页或 401。
3. WHEN System 存储密码, the System SHALL 使用单向哈希，页面与 API 均不以明文回显密码。
4. WHEN 计费接口被调用, the System SHALL 在同一事务中完成余额/点数校验、扣减与任务创建，避免超扣。

### Requirement 25: 异步任务与失败可见

**User Story:** AS User, I want 长耗时能力以后台任务方式运行, so that 关闭页面后任务仍能完成。

#### Acceptance Criteria

1. WHEN User 创建诊断、写作、混剪、发布、收录查询任务, the System SHALL 立即返回任务 ID，并在后台执行。
2. WHILE 任务状态为排队中或进行中, the System SHALL 允许 User 在对应列表看到进度或状态文案。
3. WHEN 任务失败, the System SHALL 将状态置为失败，并保存可供列表展示的失败原因。
4. WHEN User 对失败的写作或发布任务点击重试且余额/点数仍充足, the System SHALL 创建新的执行尝试并继续扣费规则（重试是否二次扣费由价格配置决定，默认重试不重复扣费）。
