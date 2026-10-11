<template>
  <div class="wb" v-if="ready">
    <aside class="side">
      <div class="brand">
        <div class="brand-name">站长之家GEO</div>
        <div class="brand-sub">AI 可见度优化平台</div>
      </div>
      <nav>
        <template v-for="g in menus" :key="g.title">
          <div v-if="g.title" class="sidebar-group">{{ g.title }}</div>
          <div v-for="item in g.items" :key="item.label" class="sub">
            <div v-if="item.children">
              <div class="sub-title" :class="{ collapsed: collapsed[item.label] }" @click="toggle(item.label)">
                <span>{{ item.label }}</span>
                <span class="arr">{{ collapsed[item.label] ? '▸' : '▾' }}</span>
              </div>
              <div v-if="!collapsed[item.label]" class="sub-body">
                <router-link v-for="c in item.children" :key="c.path" :to="c.path" class="link" :class="{ on: isActive(c.path) }">
                  <AppIcon :name="c.icon" class="ic" />
                  <span class="tx">{{ c.label }}</span>
                </router-link>
              </div>
            </div>
            <router-link v-else :to="item.path" class="link" :class="{ on: isActive(item.path) }">
              <AppIcon :name="item.icon" class="ic" />
              <span class="tx">{{ item.label }}</span>
            </router-link>
          </div>
        </template>
      </nav>
    </aside>
    <div class="main">
      <header class="top">
        <div class="crumb">GEO 工作台</div>
        <div class="top-right">
          <span class="chip grad">余额 {{ (me.balance / 100).toFixed(2) }} 元</span>
          <span class="chip">{{ me.score }} 点</span>
          <router-link class="hlink" to="/app/kyc">实名认证</router-link>
          <router-link class="hlink" to="/app/profile">修改密码</router-link>
          <a class="hlink" href="javascript:;" @click="logout">退出</a>
        </div>
      </header>
      <div class="content relative">
        <div v-if="needKyc" class="mask">
          <div class="kyc-tip">
            <div class="kyc-icon">认证</div>
            <div class="kyc-title">你尚未完成实名认证</div>
            <div class="kyc-desc">完成认证后即可使用写作、发布、数据中心等能力</div>
            <router-link class="btn" to="/app/kyc">立即实名认证</router-link>
          </div>
        </div>
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, provide, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'

const router = useRouter()
const route = useRoute()
const me = ref({ balance: 0, score: 0, kyc_status: 'none' })
const ready = ref(false)

const menus = [
  { title: '', items: [{ label: '首页', path: '/app/dashboard', icon: 'home' }] },
  { title: 'AI可见度诊断', items: [
    { label: 'AI诊断Pro版', path: '/app/analyze-pro', icon: 'zap' },
    { label: 'AI可见度诊断', path: '/app/analyze', icon: 'search' },
    { label: '诊断报告', path: '/app/analyze-reports', icon: 'file-text' },
  ]},
  { title: 'AI创作文章', items: [
    { label: 'AI素材源力', children: [
      { label: '关键词', path: '/app/keywords', icon: 'tag' },
      { label: '写作标题', path: '/app/titles', icon: 'type' },
      { label: '企业画像图库', path: '/app/images', icon: 'image' },
      { label: '企业知识库', path: '/app/knowledge', icon: 'book' },
    ]},
    { label: 'AI文章写作', children: [
      { label: '写作指令', path: '/app/instructions', icon: 'command' },
      { label: '文章分类', path: '/app/article-types', icon: 'folder' },
      { label: '创作任务', path: '/app/write-tasks', icon: 'edit' },
      { label: '文章列表', path: '/app/articles', icon: 'file' },
    ]},
    { label: 'AI流量复刻', children: [
      { label: '全网爆文复刻', path: '/app/reprint', icon: 'copy' },
      { label: '批量爆文复刻', path: '/app/reprint-batch', icon: 'layers' },
    ]},
  ]},
  { title: '视频发布', items: [
    { label: '智能TDK分组', path: '/app/tdk', icon: 'grid' },
    { label: '视频混剪任务', path: '/app/video-mix', icon: 'video' },
    { label: '矩阵视频发布', children: [
      { label: '视频任务', path: '/app/video-jobs', icon: 'play' },
      { label: '视频发布记录', path: '/app/video-records', icon: 'monitor' },
      { label: '视频素材库', path: '/app/video-assets', icon: 'film' },
    ]},
  ]},
  { title: '文章发布', items: [
    { label: 'B2B发布', children: [
      { label: 'B2B行业网站', path: '/app/b2b', icon: 'building' },
      { label: 'B2B自动发布任务', path: '/app/b2b-tasks', icon: 'send' },
      { label: '联系方式管理', path: '/app/contacts', icon: 'phone' },
      { label: '发布记录', path: '/app/b2b-records', icon: 'list' },
    ]},
    { label: '网站媒体', children: [
      { label: '单条发布媒体', path: '/app/webmedia', icon: 'globe' },
      { label: 'AI智能发布任务', path: '/app/webmedia-tasks', icon: 'rocket' },
      { label: '投稿记录', path: '/app/webmedia-records', icon: 'file-text' },
      { label: '收藏分组', path: '/app/media-favs-1', icon: 'star' },
    ]},
    { label: '自媒体大V', children: [
      { label: '发布自媒体', path: '/app/zimedia', icon: 'users' },
      { label: '投稿记录', path: '/app/zimedia-records', icon: 'file-text' },
      { label: '收藏分组', path: '/app/media-favs-2', icon: 'star' },
    ]},
    { label: '个人自媒体', children: [
      { label: '账号授权', path: '/app/weixin-auth', icon: 'key' },
      { label: '发布任务', path: '/app/weixin-tasks', icon: 'send' },
      { label: '发布记录', path: '/app/weixin-records', icon: 'list' },
    ]},
  ]},
  { title: 'AI官网SEO', items: [
    { label: '站点管理', path: '/app/sites', icon: 'link' },
    { label: 'SEO发布任务', path: '/app/site-tasks', icon: 'send' },
    { label: '发布记录', path: '/app/site-records', icon: 'list' },
  ]},
  { title: 'AI数据中心', items: [
    { label: '数据报表', path: '/app/stats', icon: 'bar-chart' },
    { label: '查询(带截图)', path: '/app/inclusion', icon: 'camera' },
    { label: '查询记录', path: '/app/inclusion-records', icon: 'clock' },
  ]},
  { title: 'AI工具助手', items: [
    { label: '关键词指数', path: '/app/zhishu', icon: 'trending-up' },
    { label: 'AI拓词', path: '/app/zhengliu', icon: 'bulb' },
    { label: '手动拓词工具', path: '/app/tuozhan', icon: 'pen' },
  ]},
  { title: '消耗明细', items: [
    { label: '点数明细', path: '/app/score-log', icon: 'star' },
    { label: '余额明细', path: '/app/money-log', icon: 'wallet' },
  ]},
  { title: '账号', items: [
    { label: '实名认证', path: '/app/kyc', icon: 'checker' },
    { label: '账号权益', path: '/app/entitlements', icon: 'award' },
    { label: '全局代理IP', path: '/app/proxy', icon: 'server' },
    { label: '修改密码', path: '/app/profile', icon: 'lock' },
  ]},
]

const kycFree = ['/app/dashboard', '/app/analyze', '/app/analyze-pro', '/app/analyze-reports', '/app/zhishu', '/app/zhengliu', '/app/tuozhan', '/app/score-log', '/app/money-log', '/app/kyc', '/app/profile', '/app/proxy']
const needKyc = computed(() => {
  if (me.value.kyc_status === 'approved') return false
  const p = route.path
  if (kycFree.some((x) => p === x || p.startsWith('/app/analyze-reports/'))) return false
  return true
})

provide('me', me)
provide('reloadMe', loadMe)

const collapsed = ref({})
function toggle(label) {
  collapsed.value[label] = !collapsed.value[label]
}
function isActive(p) {
  return route.path === p || route.path.startsWith(p + '/')
}
watch(() => route.path, () => {
  for (const g of menus) {
    for (const item of g.items) {
      if (!item.children) continue
      if (item.children.some((c) => isActive(c.path))) collapsed.value[item.label] = false
    }
  }
})

async function loadMe() {
  me.value = await api.get('/api/me')
}

async function logout() {
  await api.post('/api/auth/logout')
  router.push('/login')
}

onMounted(async () => {
  try {
    await loadMe()
    ready.value = true
  } catch {
    router.push('/login')
  }
})
</script>

<style scoped>
.wb { display: flex; min-height: 100vh; }
.side { width: 232px; background: linear-gradient(180deg, #232b4d, #171c30); color: #c6cde8; overflow: auto; flex-shrink: 0; position: sticky; top: 0; height: 100vh; }
.brand { padding: 22px 18px 18px; color: #fff; border-bottom: 1px solid rgba(255,255,255,.07); margin-bottom: 8px; }
.brand-name { font-size: 20px; font-weight: 800; background: linear-gradient(135deg, #b7c7ff, #b49aff, #ffc2e9); -webkit-background-clip: text; background-clip: text; color: transparent; }
.brand-sub { margin-top: 4px; font-size: 12px; color: #8b93b8; letter-spacing: .06em; }
.side .link { display: flex; align-items: center; gap: 9px; padding: 8px 18px 8px 16px; font-size: 13px; color: #aeb6d8; border-radius: 9px; margin: 1px 10px; border-left: 3px solid transparent; transition: all .12s; white-space: nowrap; }
.side .link .ic { width: 16px; height: 16px; flex-shrink: 0; opacity: .85; }
.side .link:hover { color: #fff; background: rgba(255,255,255,.06); }
.side .link:hover .ic { opacity: 1; }
.side .link.on, .side .link.router-link-active { background: linear-gradient(90deg, rgba(95,110,255,.38), rgba(95,110,255,.14)); color: #fff; border-left-color: #9db0ff; box-shadow: inset 0 0 0 1px rgba(130,140,255,.28); }
.side .link.on .ic, .side .link.router-link-active .ic { opacity: 1; }
.sub-title {
  display: flex; align-items: center; justify-content: space-between;
  padding: 9px 16px 9px 20px; margin: 2px 10px 2px; font-size: 12px; color: #8b93b8;
  font-weight: 600; letter-spacing: .04em; cursor: pointer; user-select: none;
  border-radius: 8px; transition: all .12s;
}
.sub-title:hover { color: #d3d9f5; background: rgba(255,255,255,.05); }
.sub-title.collapsed .arr { transform: rotate(0deg); }
.sub-title .arr { font-size: 10px; color: #8b93b8; transition: transform .15s; }
.sub { margin-top: 2px; }
.sub .link { padding: 8px 18px 8px 24px; margin: 1px 10px; }
.sub .link .ic { width: 14px; height: 14px; }
.main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.top {
  height: 60px; background: #fff; display: flex; align-items: center; justify-content: space-between;
  padding: 0 22px; border-bottom: 1px solid var(--border);
  position: sticky; top: 0; z-index: 3;
}
.crumb { font-size: 15px; font-weight: 700; }
.top-right { display: flex; gap: 10px; align-items: center; font-size: 13px; }
.hlink { color: #4b5563; padding: 6px 4px; }
.hlink:hover { color: var(--primary); }
.content { padding: 22px; flex: 1; }
.kyc-tip { text-align: center; padding: 30px; background: #fff; border-radius: var(--radius-lg); box-shadow: var(--shadow-pop); }
.kyc-icon {
  width: 52px; height: 52px; margin: 0 auto 12px; border-radius: 14px;
  background: var(--primary-gradient-2); color: #fff; font-size: 14px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.kyc-title { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
.kyc-desc { font-size: 13px; color: var(--muted); margin-bottom: 16px; }
</style>