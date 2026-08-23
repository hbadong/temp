<template>
  <div class="card sensitive-panel">
    <div class="toolbar">
      <h3 class="section__title">敏感词库（{{ items.length }}）</h3>
      <form class="add-form" @submit.prevent="add">
        <input
          v-model="word"
          type="text"
          class="form-item__input"
          placeholder="输入敏感词"
          style="width: 240px;"
          required
        />
        <button class="btn btn-primary" type="submit">添加</button>
      </form>
    </div>
    <p class="tip">命中敏感词的资源将在采集入库时被自动过滤。</p>

    <div class="words">
      <span v-for="w in items" :key="w.id" class="word">
        {{ w.word }}
        <button class="del" @click="remove(w)">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
      </span>
    </div>

    <div v-if="items.length === 0" class="empty">
      <p class="empty__icon">🔒</p>
      <p class="empty__description">暂无敏感词</p>
    </div>
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
.sensitive-panel {
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
}

.tip {
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  margin-bottom: 16px;
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
  font-size: var(--font-size-sm);
  background: var(--bg-light);
  border-radius: var(--radius-base);
  color: var(--text-regular);
}

.del {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border: none;
  background: transparent;
  color: var(--text-secondary);
  border-radius: var(--radius-base);
  transition: all var(--transition-fast);
  line-height: 1;
}

.del:hover {
  background: var(--danger);
  color: #fff;
}

@media (max-width: 700px) {
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .add-form input {
    width: 100%;
  }
}
</style>