<template>
  <form class="search-box" :class="{ big }" @submit.prevent="onSearch">
    <div class="input-wrap">
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
      <button v-if="keyword" type="button" class="clear" @click="keyword = ''">×</button>
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
  background: #fff;
  border: 2px solid transparent;
  border-radius: 12px;
  padding: 0 12px;
  box-shadow: 0 2px 12px rgba(47, 107, 255, 0.08);
  transition: border-color 0.15s;
}

.search-box.big .input-wrap {
  height: 54px;
  border-radius: 27px;
}

.input-wrap:focus-within {
  border-color: var(--primary);
}

.icon {
  color: var(--text-3);
  flex-shrink: 0;
}

.input-wrap input {
  flex: 1;
  border: none;
  padding: 13px 10px;
  font-size: 15px;
  background: transparent;
}

.clear {
  border: none;
  background: var(--border);
  color: #fff;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  line-height: 1;
  font-size: 13px;
  flex-shrink: 0;
}

.go {
  border: none;
  background: var(--primary);
  color: #fff;
  font-size: 15px;
  border-radius: 12px;
  padding: 0 26px;
  box-shadow: 0 4px 14px rgba(47, 107, 255, 0.35);
  transition: background 0.15s;
}

.search-box.big .go {
  border-radius: 27px;
  padding: 0 34px;
}

.go:hover {
  background: var(--primary-dark);
}

@media (max-width: 640px) {
  .go {
    padding: 0 16px;
  }
}
</style>
