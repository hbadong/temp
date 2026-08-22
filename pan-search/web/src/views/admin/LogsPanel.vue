<template>
  <div class="card section">
    <div class="toolbar">
      <h3>采集日志（最近 50 条）</h3>
      <button class="btn btn-primary" @click="runOnce" :disabled="running">
        {{ running ? '采集中...' : '立即采集一轮' }}
      </button>
    </div>
    <p class="tip">内置模拟源按固定间隔自动增量采集；生产环境由 Telethon 采集器回传消息。</p>
    <table class="table">
      <thead>
        <tr><th>时间</th><th>来源</th><th>拉取</th><th>入库</th><th>重复跳过</th><th>敏感过滤</th></tr>
      </thead>
      <tbody>
        <tr v-for="log in items" :key="log.id">
          <td>{{ new Date(log.created_at).toLocaleString('zh-CN') }}</td>
          <td>{{ log.source }}</td>
          <td>{{ log.fetched }}</td>
          <td class="ok-text">{{ log.inserted }}</td>
          <td>{{ log.skipped }}</td>
          <td class="danger-text">{{ log.filtered }}</td>
        </tr>
      </tbody>
    </table>
    <div class="empty" v-if="items.length === 0">暂无日志</div>
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
  },
}
</script>

<style scoped>
.section {
  padding: 16px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.toolbar h3 {
  font-size: 14px;
  color: var(--text-2);
}

.tip {
  color: var(--text-3);
  font-size: 12px;
  margin-bottom: 14px;
}

.ok-text {
  color: var(--success);
}

.danger-text {
  color: var(--danger);
}
</style>
