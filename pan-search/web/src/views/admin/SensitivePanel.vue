<template>
  <div class="card section">
    <div class="toolbar">
      <h3>敏感词库（{{ items.length }}）</h3>
      <form class="add-form" @submit.prevent="add">
        <input v-model="word" type="text" placeholder="输入敏感词" />
        <button class="btn btn-primary" type="submit">添加</button>
      </form>
    </div>
    <p class="tip">命中敏感词的资源将在采集入库时被自动过滤。</p>
    <div class="words">
      <span v-for="w in items" :key="w.id" class="word card">
        {{ w.word }}
        <button class="del" @click="remove(w)">×</button>
      </span>
    </div>
    <div class="empty" v-if="items.length === 0">暂无敏感词</div>
  </div>
</template>

<script>
import { adminGet, adminPost, adminDelete } from '../../api'

export default {
  name: 'SensitivePanel',
  data() {
    return { items: [], word: '' }
  },
  mounted() {
    this.load()
  },
  methods: {
    async load() {
      try {
        this.items = (await adminGet('/admin/sensitive')).items
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async add() {
      try {
        await adminPost('/admin/sensitive', { word: this.word })
        this.word = ''
        this.load()
        this.$toast('已添加')
      } catch (err) {
        this.$toast(err.message)
      }
    },
    async remove(w) {
      try {
        await adminDelete(`/admin/sensitive/${w.id}`)
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
}

.add-form input {
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 7px 10px;
  width: 200px;
}

.tip {
  color: var(--text-3);
  font-size: 12px;
  margin-bottom: 14px;
}

.words {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.word {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  font-size: 13px;
}

.del {
  border: none;
  background: none;
  color: var(--text-3);
  font-size: 15px;
  line-height: 1;
}

.del:hover {
  color: var(--danger);
}
</style>
