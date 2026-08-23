<template>
  <div class="card submissions-panel">
    <div class="toolbar">
      <h3 class="section__title">用户提交资源审核</h3>
      <select v-model="status" @change="load" class="form-item__select" style="width: 140px;">
        <option value="pending">待审核</option>
        <option value="approved">已通过</option>
        <option value="rejected">已驳回</option>
      </select>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>时间</th>
            <th class="wrap">标题</th>
            <th class="wrap">链接</th>
            <th>提取码</th>
            <th>网盘</th>
            <th>类型</th>
            <th>大小</th>
            <th>提交者</th>
            <th>状态</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in items" :key="s.id">
            <td>{{ formatTime(s.created_at) }}</td>
            <td class="wrap">{{ s.title }}</td>
            <td class="wrap"><a :href="s.link" target="_blank" rel="noopener" class="link">{{ s.link }}</a></td>
            <td>{{ s.password || '-' }}</td>
            <td>{{ cloudName(s.cloud_type) }}</td>
            <td>{{ resTypeName(s.res_type) }}</td>
            <td>{{ s.size_text || '-' }}</td>
            <td>{{ s.submitter || '-' }}</td>
            <td>
              <span :class="['tag', statusTag(s.status)]">{{ statusName(s.status) }}</span>
            </td>
            <td class="ops" v-if="s.status === 'pending'">
              <button class="btn btn-sm btn-primary" @click="approve(s)">通过</button>
              <button class="btn btn-sm" @click="reject(s)">驳回</button>
            </td>
            <td v-else>-</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="items.length === 0" class="empty">
      <p class="empty__description">暂无提交记录</p>
    </div>
  </div>
</template>

<script>
import { adminGet, adminPost } from '../../api'
import { CLOUD_NAMES, RES_TYPE_NAMES } from '../../constants'

export default {
  name: 'SubmissionsPanel',
  data() {
    return { items: [], status: 'pending' }
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      try {
        const res = await adminGet(`/admin/submissions?status=${this.status}`)
        this.items = res.items
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async approve(s) {
      try {
        await adminPost(`/admin/submissions/${s.id}/approve`)
        this.$toast('已通过并入库')
        this.load()
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async reject(s) {
      try {
        await adminPost(`/admin/submissions/${s.id}/reject`, {})
        this.$toast('已驳回')
        this.load()
      } catch (err) {
        this.$toast(err.message)
      }
    },
    cloudName(t) {
      return CLOUD_NAMES[t] || t
    },
    resTypeName(t) {
      return RES_TYPE_NAMES[t] || t
    },
    statusName(s) {
      return { pending: '待审核', approved: '已通过', rejected: '已驳回' }[s] || s
    },
    statusTag(s) {
      return { pending: 'tag--warning', approved: 'tag--success', rejected: 'tag--info' }[s] || ''
    },
    formatTime(t) {
      return new Date(t).toLocaleString('zh-CN')
    },
  },
}
</script>

<style scoped>
.submissions-panel {
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

.link {
  color: var(--primary);
  text-decoration: none;
  word-break: break-all;
}

.link:hover {
  text-decoration: underline;
}

.ops {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  white-space: nowrap;
}

@media (max-width: 700px) {
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
