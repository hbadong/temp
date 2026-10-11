<template>
  <div>
    <div class="hero">
      <div class="hero-text">
        <h2>你好，{{ me.username || '用户' }}</h2>
        <p>欢迎使用站长之家 GEO · AI 可见度优化平台，借助 AI 之力提升品牌在各 AI 大模型中的可见度。</p>
      </div>
      <div class="hero-side">
        <span class="hero-chip" v-if="me.kyc_status === 'approved'">已实名认证</span>
        <router-link v-else class="hero-btn" to="/app/kyc">去实名认证</router-link>
        <span class="hero-chip" style="background:rgba(255,255,255,.2)">余额 {{ (me.balance / 100).toFixed(2) }} 元 · {{ me.score }} 点</span>
      </div>
    </div>

    <h2 class="page-title" style="margin-top:20px">数据概况</h2>
    <div class="grid" style="grid-template-columns:repeat(4,1fr)">
      <div class="card stat" v-for="s in stats" :key="s.k" :style="s.accent ? { '--accent': s.accent } : {}">
        <span class="stat-label">{{ s.k }}</span>
        <span class="stat-value">{{ s.v }}</span>
      </div>
    </div>
    <div class="row" style="margin-top:16px">
      <div class="card" style="flex:1;height:280px">
        <h3 style="font-size:15px">近5日创作量</h3>
        <div style="height:230px"><canvas ref="c1"></canvas></div>
      </div>
      <div class="card" style="flex:1;height:280px">
        <h3 style="font-size:15px">近5日发布量</h3>
        <div style="height:230px"><canvas ref="c2"></canvas></div>
      </div>
    </div>
    <div class="card" style="margin-top:16px">
      <h3 style="font-size:15px;margin-bottom:14px">操作流程</h3>
      <div class="steps">
        <template v-for="(s, i) in steps" :key="s.t">
          <router-link class="step" :to="s.to">
            <span class="step-no">{{ i + 1 }}</span>{{ s.t }}
          </router-link>
          <span v-if="i < steps.length - 1" class="step-arrow">→</span>
        </template>
      </div>
    </div>
    <div class="card" style="margin-top:16px">
      <h3 style="font-size:15px;margin-bottom:14px">快捷入口</h3>
      <div class="grid" style="grid-template-columns:repeat(4,1fr)">
        <router-link class="quick" v-for="s in quickLinks" :key="s.to" :to="s.to">{{ s.t }}</router-link>
      </div>
    </div>
  </div>
</template>
<script setup>
import { inject, onMounted, ref } from 'vue'
import { api } from '../../api'
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js'
Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend)
const me = inject('me', ref({}))
const data = ref({})
const c1 = ref()
const c2 = ref()
const stats = ref([])
const steps = [
  { t: '创建关键词', to: '/app/keywords' },
  { t: '上传图片', to: '/app/images' },
  { t: '创建提示词', to: '/app/instructions' },
  { t: '创建写作任务', to: '/app/write-tasks' },
  { t: '授权账号', to: '/app/weixin-auth' },
  { t: '创建发布任务', to: '/app/b2b-tasks' },
  { t: '查看报表', to: '/app/stats' },
]
const quickLinks = [
  { t: '数据报表', to: '/app/stats' },
  { t: '拓展问题', to: '/app/zhengliu' },
  { t: '大模型自动创作', to: '/app/write-tasks' },
  { t: '发布任务', to: '/app/b2b-tasks' },
]
const accentColors = ['#4f6bff', '#7b61ff', '#18c8b6', '#f59e0b']
const barOpts = { responsive: true, maintainAspectRatio: false, borderRadius: 6, maxBarThickness: 24 }
onMounted(async () => {
  data.value = await api.get('/api/dashboard')
  const d = data.value
  stats.value = [
    { k: '余额', v: (d.balance / 100).toFixed(2) + ' 元', accent: '#4f6bff' },
    { k: '点数', v: d.score, accent: '#7b61ff' },
    { k: '关键词', v: d.keywords, accent: '#18c8b6' },
    { k: '文章标题', v: d.titles, accent: '#f59e0b' },
    { k: '写作数量', v: d.articles, accent: '#4f6bff' },
    { k: '文章发布', v: d.publishes, accent: '#7b61ff' },
    { k: '授权账号', v: d.auths, accent: '#18c8b6' },
    { k: '收录', v: d.inclusions, accent: '#f59e0b' },
  ]
  new Chart(c1.value, { type: 'bar', data: { labels: d.days, datasets: [{ label: '创作量', data: d.write_series, backgroundColor: '#4f6bff', borderRadius: 6, maxBarThickness: 24 }] }, options: barOpts })
  new Chart(c2.value, { type: 'bar', data: { labels: d.days, datasets: [{ label: '发布量', data: d.pub_series, backgroundColor: '#10b981', borderRadius: 6, maxBarThickness: 24 }] }, options: barOpts })
})
</script>
<style scoped>
.hero {
  display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
  padding: 26px 28px; border-radius: var(--radius-lg); color: #fff;
  background: linear-gradient(135deg, #5b8cff, #6e72ff 55%, #9b59f2);
  box-shadow: 0 10px 28px rgba(90, 100, 255, .3);
  position: relative; overflow: hidden;
}
.hero::after {
  content: ""; position: absolute; right: -60px; top: -60px; width: 200px; height: 200px; border-radius: 50%;
  background: rgba(255,255,255,.08);
}
.hero::before {
  content: ""; position: absolute; right: 30px; bottom: -90px; width: 160px; height: 160px; border-radius: 50%;
  background: rgba(67,210,247,.18);
}
.hero-text h2 { margin: 0 0 6px; font-size: 22px; font-weight: 700; }
.hero-text p { margin: 0; font-size: 13px; opacity: .92; }
.hero-side { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; position: relative; z-index: 1; }
.hero-btn {
  display: inline-flex; align-items: center; height: 34px; padding: 0 16px;
  border-radius: 999px; background: #fff; color: #6e72ff; font-size: 13px; font-weight: 700;
  box-shadow: 0 4px 12px rgba(0,0,0,.12); transition: transform .12s;
}
.hero-btn:hover { transform: translateY(-1px); }
.hero-chip { display: inline-flex; align-items: center; height: 30px; padding: 0 12px; border-radius: 999px; background: rgba(255,255,255,.15); font-size: 12px; font-weight: 600; }
.quick {
  display: flex; align-items: center; justify-content: center; height: 56px;
  border-radius: 12px; background: #f5f7ff; border: 1px solid #e6e9ff;
  font-size: 14px; font-weight: 600; color: #4b5563; transition: all .12s;
}
.quick:hover { background: var(--primary-gradient-2); color: #fff; box-shadow: 0 6px 18px rgba(95,110,255,.25); transform: translateY(-1px); }
</style>