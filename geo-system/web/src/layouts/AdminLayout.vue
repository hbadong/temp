<template>
  <div class="wb" v-if="ready">
    <aside class="side">
      <div class="brand">
        <div class="brand-name">GEO 管理端</div>
        <div class="brand-sub">后台管理中心</div>
      </div>
      <nav>
        <router-link class="link" to="/admin/kyc"><AppIcon name="checker" class="ic" /><span class="tx">实名审核</span></router-link>
        <router-link class="link" to="/admin/adjust"><AppIcon name="refresh" class="ic" /><span class="tx">调账</span></router-link>
        <router-link class="link" to="/admin/tasks"><AppIcon name="activity" class="ic" /><span class="tx">任务监控</span></router-link>
        <router-link class="link" to="/admin/settings"><AppIcon name="settings" class="ic" /><span class="tx">标价配置</span></router-link>
      </nav>
    </aside>
    <div class="main">
      <header class="top">
        <div class="crumb">GEO 管理端</div>
        <div class="top-right">
          <span class="chip grad">管理员 {{ admin.username }}</span>
          <a class="hlink" href="javascript:;" @click="logout">退出</a>
        </div>
      </header>
      <div class="content"><router-view /></div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'
const router = useRouter()
const admin = ref({})
const ready = ref(false)
onMounted(async () => {
  try {
    admin.value = await api.get('/api/admin/me')
    ready.value = true
  } catch {
    router.push('/admin/login')
  }
})
async function logout() {
  await api.post('/api/admin/logout')
  router.push('/admin/login')
}
</script>

<style scoped>
.wb { display: flex; min-height: 100vh; }
.side {
  width: 232px; background: linear-gradient(180deg, #232b4d, #171c30);
  color: #c6cde8; flex-shrink: 0; position: sticky; top: 0; height: 100vh;
  display: flex; flex-direction: column;
}
.brand { padding: 22px 18px 18px; color: #fff; border-bottom: 1px solid rgba(255,255,255,.07); margin-bottom: 8px; }
.brand-name { font-size: 20px; font-weight: 800; background: linear-gradient(135deg, #b7c7ff, #b49aff, #ffc2e9); -webkit-background-clip: text; background-clip: text; color: transparent; }
.brand-sub { margin-top: 4px; font-size: 12px; color: #8b93b8; letter-spacing: .06em; }
.side .link {
  display: flex; align-items: center; gap: 9px;
  padding: 8px 18px 8px 16px; font-size: 13px; color: #aeb6d8;
  border-radius: 9px; margin: 1px 10px; border-left: 3px solid transparent;
  transition: all .12s; white-space: nowrap;
}
.side .link .ic { width: 16px; height: 16px; flex-shrink: 0; opacity: .85; }
.side .link:hover { color: #fff; background: rgba(255,255,255,.06); }
.side .link:hover .ic { opacity: 1; }
.side .link.router-link-active { background: linear-gradient(90deg, rgba(95,110,255,.38), rgba(95,110,255,.14)); color: #fff; border-left-color: #9db0ff; box-shadow: inset 0 0 0 1px rgba(130,140,255,.28); }
.side .link.router-link-active .ic { opacity: 1; }
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
</style>