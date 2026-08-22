<template>
  <form class="search-box" :class="{ big }" @submit.prevent="onSearch">
    <div class="input-wrap" :class="{ focused }">
      <svg class="icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="7" />
        <path d="m20 20-3.5-3.5" />
      </svg>
      <input
        v-model="keyword"
        type="text"
        :placeholder="placeholder"
        enterkeyhint="search"
        @focus="focused = true"
        @blur="focused = false"
      />
      <button v-if="keyword" type="button" class="clear" @click="keyword = ''">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18" />
          <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>
    </div>
    <button type="submit" class="go">搜索</button>
  </form>
</template>

<script>
export default {
  name: 'SearchBox',
  props: {
    modelValue: { type: String, default: '' },
    big: { type: Boolean, default: false },
    placeholder: { type: String, default: '输入资源名称，如：三体、Photoshop、4K 电影' },
  },
  emits: ['update:modelValue', 'search'],
  data() {
    return { keyword: this.modelValue, focused: false }
  },
  watch: {
    modelValue(v) {
      this.keyword = v
    },
    keyword(v) {
      this.$emit('update:modelValue', v)
    },
  },
  methods: {
    onSearch() {
      const q = this.keyword.trim()
      if (!q) return
      this.$emit('search', q)
    },
  },
}
</script>

<style scoped>
.search-box {
  display: flex;
  gap: 10px;
  width: 100%;
}

.input-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  background: var(--bg-color);
  border: 1px solid var(--border-base);
  border-radius: var(--radius-base);
  padding: 0 12px;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
  height: 36px;
}

.search-box.big .input-wrap {
  height: 48px;
  border-radius: var(--radius-base);
  box-shadow: var(--shadow-light);
}

.input-wrap.focused,
.input-wrap:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(180, 106, 255, 0.2);
}

.icon {
  color: var(--text-secondary);
  flex-shrink: 0;
  margin-right: 8px;
}

.input-wrap input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: var(--font-size-base);
  color: var(--text-primary);
  height: 100%;
  min-width: 0;
}

.input-wrap input::placeholder {
  color: var(--text-placeholder);
}

.search-box.big .input-wrap input {
  font-size: var(--font-size-lg);
  padding: 4px 0;
}

.clear {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border: none;
  background: var(--border-lighter);
  color: var(--text-secondary);
  border-radius: var(--radius-circle);
  flex-shrink: 0;
  transition: all var(--transition-fast);
}

.clear:hover {
  background: var(--primary);
  color: #fff;
}

.go {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 36px;
  padding: 0 20px;
  font-size: var(--font-size-base);
  font-weight: 500;
  color: #fff;
  background: var(--primary);
  border-radius: var(--radius-base);
  border: 1px solid var(--primary);
  white-space: nowrap;
  transition: all var(--transition-fast);
  box-shadow: 0 2px 4px rgba(180, 106, 255, 0.2);
}

.search-box.big .go {
  height: 48px;
  padding: 0 32px;
  font-size: var(--font-size-lg);
  border-radius: var(--radius-base);
}

.go:hover {
  background: var(--primary-light);
  border-color: var(--primary-light);
}

.go:active {
  background: var(--primary-dark);
  border-color: var(--primary-dark);
}

@media (max-width: 767px) {
  .search-box {
    gap: 8px;
  }

  .go {
    padding: 0 16px;
  }

  .search-box.big .go {
    padding: 0 24px;
  }
}
</style>