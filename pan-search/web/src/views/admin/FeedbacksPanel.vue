<template>
  <div class="card section">
    <div class="toolbar">
      <h3>用户反馈 / 投诉</h3>
      <select v-model="status" @change="load">
        <option value="">全部</option>
        <option value="pending">待处理</option>
        <option value="resolved">已处理</option>
        <option value="rejected">已驳回</option>
      </select>
    </div>
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
          <td>{{ new Date(f.created_at).toLocaleString('zh-CN') }}</td>
          <td>
            <span :class="['tag', f.type === 'invalid' ? 'tag-expired' : 'tag-video']">
              {{ typeName(f.type) }}
            </span>
          </td>
          <td class="wrap">{{ f.content || '-' }}</td>
          <td class="wrap">
            <template v-if="f.resource_title">{{ f.resource_title }}</template>
            <template v-else>-</template>
          </td>
          <td>{{ f.contact || '-' }}</td>
          <td>{{ statusName(f.status) }}</td>
          <td class="ops" v-if="f.status === 'pending'">
            <button class="btn btn-sm" @click="setStatus(f, 'resolved')">已处理</button>
            <button class="btn btn-sm" @click="setStatus(f, 'rejected')">驳回</button>
          </td>
          <td v-else>-</td>
        </tr>
      </tbody>
    </table>
    <div class="empty" v-if="items.length === 0">暂无反馈</div>
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
    statusName(s) {
      return { pending: '待处理', resolved: '已处理', rejected: '已驳回' }[s] || s
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
  margin-bottom: 12px;
}

.toolbar h3 {
  font-size: 14px;
  color: var(--text-2);
}

.toolbar select {
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 6px 10px;
}

.ops {
  white-space: nowrap;
}
</style>
