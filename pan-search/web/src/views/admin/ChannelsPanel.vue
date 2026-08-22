<template>
  <div class="card channels-panel">
    <div class="toolbar">
      <h3 class="section__title">TG 频道列表</h3>
      <form class="add-form" @submit.prevent="add">
        <input
          v-model="username"
          type="text"
          class="form-item__input"
          placeholder="@channel_username"
          style="width: 180px;"
          required
        />
        <input
          v-model="note"
          type="text"
          class="form-item__input"
          placeholder="备注（可选）"
          style="width: 200px;"
        />
        <button class="btn btn-primary" type="submit">添加频道</button>
      </form>
    </div>
    <p class="tip">
      频道由采集器（tools/tg_importer.py，Telethon）按配置轮询拉取；
      屏蔽后该频道资源不再入库。
    </p>

    <div class="table-wrap">
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
              <span v-if="ch.blocked" class="tag tag--danger">已屏蔽</span>
              <span v-else-if="ch.enabled" class="tag tag--success">采集中</span>
              <span v-else class="tag tag--info">已停用</span>
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
    </div>

    <div v-if="items.length === 0" class="empty">
      <p class="empty__icon">📢</p>
      <p class="empty__description">暂无频道</p>
    </div>
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
.channels-panel {
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

.add-form {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
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

  .add-form {
    flex-direction: column;
    align-items: stretch;
  }

  .add-form input {
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