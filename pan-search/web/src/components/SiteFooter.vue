<template>
  <footer class="footer">
    <div class="container">
      <div class="footer-links" v-if="hotKeywords.length">
        <span class="footer-links__label">热门：</span>
        <router-link
          v-for="kw in hotKeywords"
          :key="kw"
          :to="{ path: '/search', query: { q: kw } }"
          class="footer-links__item"
        >{{ kw }}</router-link>
      </div>
      <p>
        本站所有内容均来自网络公开渠道，仅用于学习交流，请在下载后 24 小时内删除。
      </p>
      <p>
        <router-link to="/submit">提交资源</router-link>
        <span> · </span>
        <router-link to="/complain">侵权投诉 DMCA</router-link>
        <span> · </span>
        <router-link to="/admin">后台管理</router-link>
      </p>
    </div>
  </footer>
</template>

<script>
import { getHot } from '../api'

export default {
  name: 'SiteFooter',
  data() {
    return { hotKeywords: [] }
  },
  mounted() {
    this.loadHot()
  },
  methods: {
    async loadHot() {
      try {
        const res = await getHot()
        this.hotKeywords = (res.keywords || []).slice(0, 8)
      } catch {
        // silently fail
      }
    },
  },
}
</script>

<style scoped>
.footer {
  padding: 24px 0;
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  text-align: center;
  border-top: 1px solid var(--border-divider);
  margin-top: auto;
}

.footer p {
  margin: 6px 0;
  line-height: 1.8;
}

.footer a {
  color: var(--text-secondary);
  transition: color var(--transition-fast);
  text-decoration: none;
}

.footer a:hover {
  color: var(--primary);
}

.footer span {
  color: var(--text-disabled);
  margin: 0 2px;
}

.footer-links {
  margin-bottom: 10px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 4px 8px;
}

.footer-links__label {
  color: var(--text-placeholder);
}

.footer-links__item {
  color: var(--text-secondary);
  font-size: 12px;
  transition: color var(--transition-fast);
}

.footer-links__item:hover {
  color: var(--primary);
}

@media (max-width: 700px) {
  .footer {
    padding: 16px 0;
  }
}
</style>
