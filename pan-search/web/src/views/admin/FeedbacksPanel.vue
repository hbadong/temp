<template>
  <div class="card feedbacks-panel">
    <div class="toolbar">
      <h3 class="section__title">用户反馈 / 投诉</h3>
      <select v-model="status" @change="load" class="form-item__select" style="width: 140px;">
        <option value="">全部</option>
        <option value="pending">待处理</option>
        <option value="resolved">已处理</option>
        <option value="rejected">已驳回</option>
      </select>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>时间</th>
            <th>类型</th>
            <th class="wrap">内容</th>
            <th class="wrap">关联资源</th>
            <th>联系方式</th>
            <th>状态</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="f in items" :key="f.id">
            <td>{{ formatTime(f.created_at) }}</td>
            <td>
              <span :class="['tag', typeTag(f.type)]">{{ typeName(f.type) }}</span>
            </td>
            <td class="wrap">{{ f.content || '-' }}</td>
            <td class="wrap">{{ f.resource_title || '-' }}</td>
            <td>{{ f.contact || '-' }}</td>
            <td>
              <span :class="['tag', statusTag(f.status)]">{{ statusName(f.status) }}</span>
            </td>
            <td class="ops" v-if="f.status === 'pending'">
              <button class="btn btn-sm" @click="setStatus(f, 'resolved')">已处理</button>
              <button class="btn btn-sm" @click="setStatus(f, 'rejected')">驳回</button>
            </td>
            <td v-else>-</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="items.length === 0" class="empty">
      <p class="empty__icon">📝</p>
      <p class="empty__description">暂无反馈</p>
    </div>
  </div>
</template>

<script>
import { adminGet, adminPut } from '../../api'

export default {
  name: 'FeedbacksPanel',
  data() {
    return { items: [], status: '' }
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      try {
        this.items = (await adminGet(`/admin/feedbacks?status=${this.status}`)).items
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async setStatus(f, status) {
      try {
        await adminPut(`/admin/feedbacks/${f.id}`, { status })
        this.load()
      } catch (err) {
        this.$toast(err.message)
      }
    },
    typeName(t) {
      return { invalid: '失效反馈', infringe: '侵权投诉', other: '其他' }[t] || t
    },
    typeTag(t) {
      return { invalid: 'tag--danger', infringe: 'tag--warning', other: 'tag--info' }[t] || ''
    },
    statusName(s) {
      return { pending: '待处理', resolved: '已处理', rejected: '已驳回' }[s] || s
    },
    statusTag(s) {
      return { pending: 'tag--warning', resolved: 'tag--success', rejected: 'tag--info' }[s] || ''
    },
    formatTime(t) {
      return new Date(t).toLocaleString('zh-CN')
    },
  },
}
</script>

<style scoped>
.feedbacks-panel {
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
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.section__title {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.table-wrap {
  overflow-x: auto;
  margin: 0 -20px;
  padding: 0 20px;
}

.ops {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  white-space: nowrap;
}

@media (max-width: 767px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar select {
    width: 100%;
  }

  .ops {
    flex-direction: column;
  }

  .ops .btn {
    width: 100%;
    text-align: center;
  }
}
</style>