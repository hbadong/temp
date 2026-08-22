<template>
  <div class="card resources-panel">
    <div class="toolbar">
      <form class="filter-form" @submit.prevent="load(1)">
        <input
          v-model="q"
          type="text"
          class="form-item__input"
          placeholder="搜索标题或链接"
          style="width: 240px;"
        />
        <select v-model="cloud" class="form-item__select" style="width: 140px;">
          <option value="">全部网盘</option>
          <option v-for="(name, key) in cloudNames" :key="key" :value="key">{{ name }}</option>
        </select>
        <select v-model="status" class="form-item__select" style="width: 120px;">
          <option value="">全部状态</option>
          <option value="ok">正常</option>
          <option value="expired">已失效</option>
          <option value="blocked">已屏蔽</option>
        </select>
        <button class="btn" type="submit">查询</button>
      </form>
      <button class="btn btn-primary" @click="showAdd = true">手动添加资源</button>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="wrap">标题</th>
            <th>网盘</th>
            <th>状态</th>
            <th>失效举报</th>
            <th>来源</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td class="wrap">
              <div class="title-cell">
                <span class="title-text">{{ item.title }}</span>
                <span class="link-text">{{ item.link }}</span>
                <span v-if="item.password" class="pwd">码：{{ item.password }}</span>
              </div>
            </td>
            <td>{{ cloudNames[item.cloud_type] || item.cloud_type }}</td>
            <td>
              <span :class="['tag', statusTag(item.status)]">{{ statusName(item.status) }}</span>
            </td>
            <td>{{ item.invalid_reports }}</td>
            <td>{{ item.channel || 'manual' }}</td>
            <td class="ops">
              <button v-if="item.status !== 'blocked'" class="btn btn-sm" @click="setStatus(item, 'blocked')">屏蔽</button>
              <button v-if="item.status === 'blocked'" class="btn btn-sm" @click="setStatus(item, 'ok')">恢复</button>
              <button v-if="item.status === 'ok'" class="btn btn-sm" @click="setStatus(item, 'expired')">标失效</button>
              <button class="btn btn-sm btn-danger" @click="remove(item)">删除</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading && items.length === 0" class="empty">
      <p class="empty__icon">📦</p>
      <p class="empty__description">暂无数据</p>
    </div>

    <div class="pager" v-if="totalPages > 1">
      <button class="btn btn-sm" :disabled="page <= 1" @click="load(page - 1)">上一页</button>
      <span class="page-info">{{ page }} / {{ totalPages }}</span>
      <button class="btn btn-sm" :disabled="page >= totalPages" @click="load(page + 1)">下一页</button>
    </div>

    <teleport to="body">
      <div v-if="showAdd" class="dialog-mask" @click.self="showAdd = false">
        <div class="dialog">
          <div class="dialog__header">
            <span class="dialog__title">手动添加资源</span>
            <span class="dialog__close" @click="showAdd = false">&times;</span>
          </div>
          <div class="dialog__body">
            <form @submit.prevent="add" class="form">
              <div class="form-item">
                <label class="form-item__label">资源标题 *</label>
                <div class="form-item__content">
                  <input v-model="form.title" type="text" class="form-item__input" required />
                </div>
              </div>
              <div class="form-item">
                <label class="form-item__label">网盘链接 *</label>
                <div class="form-item__content">
                  <input
                    v-model="form.link"
                    type="text"
                    class="form-item__input"
                    placeholder="https://pan.quark.cn/s/..."
                    required
                  />
                </div>
              </div>
              <div class="form-row">
                <div class="form-item" style="flex: 1">
                  <label class="form-item__label">提取码</label>
                  <div class="form-item__content">
                    <input v-model="form.password" type="text" class="form-item__input" maxlength="4" />
                  </div>
                </div>
                <div class="form-item" style="flex: 1">
                  <label class="form-item__label">网盘类型</label>
                  <div class="form-item__content">
                    <select v-model="form.cloud_type" class="form-item__select">
                      <option value="">自动识别</option>
                      <option v-for="(name, key) in cloudNames" :key="key" :value="key">{{ name }}</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="dialog__footer">
                <button class="btn" type="button" @click="showAdd = false">取消</button>
                <button class="btn btn-primary" type="submit" :disabled="submitting">
                  {{ submitting ? '保存中...' : '保存' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script>
import { adminGet, adminPost, adminPut, adminDelete } from '../../api'
import { CLOUD_NAMES } from '../../constants'

export default {
  name: 'ResourcesPanel',
  data() {
    return {
      items: [],
      q: '',
      cloud: '',
      status: '',
      page: 1,
      total: 0,
      size: 20,
      loading: false,
      showAdd: false,
      submitting: false,
      form: { title: '', link: '', password: '', cloud_type: '' },
      cloudNames: CLOUD_NAMES,
    }
  },
  computed: {
    totalPages() {
      return Math.max(1, Math.ceil(this.total / this.size))
    },
  },
  mounted() {
    this.load(1)
  },
  methods: {
    async load(page) {
      this.loading = true
      try {
        const data = await adminGet(
          `/admin/resources?page=${page}&size=${this.size}` +
            `&q=${encodeURIComponent(this.q)}&cloud=${this.cloud}&status=${this.status}`
        )
        this.items = data.items
        this.total = data.total
        this.page = data.page
      } catch (err) {
        this.$toast(err.message)
      } finally {
        this.loading = false
      }
    },
    async setStatus(item, status) {
      try {
        await adminPut(`/admin/resources/${item.id}`, { status })
        item.status = status
        this.$toast('已更新')
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async remove(item) {
      if (!confirm(`确认删除「${item.title}」？`)) return
      try {
        await adminDelete(`/admin/resources/${item.id}`)
        this.load(this.page)
        this.$toast('已删除')
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async add() {
      if (!this.form.title || !this.form.link) {
        this.$toast('请填写标题和链接')
        return
      }
      this.submitting = true
      try {
        await adminPost('/admin/resources', this.form)
        this.$toast('添加成功')
        this.showAdd = false
        this.form = { title: '', link: '', password: '', cloud_type: '' }
        this.submitting = false
        this.load(1)
      } catch (err) {
        this.$toast(err.message)
        this.submitting = false
      }
    },
    statusName(s) {
      return { ok: '正常', expired: '已失效', blocked: '已屏蔽' }[s] || s
    },
    statusTag(s) {
      return { ok: 'tag--success', expired: 'tag--danger', blocked: 'tag--info' }[s] || ''
    },
  },
}
</script>

<style scoped>
.resources-panel {
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

.filter-form {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.table-wrap {
  overflow-x: auto;
  margin: 0 -20px;
  padding: 0 20px;
}

.title-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.title-text {
  font-weight: 500;
  color: var(--text-primary);
  word-break: break-all;
}

.link-text {
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  word-break: break-all;
}

.pwd {
  color: var(--warning);
  font-size: var(--font-size-sm);
}

.ops {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  white-space: nowrap;
}

.form-row {
  display: flex;
  gap: 16px;
}

@media (max-width: 767px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-form {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-form input,
  .filter-form select {
    width: 100%;
  }

  .ops {
    flex-direction: column;
  }

  .ops .btn {
    width: 100%;
    text-align: center;
  }

  .form-row {
    flex-direction: column;
    gap: 0;
  }
}
</style>