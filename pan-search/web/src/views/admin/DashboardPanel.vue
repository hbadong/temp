<template>
  <div v-if="data">
    <div class="stat-grid">
      <div class="card stat">
        <span class="num">{{ data.total.toLocaleString() }}</span>
        <span class="label">资源总量</span>
      </div>
      <div class="card stat">
        <span class="num">{{ data.today_new.toLocaleString() }}</span>
        <span class="label">今日新增</span>
      </div>
      <div class="card stat">
        <span class="num">{{ data.search_count_24h.toLocaleString() }}</span>
        <span class="label">24h 搜索量</span>
      </div>
      <div class="card stat">
        <span class="num warn">{{ data.expired.toLocaleString() }}</span>
        <span class="label">失效资源</span>
      </div>
      <div class="card stat">
        <span class="num warn">{{ data.blocked.toLocaleString() }}</span>
        <span class="label">已屏蔽</span>
      </div>
      <div class="card stat">
        <span class="num danger">{{ data.pending_feedbacks.toLocaleString() }}</span>
        <span class="label">待处理反馈</span>
      </div>
    </div>

    <div class="two-col">
      <div class="card section">
        <h3>网盘类型分布</h3>
        <div v-for="c in data.by_cloud" :key="c.cloud_type" class="bar-row">
          <span class="bar-label">{{ cloudName(c.cloud_type) }}</span>
          <div class="bar-track">
            <div class="bar" :style="{ width: barWidth(c.c) }"></div>
          </div>
          <span class="bar-num">{{ c.c.toLocaleString() }}</span>
        </div>
      </div>

      <div class="card section">
        <h3>频道采集量 TOP</h3>
        <table class="table">
          <thead>
            <tr><th>频道</th><th>采集量</th><th>状态</th></tr>
          </thead>
          <tbody>
            <tr v-for="ch in data.channels" :key="ch.username">
              <td>@{{ ch.username }}</td>
              <td>{{ ch.collected.toLocaleString() }}</td>
              <td>
                <span v-if="ch.blocked" class="tag tag-expired">已屏蔽</span>
                <span v-else-if="ch.enabled" class="tag tag-ok">采集中</span>
                <span v-else class="tag tag-other">已停用</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card section">
      <h3>最近采集记录</h3>
      <table class="table">
        <thead>
          <tr><th>时间</th><th>来源</th><th>拉取</th><th>入库</th><th>重复</th><th>过滤</th></tr>
        </thead>
        <tbody>
          <tr v-for="log in data.recent_logs" :key="log.id">
            <td>{{ new Date(log.created_at).toLocaleString('zh-CN') }}</td>
            <td>{{ log.source }}</td>
            <td>{{ log.fetched }}</td>
            <td class="ok-text">{{ log.inserted }}</td>
            <td>{{ log.skipped }}</td>
            <td class="danger-text">{{ log.filtered }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { adminGet } from '../../api'
import { CLOUD_NAMES } from '../../constants'

export default {
  name: 'DashboardPanel',
  data() {
    return { data: null }
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      try {
        this.data = await adminGet('/admin/dashboard')
      } catch (err) {
        this.$toast(err.message)
      }
    },
    cloudName(t) {
      return CLOUD_NAMES[t] || t
    },
    barWidth(c) {
      const max = Math.max(...this.data.by_cloud.map((x) => x.c), 1)
      return `${Math.max(4, Math.round((c / max) * 100))}%`
    },
  },
}
</script>

<style scoped>
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.stat {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.num {
  font-size: 24px;
  font-weight: 700;
  color: var(--primary);
}

.num.warn {
  color: var(--warn);
}

.num.danger {
  color: var(--danger);
}

.label {
  color: var(--text-3);
  font-size: 12px;
}

.two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
}

@media (max-width: 760px) {
  .two-col {
    grid-template-columns: 1fr;
  }
}

.section {
  padding: 16px;
}

.section h3 {
  font-size: 14px;
  margin-bottom: 12px;
  color: var(--text-2);
}

.bar-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  font-size: 13px;
}

.bar-label {
  width: 70px;
  color: var(--text-2);
  flex-shrink: 0;
}

.bar-track {
  flex: 1;
  height: 10px;
  background: var(--bg);
  border-radius: 5px;
  overflow: hidden;
}

.bar {
  height: 100%;
  background: linear-gradient(90deg, var(--primary), #7a5cff);
  border-radius: 5px;
}

.bar-num {
  width: 60px;
  text-align: right;
  color: var(--text-2);
}

.ok-text {
  color: var(--success);
}

.danger-text {
  color: var(--danger);
}
</style>
