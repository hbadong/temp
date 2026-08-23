<template>
  <div class="card logs-panel">
    <div class="toolbar">
      <h3 class="section__title">采集日志（最近 50 条）</h3>
      <button class="btn btn-primary" @click="runOnce" :disabled="running">
        {{ running ? '采集中...' : '立即采集一轮' }}
      </button>
    </div>
    <p class="tip">内置模拟源按固定间隔自动增量采集；生产环境由 Telethon 采集器回传消息。</p>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>时间</th><th>来源</th><th>拉取</th><th>入库</th><th>重复跳过</th><th>敏感过滤</th></tr>
        </thead>
        <tbody>
          <tr v-for="log in items" :key="log.id">
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

    <div v-if="items.length === 0" class="empty">
      <p class="empty__icon">📋</p>
      <p class="empty__description">暂无日志</p>
    </div>
  </div>
</template>

<script>
import { adminGet, adminPost } from '../../api'

export default {
  name: 'LogsPanel',
  data() {
    return { items: [], running: false }
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      try {
        this.items = (await adminGet('/admin/collect-logs')).items
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async runOnce() {
      this.running = true
      try {
        const r = await adminPost('/admin/collect/run')
        this.$toast(`本轮拉取 ${r.fetched} 条，入库 ${r.inserted} 条`)
        this.load()
      } catch (err) {
        this.$toast(err.message)
      } finally {
        this.running = false
      }
    },
    formatTime(t) {
      return new Date(t).toLocaleString('zh-CN')
    },
  },
}
</script>

<style scoped>
.logs-panel {
  animation: fade-in 0.3s ease-out;
}

@keyframes fade-in {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.section__title {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.tip {
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  margin-bottom: 16px;
}

.table-wrap {
  overflow-x: auto;
  margin: 0 -20px;
  padding: 0 20px;
}

.ok-text {
  color: var(--success);
  font-weight: 500;
}

.danger-text {
  color: var(--danger);
}

@media (max-width: 700px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar .btn {
    width: 100%;
    text-align: center;
  }
}
</style>