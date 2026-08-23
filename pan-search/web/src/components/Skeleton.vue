<template>
  <div class="skeleton" :class="{ 'skeleton--card': card }">
    <div v-if="card" class="skeleton-card">
      <div class="skeleton-row">
        <div class="skeleton-item skeleton-item--tag"></div>
        <div class="skeleton-item skeleton-item--title"></div>
        <div class="skeleton-item skeleton-item--tag"></div>
      </div>
      <div class="skeleton-row">
        <div class="skeleton-item skeleton-item--meta"></div>
        <div class="skeleton-item skeleton-item--meta"></div>
        <div class="skeleton-item skeleton-item--meta"></div>
        <div class="skeleton-item skeleton-item--meta"></div>
      </div>
      <div class="skeleton-row skeleton-row--actions">
        <div class="skeleton-item skeleton-item--btn"></div>
        <div class="skeleton-item skeleton-item--btn"></div>
        <div class="skeleton-item skeleton-item--btn"></div>
      </div>
    </div>
    <div v-else class="skeleton-list">
      <div class="skeleton-item skeleton-item--avatar"></div>
      <div class="skeleton-content">
        <div class="skeleton-item skeleton-item--title"></div>
        <div class="skeleton-item skeleton-item--text"></div>
        <div class="skeleton-item skeleton-item--text skeleton-item--short"></div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Skeleton',
  props: {
    card: { type: Boolean, default: false },
    count: { type: Number, default: 5 },
  },
  computed: {
    items() {
      return Array.from({ length: this.count }, (_, i) => i)
    },
  },
}
</script>

<style scoped>
.skeleton {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton-card {
  padding: 16px 20px;
  background: var(--bg-color);
  border: 1px solid var(--border-base);
  border-radius: var(--radius-card);
}

.skeleton-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.skeleton-row--actions {
  margin-top: 8px;
  gap: 8px;
}

.skeleton-item {
  background: linear-gradient(90deg, var(--bg-light) 25%, var(--bg-hover) 50%, var(--bg-light) 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s ease-in-out infinite;
  border-radius: var(--radius-base);
}

.skeleton-item--tag {
  width: 60px;
  height: 20px;
}

.skeleton-item--title {
  flex: 1;
  min-width: 120px;
  height: 20px;
}

.skeleton-item--meta {
  width: 80px;
  height: 14px;
}

.skeleton-item--btn {
  width: 70px;
  height: 28px;
}

.skeleton-list {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  background: var(--bg-color);
  border: 1px solid var(--border-base);
  border-radius: var(--radius-card);
}

.skeleton-item--avatar {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-circle);
  flex-shrink: 0;
}

.skeleton-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.skeleton-item--text {
  height: 14px;
  width: 100%;
}

.skeleton-item--short {
  width: 60%;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>