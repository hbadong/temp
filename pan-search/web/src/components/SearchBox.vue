<template>
  <div class="search-box" :class="{ big }">
    <form @submit.prevent="onSearch">
      <div class="input-wrap" :class="{ focused }" ref="inputWrap">
        <svg class="icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="7" />
          <path d="m20 20-3.5-3.5" />
        </svg>
        <input
          v-model="keyword"
          type="text"
          :placeholder="placeholder"
          enterkeyhint="search"
          @focus="onFocus"
          @blur="onBlur"
          @input="onInput"
          ref="input"
          autocomplete="off"
          aria-autocomplete="list"
          aria-controls="suggestions-list"
          aria-expanded="showSuggestions"
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

    <div v-if="showSuggestions" class="suggestions-dropdown" ref="suggestionsDropdown" id="suggestions-list" role="listbox">
      <div v-if="suggestions.length" class="suggestions-section">
        <div class="suggestions-header">
          <span>搜索建议</span>
        </div>
        <div class="suggestions-list">
          <button
            v-for="(item, index) in suggestions"
            :key="item"
            class="suggestion-item"
            :class="{ 'suggestion-item--selected': suggestionIndex === index }"
            @click="selectSuggestion(item)"
            @mouseenter="suggestionIndex = index"
            role="option"
          >
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7" />
              <path d="m20 20-3.5-3.5" />
            </svg>
            <span class="suggestion-text">
              <span v-for="(seg, i) in highlightSegments(item)" :key="i" :class="{ 'suggestion-highlight': seg.hit }">{{ seg.text }}</span>
            </span>
          </button>
        </div>
      </div>

      <div v-if="showHistory && history.length" class="suggestions-section">
        <div class="suggestions-header">
          <span>搜索历史</span>
          <button class="clear-history" @click="clearHistory">清空</button>
        </div>
        <div class="suggestions-list">
          <button
            v-for="item in history"
            :key="item"
            class="suggestion-item"
            @click="selectHistory(item)"
            role="option"
          >
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7" />
              <path d="m20 20-3.5-3.5" />
            </svg>
            <span>{{ item }}</span>
          </button>
        </div>
      </div>

      <div v-if="showHistory && !history.length && !suggestions.length" class="suggestions-empty">
        暂无搜索历史，开始搜索吧
      </div>
    </div>
  </div>
</template>

<script>
const HISTORY_KEY = 'pansearch_history'
const MAX_HISTORY = 10

export default {
  name: 'SearchBox',
  props: {
    modelValue: { type: String, default: '' },
    big: { type: Boolean, default: false },
    placeholder: { type: String, default: '输入资源名称，如：三体、Photoshop、4K 电影' },
  },
  emits: ['update:modelValue', 'search', 'input'],
  data() {
    return {
      keyword: this.modelValue,
      focused: false,
      showHistory: false,
      history: [],
      suggestions: [],
      suggestionIndex: -1,
      debounceTimer: null,
      suggestionTimer: null,
    }
  },
  computed: {
    showSuggestions() {
      return this.focused && (this.suggestions.length > 0 || (this.showHistory && this.history.length > 0))
    },
  },
  watch: {
    modelValue(v) {
      this.keyword = v
    },
    keyword(v) {
      this.$emit('update:modelValue', v)
      this.fetchSuggestions(v)
    },
    focused(v) {
      if (v && this.history.length) {
        this.showHistory = true
      }
    },
  },
  mounted() {
    this.loadHistory()
    document.addEventListener('click', this.handleOutsideClick)
    document.addEventListener('keydown', this.handleKeydown)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideClick)
    document.removeEventListener('keydown', this.handleKeydown)
    if (this.debounceTimer) clearTimeout(this.debounceTimer)
    if (this.suggestionTimer) clearTimeout(this.suggestionTimer)
  },
  methods: {
    loadHistory() {
      try {
        const data = localStorage.getItem(HISTORY_KEY)
        this.history = data ? JSON.parse(data) : []
      } catch {
        this.history = []
      }
    },
    saveHistory() {
      try {
        localStorage.setItem(HISTORY_KEY, JSON.stringify(this.history.slice(0, MAX_HISTORY)))
      } catch {}
    },
    addHistory(q) {
      const trimmed = q.trim()
      if (!trimmed) return
      this.history = [trimmed, ...this.history.filter((h) => h !== trimmed)]
      this.saveHistory()
    },
    clearHistory() {
      this.history = []
      this.saveHistory()
    },
    selectHistory(item) {
      this.keyword = item
      this.onSearch()
    },
    async fetchSuggestions(q) {
      const trimmed = q.trim()
      if (!trimmed || trimmed.length < 1) {
        this.suggestions = []
        this.suggestionIndex = -1
        return
      }
      if (this.suggestionTimer) clearTimeout(this.suggestionTimer)
      this.suggestionTimer = setTimeout(async () => {
        try {
          const { getSearchSuggestions } = await import('../api-suggest')
          const { suggestions } = await getSearchSuggestions(trimmed, 8)
          this.suggestions = suggestions
          this.suggestionIndex = -1
        } catch {
          this.suggestions = []
        }
      }, 150)
    },
    highlightSegments(text) {
      const q = this.keyword.trim().toLowerCase()
      if (!q) return [{ text, hit: false }]
      const segments = []
      let current = ''
      let currentState = false
      for (let i = 0; i < text.length; i++) {
        const isMatch = text.slice(i, i + q.length).toLowerCase() === q
        if (isMatch) {
          if (current) {
            segments.push({ text: current, hit: currentState })
            current = ''
          }
          segments.push({ text: text.slice(i, i + q.length), hit: true })
          i += q.length - 1
        } else {
          if (currentState !== false) {
            segments.push({ text: current, hit: currentState })
            current = ''
          }
          current += text[i]
          currentState = false
        }
      }
      if (current) segments.push({ text: current, hit: currentState })
      return segments.length > 1 ? segments : [{ text, hit: false }]
    },
    onFocus() {
      this.focused = true
      if (this.keyword.trim()) {
        this.fetchSuggestions(this.keyword)
      }
    },
    onBlur() {
      setTimeout(() => {
        this.focused = false
        this.showHistory = false
        this.suggestions = []
        this.suggestionIndex = -1
      }, 200)
    },
    onInput() {
      if (this.debounceTimer) clearTimeout(this.debounceTimer)
      this.debounceTimer = setTimeout(() => {
        this.$emit('input', this.keyword.trim())
      }, 300)
    },
    onSearch() {
      const q = this.keyword.trim()
      if (!q) return
      this.addHistory(q)
      this.showHistory = false
      this.suggestions = []
      this.suggestionIndex = -1
      this.$emit('search', q)
    },
    selectSuggestion(item) {
      this.keyword = item
      this.onSearch()
    },
    handleOutsideClick(e) {
      if (!this.$el.contains(e.target)) {
        this.focused = false
        this.showHistory = false
        this.suggestions = []
        this.suggestionIndex = -1
      }
    },
    handleKeydown(e) {
      if (e.key === '/' && (e.metaKey || e.ctrlKey)) {
        e.preventDefault()
        this.$refs.input?.focus()
      }
      if (e.key === 'Escape') {
        this.focused = false
        this.showHistory = false
        this.suggestions = []
        this.suggestionIndex = -1
        this.$refs.input?.blur()
      }
      if (this.showSuggestions && this.suggestions.length) {
        if (e.key === 'ArrowDown') {
          e.preventDefault()
          this.suggestionIndex = (this.suggestionIndex + 1) % this.suggestions.length
        } else if (e.key === 'ArrowUp') {
          e.preventDefault()
          this.suggestionIndex = (this.suggestionIndex - 1 + this.suggestions.length) % this.suggestions.length
        } else if (e.key === 'Enter' && this.suggestionIndex >= 0) {
          e.preventDefault()
          this.selectSuggestion(this.suggestions[this.suggestionIndex])
        }
      }
    },
  },
}
</script>

<style scoped>
.search-box {
  display: flex;
  gap: 10px;
  width: 100%;
  position: relative;
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

.history-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: var(--bg-color);
  border: 1px solid var(--border-base);
  border-radius: var(--radius-base);
  box-shadow: var(--shadow-base);
  z-index: 50;
  overflow: hidden;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  border-bottom: 1px solid var(--border-divider);
  font-size: var(--font-size-sm);
  font-weight: 500;
  color: var(--text-secondary);
}

.clear-history {
  background: none;
  border: none;
  color: var(--text-placeholder);
  font-size: var(--font-size-sm);
  cursor: pointer;
  padding: 2px 8px;
  border-radius: var(--radius-sm);
  transition: color var(--transition-fast);
}

.clear-history:hover {
  color: var(--danger);
}

.history-list {
  max-height: 240px;
  overflow-y: auto;
}

.history-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  border: none;
  background: transparent;
  color: var(--text-primary);
  font-size: var(--font-size-base);
  text-align: left;
  cursor: pointer;
  transition: background var(--transition-fast);
}

.history-item:hover {
  background: var(--bg-hover);
}

.history-item svg {
  color: var(--text-secondary);
  flex-shrink: 0;
}

.history-empty {
  padding: 20px 12px;
  text-align: center;
  color: var(--text-placeholder);
  font-size: var(--font-size-sm);
}

@media (max-width: 700px) {
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

.suggestions-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: var(--bg-color);
  border: 1px solid var(--border-base);
  border-radius: var(--radius-base);
  box-shadow: var(--shadow-base);
  z-index: 50;
  overflow: hidden;
  max-height: 400px;
}

.suggestions-section {
  border-bottom: 1px solid var(--border-divider);
}

.suggestions-section:last-child {
  border-bottom: none;
}

.suggestions-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  font-size: var(--font-size-sm);
  font-weight: 500;
  color: var(--text-secondary);
  background: var(--bg-light);
}

.suggestions-list {
  max-height: 280px;
  overflow-y: auto;
}

.suggestion-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  border: none;
  background: transparent;
  color: var(--text-primary);
  font-size: var(--font-size-base);
  text-align: left;
  cursor: pointer;
  transition: background var(--transition-fast);
}

.suggestion-item:hover,
.suggestion-item--selected {
  background: var(--bg-hover);
}

.suggestion-item svg {
  color: var(--text-secondary);
  flex-shrink: 0;
}

.suggestion-text {
  display: flex;
  flex: 1;
  min-width: 0;
}

.suggestion-highlight {
  color: var(--primary);
  font-weight: 600;
}

.suggestions-empty {
  padding: 20px 12px;
  text-align: center;
  color: var(--text-placeholder);
  font-size: var(--font-size-sm);
}
</style>