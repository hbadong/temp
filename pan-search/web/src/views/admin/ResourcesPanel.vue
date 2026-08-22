<template>
  <div class="card section">
    <div class="toolbar">
      <form class="filter-form" @submit.prevent="load(1)">
        <input v-model="q" type="text" placeholder="搜索标题或链接" />
        <select v-model="cloud">
          <option value="">全部网盘</option>
          <option v-for="(name, key) in cloudNames" :key="key" :value="key">{{ name }}</option>
        </select>
        <select v-model="status">
          <option value="">全部状态</option>
          <option value="ok">正常</option>
          <option value="expired">已失效</option>
          <option value="blocked">已屏蔽</option>
        </select>
        <button class="btn" type="submit">查询</button>
      </form>
      <button class="btn btn-primary" @click="showAdd = true">手动添加资源</button>
    </div>

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
            {{ item.title }}
            <span class="link-text">{{ item.link }}</span>
            <span v-if="item.password" class="pwd">码：{{ item.password }}</span>
          </td>
          <td>{{ cloudNames[item.cloud_type] || item.cloud_type }}</td>
          <td>
            <span :class="['tag', statusClass(item.status)]">{{ statusName(item.status) }}</span>
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
    <div class="empty" v-if="!loading && items.length === 0">暂无数据</div>

    <div class="pager" v-if="totalPages > 1">
      <button class="btn btn-sm" :disabled="page <= 1" @click="load(page - 1)">上一页</button>
      <span>{{ page }} / {{ totalPages }}</span>
      <button class="btn btn-sm" :disabled="page >= totalPages" @click="load(page + 1)">下一页</button>
    </div>

    <teleport to="body">
      <div v-if="showAdd" class="modal-mask" @click.self="showAdd = false">
        <div class="modal">
          <h3>手动添加资源</h3>
          <div class="form-item">
            <label>资源标题 *</label>
            <input v-model="form.title" type="text" />
          </div>
          <div class="form-item">
            <label>网盘链接 *</label>
            <input v-model="form.link" type="text" placeholder="https://pan.quark.cn/s/..." />
          </div>
          <div style="display: flex; gap: 10px">
            <div class="form-item" style="flex: 1">
              <label>提取码</label>
              <input v-model="form.password" type="text" maxlength="4" />
            </div>
            <div class="form-item" style="flex: 1">
              <label>网盘类型</label>
              <select v-model="form.cloud_type">
                <option value="">自动识别</option>
                <option v-for="(name, key) in cloudNames" :key="key" :value="key">{{ name }}</option>
              </select>
            </div>
          </div>
          <div style="display: flex; gap: 10px; justify-content: flex-end">
            <button class="btn" @click="showAdd = false">取消</button>
            <button class="btn btn-primary" @click="add">保存</button>
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
      try {
        await adminPost('/admin/resources', this.form)
        this.$toast('添加成功')
        this.showAdd = false
        this.form = { title: '', link: '', password: '', cloud_type: '' }
        this.load(1)
      } catch (err) {
        this.$toast(err.message)
      }
    },
    statusName(s) {
      return { ok: '正常', expired: '已失效', blocked: '已屏蔽' }[s] || s
    },
    statusClass(s) {
      return { ok: 'tag-ok', expired: 'tag-expired', blocked: 'tag-other' }[s] || ''
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
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.filter-form {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-form input,
.filter-form select {
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 7px 10px;
}

.filter-form input {
  width: 200px;
}

.link-text {
  display: block;
  color: var(--text-3);
  font-size: 12px;
}

.pwd {
  color: var(--warn);
  font-size: 12px;
  margin-left: 8px;
}

.ops {
  white-space: nowrap;
}
</style>
