# 需求实施计划

- [ ] 1. 搭建 geo-system 工程骨架与启动入口
  - 创建 `geo-system/server` 与 `geo-system/web` 目录、各自 `package.json`、`start.sh`
  - 后端入口 `server/src/index.js` 使用 `node --experimental-sqlite` 监听 3001，提供健康检查 `GET /api/health`
  - 前端 Vite + Vue3，`vite.config.ts` 配置 `allowedHosts: ['.monkeycode-ai.online']` 与 `/api` 反代 3001
  - 对应设计：目录结构、部署拓扑、C1/C2

- [ ] 2. 实现 SQLite 连接、建表与种子数据
  - 编写 `server/src/db.js`：WAL、`busy_timeout=5000`、启动时 `CREATE TABLE IF NOT EXISTS` 覆盖设计文档全部表
  - 种子用户 `chinaz51008`（已实名、有余额点数）、`demo`（未实名）、管理员 `admin`
  - 种子渠道目录每类不少于 12 条、6 个 AI 平台、示例关键词/文章/诊断报告
  - 对应设计：Data Models、Correctness 9、种子账号；需求 22 落地页可引用平台列表

- [ ] 3. 实现认证、会话与个人资料
  - `POST /api/auth/login`、`POST /api/auth/logout`、`GET /api/me`、`PUT /api/me/password`
  - 密码 scrypt；Cookie `geo_sid`；`keeplogin` 写入 `geo_keep` 86400 秒
  - 未登录业务接口返回 401
  - 对应需求 1、21；设计 C2、Correctness 2/8

- [ ] 3.1 编写登录与会话接口测试
  - 覆盖错误密码、未勾选协议由前端拦截、keeplogin 恢复会话、改密后旧密码失效

- [ ] 4. 实现计费服务与流水
  - `server/src/billing.js`：`charge` 在同一事务用 `WHERE balance>=amount` 扣减并写 `ledger_money` / `ledger_score`
  - 价格读取 `settings`；`GET /api/ledger/score`、`GET /api/ledger/money`
  - 对应需求 20；设计 C3、Correctness 2/3

- [ ] 4.1 编写计费并发与超扣属性测试
  - 属性 3：并发扣费后余额不为负，成功次数与流水条数一致

- [ ] 5. 实现任务 Runner 与 Provider 适配器
  - `server/src/tasks.js` 进程内 tick、单次最多 2 并发、queued→running→done/failed
  - `providers/llm.js` 默认 Mock（seed=hash(brand+keyword+platform) 确定性），可选 `openai_compat` 读 `USER_LLM_*`
  - `providers/channel.js` MockChannel 生成伪 URL，约 10% 单条失败
  - 失败任务 `retry` 且 `billed=1` 时不二次扣费
  - 对应需求 25；设计 C4/C5、Correctness 6/7

- [ ] 6. 实现 KYC 门禁中间件与实名接口
  - 个人/企业提交 `POST /api/kyc/personal|company`，状态 pending
  - 需认证路由在 handler 前检查 `kyc_status=approved`
  - 对应需求 4；设计 C6、Correctness 5

- [ ] 7. 检查点 - 认证、计费、任务骨架可用
  - 启动 API 后登录、扣一笔余额、入队一条 mock 任务至 done
  - 确保所有测试通过,如有疑问请询问用户

- [ ] 8. 实现标准诊断、Pro 诊断与报告 API
  - `POST /api/analyze` 校验平台/品牌/行业词≤3，按是否建议报告取价并入队
  - `POST /api/analyze/pro/parse`、`POST /api/analyze/pro`
  - `GET /api/analyze`、`GET /api/analyze/:id` 含各平台提及、得分、摘要、建议
  - 对应需求 5、6、7

- [ ] 8.1 编写诊断确定性与扣费测试
  - 属性 7：同一品牌+行业+平台两次报告 score/snippet 相同
  - 余额差额等于标价

- [ ] 9. 实现首页仪表盘 API
  - `GET /api/dashboard` 返回余额、点数、关键词/标题/写作/发布/授权/收录数量及近 5 日创作量与发布量
  - 对应需求 2

- [ ] 10. 实现素材域 CRUD API
  - 关键词、写作标题、写作指令、文章分类、知识库、图片分组与上传下载（校验归属）
  - 蒸馏生成标题接口
  - 对应需求 8、9；Correctness 1/9

- [ ] 11. 实现 AI 写作任务与文章列表 API
  - 创建任务扣费入队；完成后写入 articles
  - 文章编辑、删除、按分类/关键词筛选
  - 对应需求 10

- [ ] 12. 实现爆文复刻 API
  - 单条与批量，抓取失败子任务标记 failed
  - 对应需求 11

- [ ] 13. 实现 TDK、视频素材、混剪与矩阵发布 API
  - TDK 分组；素材上传 mp4/mov/封面；混剪任务；发布记录含失败原因
  - 对应需求 12

- [ ] 14. 实现多渠道发布与官网 SEO API
  - 联系方式、B2B/网站媒体/自媒体目录与收藏分组、自动发布任务与记录
  - 个人自媒体模拟授权、失效时拒绝发布
  - 站点管理与 SEO 发布记录（成功则写入可访问 URL）
  - 对应需求 13、14、15

- [ ] 15. 实现数据报表、收录查询与工具 API
  - 报表 7/30 天；收录查询扣点并保存截图占位；指数查询与 AI 拓词各扣 10 点
  - 代理 IP CRUD；账号权益只读
  - 对应需求 16、17、18、20、21

- [ ] 16. 实现管理端 API
  - 独立 `admin_sessions`；实名审核通过/驳回；调账写 operator；任务监控；settings 读写
  - 对应需求 23；Correctness 1

- [ ] 17. 检查点 - 全部后端接口冒烟
  - 编写并运行 `server/test/smoke.mjs` 覆盖登录、诊断扣费、报告、KYC 门禁、指数、拓词、写作出文、B2B 记录、Admin 审核调账
  - 确保所有测试通过,如有疑问请询问用户

- [ ] 18. 实现官网落地页与登录页
  - `/` 展示价值主张、6 个 AI 平台、六项优势、登录入口
  - `/login` 账号、密码、记住登录、协议勾选、文案与校验对齐需求 1
  - 对应需求 1、22

- [ ] 19. 实现工作台壳层与完整侧栏路由
  - `WorkbenchLayout`：顶栏余额/点数、改密、退出；侧栏分组菜单与需求 3 一一对应，禁止空链
  - 未登录跳转 `/login`；需实名页展示遮罩与跳转
  - 对应需求 2、3、4；Correctness 10

- [ ] 20. 实现首页、诊断标准/Pro、报告列表与详情页
  - 首页卡片、双柱状图、操作流程 7 步入口
  - 诊断表单字段/二次确认/价格展示与参考站一致
  - 报告详情展示平台得分、摘要、建议、导出入口
  - 对应需求 2、5、6、7

- [ ] 21. 实现素材、写作、复刻前端页
  - 关键词/标题/图库/知识库；指令/分类/写作任务/文章列表；全网与批量爆文复刻
  - 对应需求 8、9、10、11

- [ ] 22. 实现视频与全部发布前端页
  - TDK、混剪、视频任务/记录/素材库
  - B2B、网站媒体、自媒体大 V、个人自媒体、官网 SEO 各子页
  - 对应需求 12、13、14、15

- [ ] 23. 实现数据中心、工具助手、流水与账号页
  - 报表、收录查询（含截图预览）、查询记录
  - 指数（含 Excel 导出）、AI 拓词、手动拓词纯前端笛卡尔积（六区+组合+复制+清空）
  - 点数/余额明细、实名双入口、权益、代理 IP、改密
  - 对应需求 16–21、19

- [ ] 24. 实现管理端四页
  - `/admin/login` 与 `/admin` 布局
  - 实名审核、调账、任务监控、标价配置
  - 对应需求 23

- [ ] 25. 前端构建验收与启动脚本收口
  - `web` 执行 `npm run build` 通过
  - `start.sh` 同时拉起 API 与 Vite
  - 对应设计 Test Strategy 5、部署拓扑

- [ ] 26. 检查点 - 工作台全菜单可打开且核心闭环可走通
  - 用种子账号走通：登录 → 诊断 → 报告 → 写作任务出文 → B2B 发布记录 → 报表
  - 确保所有测试通过,如有疑问请询问用户
