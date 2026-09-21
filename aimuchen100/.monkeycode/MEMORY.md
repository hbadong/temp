# User Instruction Memory

This file records user instructions, preferences, and teachings for reference in future interactions.

## Format

### User Instruction Entry
User instruction entries should follow this format:

[User Instruction Summary]
- Date: [YYYY-MM-DD]
- Context: [Mentioned scenario or time]
- Instructions:
  - [Content of user teaching or instruction, described line by line]

### Project Knowledge Entry
Entries discovered by the Agent during task execution should follow this format:

[Project Knowledge Summary]
- Date: [YYYY-MM-DD]
- Context: Discovered by Agent while performing [specific task description]
- Category: [Operations & Deployment|Build Methods|Testing Methods|Troubleshooting & Debugging|Workflow & Collaboration|Environment Configuration]
- Instructions:
  - [Specific knowledge points, described line by line]

## Deduplication Strategy
- Before adding a new entry, check for similar or identical instructions.
- If a duplicate is found, skip the new entry or merge it with the existing one.
- When merging, update the context or date information.
- This helps avoid redundant entries and keeps the memory file tidy.

## Entries

[Project Knowledge Summary]
- Date: 2026-09-20
- Context: Discovered by Agent while performing T3 template_rewrite 插件实施调试（LECMS 站群前端debug=2/后台debug_admin=0）
- Category: Environment Configuration
- Instructions:
  - 后台 admin 请求 `debug_admin=0`，admin 控制器与模型走编译缓存（runcache/admin_control、runcache/lecms_model），修改 admin 插件源码必须删除 runcache 对应编译文件才会生效；前台 `debug=2` 源码直载，插件 hook 修改即时生效。
  - view_display_after hook 在 DEBUG 模式下会被框架显式 include 全部启用插件的同名 hook；若每个 hook 各自 `echo $html` 会输出重复页面。约定：hook 只就地修改 `$html` 后 return，由 view::display() 末尾统一 echo 一次（template_manager/template_rewrite 均已改为不 echo）。
  - admin 语境下 `core::model('site_manager')->get_config()` 取不到配置（模型 get 经缓存返回空），读配置应直接 `$this->db->fetch_first` 取 site_manager.config 列再 json_decode，避免覆盖写丢其它插件配置。
  - FORM_HASH 校验：`form_submit()` 比对 `R('FORM_HASH','P')` 与动态生成的 `form_hash()`（substr(md5(substr(REQUEST_TIME,0,-5).auth_key),16)），跨请求复用必然失败；POST 前需先 GET 页面提取当次 FORM_HASH 再提交。
  - 插件启用方式：在 `lecms/config/plugin.inc.php` 添加 `'插件名' => array('enable'=>1)` 即激活 hooks（hooks 从 plugin/{名}/hook/*.php 内联加载）。