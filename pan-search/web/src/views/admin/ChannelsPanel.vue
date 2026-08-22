<template>
  <div class="card section">
    <div class="toolbar">
      <h3>TG 频道列表</h3>
      <form class="add-form" @submit.prevent="add">
        <input v-model="username" type="text" placeholder="@channel_username" />
        <input v-model="note" type="text" placeholder="备注（可选）" />
        <button class="btn btn-primary" type="submit">添加频道</button>
      </form>
    </div>
    <p class="tip">
      频道由采集器（tools/tg_importer.py，Telethon）按配置轮询拉取；
      屏蔽后该频道资源不再入库。
    </p>
    <table class="table">
      <thead>
        <tr><th>频道</th><th>备注</th><th>累计采集</th><th>状态</th><th>操作</th></tr>
      </thead>
      <tbody>
        <tr v-for="ch in items" :key="ch.id">
          <td>@{{ ch.username }}</td>
          <td>{{ ch.note || '-' }}</td>
          <td>{{ ch.collected.toLocaleString() }}</td>
          <td>
            <span v-if="ch.blocked" class="tag tag-expired">已屏蔽</span>
            <span v-else-if="ch.enabled" class="tag tag-ok">采集中</span>
            <span v-else class="tag tag-other">已停用</span>
          </td>
          <td class="ops">
            <button class="btn btn-sm" @click="toggle(ch, 'enabled')">
              {{ ch.enabled ? '停用' : '启用' }}
            </button>
            <button class="btn btn-sm" @click="toggle(ch, 'blocked')">
              {{ ch.blocked ? '取消屏蔽' : '屏蔽' }}
            </button>
            <button class="btn btn-sm btn-danger" @click="remove(ch)">删除</button>
          </td>
        </tr>
      </tbody>
    </table>
    <div class="empty" v-if="items.length === 0">暂无频道</div>
  </div>
</template>

<script>
import { adminGet, adminPost, adminPut, adminDelete } from '../../api'

export default {
  name: 'ChannelsPanel',
  data() {
    return { items: [], username: '', note: '' }
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      try {
        this.items = (await adminGet('/admin/channels')).items
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async add() {
      try {
        await adminPost('/admin/channels', { username: this.username, note: this.note })
        this.username = ''
        this.note = ''
        this.load()
        this.$toast('频道已添加')
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async toggle(ch, field) {
      try {
        await adminPut(`/admin/channels/${ch.id}`, { [field]: !ch[field] })
        this.load()
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async remove(ch) {
      if (!confirm(`确认删除频道 @${ch.username}？`)) return
      try {
        await adminDelete(`/admin/channels/${ch.id}`)
        this.load()
      } catch (err) {
        this.$toast(err.message)
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
  gap: 12px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.toolbar h3 {
  font-size: 14px;
  color: var(--text-2);
}

.add-form {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.add-form input {
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 7px 10px;
}

.add-form input:first-child {
  width: 180px;
}

.tip {
  color: var(--text-3);
  font-size: 12px;
  margin-bottom: 14px;
}

.ops {
  white-space: nowrap;
}
</style>
