<template>
  <div v-if="data" class="dashboard">
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
        <h3 class="section__title">网盘类型分布</h3>
        <div v-for="c in data.by_cloud" :key="c.cloud_type" class="bar-row">
          <span class="bar-label">{{ cloudName(c.cloud_type) }}</span>
          <div class="bar-track">
            <div class="bar" :style="{ width: barWidth(c.c) }"></div>
          </div>
          <span class="bar-num">{{ c.c.toLocaleString() }}</span>
        </div>
      </div>

      <div class="card section">
        <h3 class="section__title">频道采集量 TOP</h3>
        <table class="table">
          <thead>
            <tr><th>频道</th><th>采集量</th><th>状态</th></tr>
          </thead>
          <tbody>
            <tr v-for="ch in data.channels" :key="ch.username">
              <td>@{{ ch.username }}</td>
              <td>{{ ch.collected.toLocaleString() }}</td>
              <td>
                <span v-if="ch.blocked" class="tag tag--danger">已屏蔽</span>
                <span v-else-if="ch.enabled" class="tag tag--success">采集中</span>
                <span v-else class="tag tag--info">已停用</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card section">
      <h3 class="section__title">最近采集记录</h3>
      <table class="table">
        <thead>
          <tr><th>时间</th><th>来源</th><th>拉取</th><th>入库</th><th>重复</th><th>过滤</th></tr>
        </thead>
        <tbody>
          <tr v-for="log in data.recent_logs" :key="log.id">
            <td>{{ formatTime(log.created_at) }}</td>
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

  <div v-else class="loading-container">
    <div class="loading"></div>
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
    formatTime(t) {
      return new Date(t).toLocaleString('zh-CN')
    },
  },
}
</script>

<style scoped>
.dashboard {
  animation: fade-in 0.3s ease-out;
}

@keyframes fade-in {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.stat {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.num {
  font-size: 28px;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.2;
}

.num.warn {
  color: var(--warning);
}

.num.danger {
  color: var(--danger);
}

.label {
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
}

.two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 20px;
}

@media (max-width: 992px) {
  .two-col {
    grid-template-columns: 1fr;
  }
}

.section {
  padding: 20px;
}

.section__title {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 16px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border-divider);
}

.bar-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  font-size: var(--font-size-sm);
}

.bar-label {
  width: 80px;
  color: var(--text-regular);
  flex-shrink: 0;
}

.bar-track {
  flex: 1;
  height: 8px;
  background: var(--bg-light);
  border-radius: 4px;
  overflow: hidden;
}

.bar {
  height: 100%;
  background: linear-gradient(90deg, var(--primary), var(--primary-light));
  border-radius: 4px;
  transition: width 0.3s ease-out;
}

.bar-num {
  width: 70px;
  text-align: right;
  color: var(--text-secondary);
  font-variant-numeric: tabular-nums;
}

.ok-text {
  color: var(--success);
  font-weight: 500;
}

.danger-text {
  color: var(--danger);
}

.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
}

@media (max-width: 767px) {
  .stat-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .stat {
    padding: 16px;
  }

  .num {
    font-size: 22px;
  }
}
</style>