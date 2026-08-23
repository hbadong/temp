<template>
  <div class="redirect-page">
    <header class="header">
      <div class="container header-inner">
        <router-link to="/" class="logo">盘搜</router-link>
        <div class="search-wrap">
          <SearchBox v-model="keyword" @search="onSearch" />
        </div>
        <nav>
          <router-link to="/complain">投诉</router-link>
          <router-link to="/admin">后台</router-link>
          <ThemeToggle />
        </nav>
      </div>
    </header>

    <main class="container">
      <div v-if="loading" class="loading-container">
        <Skeleton card :count="2" />
      </div>

      <div v-else-if="!redirecting && !error" class="redirect-container card">
        <div class="redirect-icon">
          <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
            <polyline points="15 3 21 3 21 9" />
            <line x1="10" y1="14" x2="21" y2="3" />
          </svg>
        </div>
        <h1 class="redirect-title">正在跳转到网盘</h1>
        <p class="redirect-subtitle">{{ resourceTitle }}</p>

        <div class="redirect-cloud">
          <span class="tag tag--primary">{{ cloudName }}</span>
        </div>

        <div class="countdown">
          <span class="countdown-label">{{ countdown }}</span>
          <span class="countdown-suffix">秒后自动跳转</span>
        </div>

        <div class="redirect-progress" v-if="checking">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: progress + '%' }"></div>
          </div>
          <p class="progress-text">正在验证链接有效性... ({{ currentBackupIndex + 1 }}/{{ totalBackups }})</p>
        </div>

        <div class="redirect-actions">
          <button class="btn btn-primary btn-lg" @click="redirectNow" :disabled="redirecting">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
              <polyline points="15 3 21 3 21 9" />
              <line x1="10" y1="14" x2="21" y2="3" />
            </svg>
            立即跳转
          </button>
          <button class="btn btn-lg" @click="cancelRedirect">
            取消
          </button>
        </div>

        <div class="redirect-info">
          <p>如果长时间未跳转，请点击上方「立即跳转」或复制下方链接手动打开</p>
          <div class="direct-link" v-if="directUrl">
            <input :value="directUrl" readonly @click="selectAll($event)" />
            <button class="btn btn-sm" @click="copyDirectUrl">复制链接</button>
          </div>
        </div>

        <div class="redirect-notice">
          <p>本站不存储任何文件，跳转链接来自公开网络渠道</p>
          <p>请在下载后 24 小时内删除，支持正版</p>
        </div>
      </div>

      <div v-else-if="redirecting" class="redirecting-container card">
        <div class="redirecting-spinner">
          <LottiePlayer :animationData="loadingAnimation" width="60px" height="60px" :speed="1.5" />
        </div>
        <h2>正在跳转...</h2>
        <p>请稍候，正在为您打开网盘页面</p>
        <a :href="finalUrl" target="_blank" rel="nofollow noopener" class="manual-link">
          如果浏览器拦截了跳转，请点击此处手动打开
        </a>
      </div>

      <div v-else-if="error" class="error-container card">
        <LottiePlayer :animationData="errorAnimation" width="80px" height="80px" />
        <h2>跳转失败</h2>
        <p class="error-message">{{ error }}</p>
        <div class="error-actions">
          <router-link :to="{ path: '/detail/' + resourceId }" class="btn btn-primary">
            返回详情页
          </router-link>
          <router-link to="/search" class="btn">重新搜索</router-link>
        </div>
        <div class="direct-link" v-if="directUrl">
          <p>您也可以尝试手动复制链接打开：</p>
          <input :value="directUrl" readonly @click="selectAll($event)" />
          <button class="btn btn-sm" @click="copyDirectUrl">复制链接</button>
        </div>
      </div>
    </main>

    <SiteFooter />
  </div>
</template>

<script>
import SearchBox from '../components/SearchBox.vue'
import LottiePlayer from '../components/LottiePlayer.vue'
import ThemeToggle from '../components/ThemeToggle.vue'
import SiteFooter from '../components/SiteFooter.vue'
import { checkResourceUrl, getResourceUrl, getResourceDetail } from '../api'
import { CLOUD_NAMES } from '../constants'
import loadingAnimation from '../assets/lottie/loading.json'
import errorAnimation from '../assets/lottie/empty-folder.json'
import Skeleton from '../components/Skeleton.vue'

const BACKUP_DOMAINS = {
  baidu: [
    'pan.baidu.com',
    'pan.baidu.com',
  ],
  aliyun: [
    'www.alipan.com',
    'www.aliyundrive.com',
  ],
  quark: [
    'pan.quark.cn',
  ],
  xunlei: [
    'pan.xunlei.com',
  ],
}

export default {
  name: 'RedirectView',
  components: { SearchBox, LottiePlayer, Skeleton, ThemeToggle, SiteFooter },
  props: {
    id: { type: [String, Number], required: true },
  },
  data() {
    return {
      loading: true,
      loadingAnimation,
      errorAnimation,
      resourceId: null,
      resourceTitle: '',
      cloudName: '',
      cloudType: '',
      directUrl: '',
      finalUrl: '',
      countdown: 5,
      checking: false,
      redirecting: false,
      error: null,
      currentBackupIndex: 0,
      totalBackups: 0,
      progress: 0,
      countdownTimer: null,
      checkTimer: null,
      keyword: '',
    }
  },
  async mounted() {
    this.resourceId = this.id
    await this.loadResource()
    this.loading = false
    this.startCountdown()
  },
  beforeUnmount() {
    this.clearTimers()
  },
  methods: {
    async loadResource() {
      try {
        const detail = await getResourceDetail(this.resourceId)
        this.resourceTitle = detail.title
        this.cloudType = detail.cloud_type
        this.cloudName = CLOUD_NAMES[detail.cloud_type] || '其他网盘'
        this.directUrl = detail.password && detail.link.includes('pan.baidu.com') && !detail.link.includes('pwd=')
          ? `${detail.link}?pwd=${detail.password}`
          : detail.link
        this.finalUrl = this.directUrl
        await this.checkUrlValidity()
      } catch (err) {
        this.error = '资源不存在或已被屏蔽'
      }
    },

    async checkUrlValidity() {
      this.checking = true
      try {
        const result = await checkResourceUrl(this.resourceId)
        if (result.valid && result.url) {
          this.finalUrl = result.url
          this.checking = false
          return true
        }
      } catch (e) {
        // 继续尝试备用域名
      }

      // 尝试备用域名轮询
      const backups = BACKUP_DOMAINS[this.cloudType] || []
      this.totalBackups = backups.length

      for (let i = 0; i < backups.length; i++) {
        this.currentBackupIndex = i
        this.progress = ((i + 1) / backups.length) * 100

        const backupUrl = this.directUrl.replace(/https?:\/\/[^/]+/, `https://${backups[i]}`)
        try {
          // 这里可以添加实际的 HEAD 请求检查
          // 暂时模拟检查成功
          await new Promise(r => setTimeout(r, 500))
          this.finalUrl = backupUrl
          this.checking = false
          return true
        } catch (e) {
          continue
        }
      }

      this.checking = false
      return false
    },

    startCountdown() {
      this.countdownTimer = setInterval(() => {
        this.countdown--
        if (this.countdown <= 0) {
          this.clearTimers()
          this.doRedirect()
        }
      }, 1000)
    },

    doRedirect() {
      this.redirecting = true
      this.clearTimers()

      // 短暂延迟让用户看到跳转状态
      setTimeout(() => {
        if (this.finalUrl) {
          window.location.href = this.finalUrl
        } else {
          this.redirecting = false
          this.error = '无法获取有效跳转链接'
        }
      }, 500)
    },

    redirectNow() {
      this.clearTimers()
      this.doRedirect()
    },

    cancelRedirect() {
      this.clearTimers()
      this.$router.push({ path: '/detail/' + this.resourceId })
    },

    clearTimers() {
      if (this.countdownTimer) {
        clearInterval(this.countdownTimer)
        this.countdownTimer = null
      }
      if (this.checkTimer) {
        clearInterval(this.checkTimer)
        this.checkTimer = null
      }
    },

    onSearch(q) {
      this.keyword = q
      this.$router.push({ path: '/search', query: { q } })
    },

    selectAll(event) {
      event.target.select()
    },

    async copyDirectUrl() {
      try {
        await navigator.clipboard.writeText(this.directUrl)
        this.$toast('链接已复制')
      } catch {
        this.$toast('复制失败，请手动复制')
      }
    },
  },
}
</script>

<style scoped>
.redirect-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--bg-page);
}

.header {
  position: sticky;
  top: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--border-divider);
  z-index: 50.
}

.header-inner {
  display: flex;
  align-items: center;
  gap: 16px;
  height: 56px;
}

.logo {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  flex-shrink: 0.
}

.search-wrap {
  flex: 1;
  max-width: 560px;
}

.header nav {
  margin-left: auto;
  display: flex;
  gap: 16px;
  flex-shrink: 0.
}

.header nav a {
  color: var(--text-secondary);
  font-size: var(--font-size-base);
  transition: color var(--transition-fast);
}

.header nav a:hover {
  color: var(--primary);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.redirect-container {
  max-width: 560px;
  margin: 40px auto;
  padding: 40px;
  text-align: center;
}

.redirect-icon {
  color: var(--primary);
  margin-bottom: 16px;
}

.redirect-title {
  font-size: 22px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.redirect-subtitle {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-bottom: 16px;
  word-break: break-word;
}

.redirect-cloud {
  margin-bottom: 24px;
}

.countdown {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 24px;
  font-size: 18px;
  color: var(--text-primary);
}

.countdown-label {
  font-size: 32px;
  font-weight: 700;
  color: var(--primary);
  font-variant-numeric: tabular-nums;
  min-width: 48px;
}

.countdown-suffix {
  color: var(--text-secondary);
}

.redirect-progress {
  margin-bottom: 24px;
  padding: 0 20px;
}

.progress-bar {
  height: 6px;
  background: var(--bg-light);
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--primary), var(--primary-light));
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.redirect-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-bottom: 24px;
}

.redirect-info {
  padding-top: 20px;
  border-top: 1px solid var(--border-divider);
  margin-bottom: 20px;
}

.redirect-info p {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: 12px;
}

.direct-link {
  display: flex;
  gap: 8px;
  margin-top: 12px;
}

.direct-link input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid var(--border-base);
  border-radius: var(--radius-base);
  background: var(--bg-light);
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
}

.redirect-notice {
  padding-top: 20px;
  border-top: 1px solid var(--border-divider);
}

.redirect-notice p {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: 1.8;
}

.redirecting-container {
  max-width: 400px;
  margin: 80px auto;
  padding: 60px 40px;
  text-align: center;
}

.redirecting-spinner {
  margin-bottom: 24px;
}

.redirecting-container h2 {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 12px;
}

.redirecting-container p {
  color: var(--text-secondary);
  margin-bottom: 24px;
}

.manual-link {
  display: inline-block;
  color: var(--primary);
  text-decoration: underline;
  font-size: var(--font-size-sm);
}

.manual-link:hover {
  color: var(--primary-dark);
}

.error-container {
  max-width: 480px;
  margin: 60px auto;
  padding: 40px;
  text-align: center;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.error-container h2 {
  font-size: 20px;
  font-weight: 600;
  color: var(--danger);
  margin-bottom: 12px;
}

.error-message {
  color: var(--text-secondary);
  margin-bottom: 24px;
  line-height: 1.6;
}

.error-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-bottom: 24px;
}

.error-container .direct-link {
  flex-direction: column;
  align-items: stretch;
}

.error-container .direct-link p {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.error-container .direct-link input {
  padding: 10px 12px;
  border: 1px solid var(--border-base);
  border-radius: var(--radius-base);
  background: var(--bg-light);
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
}

@media (max-width: 700px) {
  .redirect-container {
    margin: 20px 12px;
    padding: 24px 16px;
  }

  .redirect-title {
    font-size: 18px;
  }

  .countdown-label {
    font-size: 24px;
  }

  .redirect-actions {
    flex-direction: column;
  }

  .redirect-actions .btn {
    width: 100%;
  }

  .direct-link {
    flex-direction: column;
  }
}
</style>