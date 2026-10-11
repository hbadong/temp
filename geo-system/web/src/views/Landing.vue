<template>
  <div class="land">
    <header>
      <strong>站长之家GEO</strong>
      <router-link class="btn" to="/login">企业登录</router-link>
    </header>
    <section class="hero">
      <h1>AI可见度优化</h1>
      <p>借助AI之力　轻松解决难题　畅享智慧之旅</p>
      <router-link class="btn" to="/login">立即体验</router-link>
    </section>
    <section>
      <h2>主流AI模型优化</h2>
      <div class="plats">
        <div v-for="p in platforms" :key="p" class="card">{{ p }}</div>
      </div>
    </section>
    <section>
      <h2>系统核心优势</h2>
      <div class="grid" style="grid-template-columns:repeat(3,1fr)">
        <div v-for="a in advantages" :key="a.t" class="card">
          <h3>{{ a.t }}</h3>
          <p class="muted">{{ a.d }}</p>
        </div>
      </div>
    </section>
  </div>
</template>
<script setup>
const platforms = ['Deepseek', '豆包', '元宝', '通义千问', '文心一言', '智谱清言']
const advantages = [
  { t: 'AI蒸馏', d: '基于企业关键词，自动蒸馏拓展问题' },
  { t: '排名提升', d: '专业优化算法，快速提升搜索可见度' },
  { t: '稳定高效', d: '自动化优化流程，降低人工成本' },
  { t: '数据监控', d: '可视化报表，精准掌握优化效果' },
  { t: '流量增长', d: '基于GEO优化精准获客' },
  { t: '持续迭代', d: '算法持续更新，长期保持效果' },
]
</script>
<style scoped>
.land { max-width: 1080px; margin: 0 auto; padding: 20px; }
header { display: flex; justify-content: space-between; align-items: center; }
.hero { text-align: center; padding: 70px 0; background: linear-gradient(168deg, #efefff, #c9e0ff, #719aff); border-radius: 24px; margin: 20px 0; color: #1e1b4b; }
.plats { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
h2 { margin: 28px 0 12px; }
</style>
