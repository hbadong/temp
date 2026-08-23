<template>
  <div class="file-node" :class="{ 'file-node--dir': file.is_dir, 'file-node--file': !file.is_dir }">
    <div class="file-node__row" @click="toggle">
      <div class="file-node__indent" :style="{ width: level * 16 + 'px' }"></div>
      <span
        v-if="file.is_dir && file.children && file.children.length > 0"
        class="file-node__toggle"
        :class="{ expanded }"
      >
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 18 15 12 9 6" />
        </svg>
      </span>
      <span v-else class="file-node__toggle" style="visibility: hidden;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 18 15 12 9 6" />
        </svg>
      </span>
      <span class="file-node__icon">
        <svg v-if="file.is_dir" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
        </svg>
        <svg v-else viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" />
          <polyline points="13 2 13 9 20 9" />
        </svg>
      </span>
      <span class="file-node__name" :title="file.name">{{ file.name }}</span>
      <span v-if="!file.is_dir && file.size != null" class="file-node__size">{{ formatSize(file.size) }}</span>
    </div>
    <transition name="slide">
      <div v-show="expanded && file.is_dir && file.children && file.children.length > 0" class="file-node__children">
        <FileTreeNode
          v-for="child in file.children"
          :key="child.id"
          :file="child"
          :level="level + 1"
          :expanded="expanded"
        />
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  name: 'FileTreeNode',
  props: {
    file: { type: Object, required: true },
    level: { type: Number, default: 0 },
    expanded: { type: Boolean, default: false },
  },
  data() {
    return { localExpanded: this.expanded }
  },
  watch: {
    expanded(v) {
      this.localExpanded = v
    },
  },
  methods: {
    toggle() {
      if (this.file.is_dir && this.file.children && this.file.children.length > 0) {
        this.localExpanded = !this.localExpanded
        this.$emit('toggle', this.file.id, this.localExpanded)
      }
    },
    formatSize(bytes) {
      if (bytes === null || bytes === undefined) return ''
      if (bytes === 0) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },
  },
}
</script>

<style scoped>
.file-node {
  font-size: var(--font-size-sm);
}

.file-node__row {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 8px;
  border-radius: var(--radius-base);
  cursor: pointer;
  transition: background-color var(--transition-fast);
  user-select: none;
}

.file-node__row:hover {
  background: var(--bg-hover);
}

.file-node__indent {
  flex-shrink: 0;
}

.file-node__toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  color: var(--text-secondary);
  flex-shrink: 0;
  transition: transform var(--transition-fast);
}

.file-node__toggle.expanded {
  transform: rotate(90deg);
}

.file-node__icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-node__name {
  flex: 1;
  min-width: 0;
  word-break: break-all;
  color: var(--text-primary);
}

.file-node__size {
  color: var(--text-secondary);
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
  flex-shrink: 0;
  padding-left: 12px;
}

.file-node__children {
  padding-left: 16px;
  border-left: 1px solid var(--border-divider);
  margin-left: 14px;
}

.file-node--file .file-node__icon {
  color: var(--text-secondary);
}

.file-node--dir .file-node__icon {
  color: var(--warning);
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.2s ease;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
  height: 0;
  overflow: hidden;
}
</style>