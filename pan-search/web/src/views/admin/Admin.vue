<template>
  <div class="admin-page">
    <template v-if="!loggedIn">
      <div class="login-wrap">
        <div class="card login-card">
          <h2 class="login-card__title">盘搜管理后台</h2>
          <p class="login-card__tip">请输入管理令牌登录</p>
          <form @submit.prevent="login" class="form">
            <div class="form-item">
              <div class="form-item__content">
                <input
                  v-model="token"
                  type="password"
                  class="form-item__input"
                  placeholder="Admin Token"
                  required
                />
              </div>
            </div>
            <div class="form-item form-item--submit">
              <button class="btn btn-primary btn-lg" style="width: 100%" type="submit" :disabled="busy">
                {{ busy ? '验证中...' : '登录' }}
              </button>
            </div>
          </form>
          <p class="back"><router-link to="/">返回首页</router-link></p>
        </div>
      </div>
    </template>

    <template v-else>
      <header class="header">
        <div class="container header-inner">
          <span class="logo">盘搜后台</span>
          <nav class="tabs">
            <a
              v-for="t in tabs"
              :key="t.key"
              :class="['tabs__item', { 'tabs__item--active': currentTab === t.key }]"
              @click.prevent="currentTab = t.key"
            >{{ t.label }}</a>
          </nav>
          <div style="display: flex; align-items: center; gap: 12px;">
            <ThemeToggle />
            <button class="btn btn-sm" @click="logout">退出</button>
          </div>
        </div>
      </header>
      <main class="container main">
        <DashboardPanel v-if="currentTab === 'dashboard'" />
        <ResourcesPanel v-else-if="currentTab === 'resources'" />
        <SubmissionsPanel v-else-if="currentTab === 'submissions'" />
        <ChannelsPanel v-else-if="currentTab === 'channels'" />
        <SensitivePanel v-else-if="currentTab === 'sensitive'" />
        <FeedbacksPanel v-else-if="currentTab === 'feedbacks'" />
        <LogsPanel v-else />
      </main>
    </template>
  </div>
</template>

<script>
import { adminLogin } from '../../api'
import DashboardPanel from './DashboardPanel.vue'
import ResourcesPanel from './ResourcesPanel.vue'
import ChannelsPanel from './ChannelsPanel.vue'
import SensitivePanel from './SensitivePanel.vue'
import FeedbacksPanel from './FeedbacksPanel.vue'
import LogsPanel from './LogsPanel.vue'
import SubmissionsPanel from './SubmissionsPanel.vue'
import ThemeToggle from '../../components/ThemeToggle.vue'

export default {
  name: 'AdminView',
  components: {
    DashboardPanel,
    ResourcesPanel,
    ChannelsPanel,
    SensitivePanel,
    FeedbacksPanel,
    LogsPanel,
    SubmissionsPanel,
    ThemeToggle,
  },
  data() {
    return {
      loggedIn: !!localStorage.getItem('admin_token'),
      token: '',
      busy: false,
      currentTab: 'dashboard',
      tabs: [
        { key: 'dashboard', label: '数据监控' },
        { key: 'resources', label: '资源管理' },
        { key: 'submissions', label: '提交审核' },
        { key: 'channels', label: '频道管理' },
        { key: 'sensitive', label: '敏感词' },
        { key: 'feedbacks', label: '用户反馈' },
        { key: 'logs', label: '采集日志' },
      ],
    }
  },
  methods: {
    async login() {
      this.busy = true
      try {
        await adminLogin(this.token)
        localStorage.setItem('admin_token', this.token)
        this.loggedIn = true
      } catch (err) {
        this.$toast(err.message)
      } finally {
        this.busy = false
      }
    },
    logout() {
      localStorage.removeItem('admin_token')
      this.loggedIn = false
      this.token = ''
    },
  },
}
</script>

<style scoped>
.login-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.login-card {
  width: min(360px, calc(100vw - 32px));
  padding: 30px;
}

.login-card__title {
  font-size: var(--font-size-title);
  line-height: 24px;
  font-weight: 500;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.login-card__tip {
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  margin-bottom: 24px;
}

.back {
  margin-top: 20px;
  text-align: center;
  font-size: var(--font-size-sm);
}

.back a {
  color: var(--text-secondary);
  transition: color var(--transition-fast);
}

.back a:hover {
  color: var(--primary);
}

.header {
  background: var(--bg-color);
  border-bottom: 1px solid var(--border-divider);
  position: sticky;
  top: 0;
  z-index: 50;
}

.header-inner {
  height: 56px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.logo {
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--text-primary);
  flex-shrink: 0;
}

.tabs {
  display: flex;
  gap: 4px;
  flex: 1;
  overflow-x: auto;
}

.tabs__item {
  display: flex;
  align-items: center;
  padding: 6px 14px;
  height: 36px;
  font-size: var(--font-size-sm);
  color: var(--text-regular);
  border-radius: var(--radius-base);
  white-space: nowrap;
  transition: all var(--transition-fast);
  cursor: pointer;
}

.tabs__item:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.tabs__item.tabs__item--active {
  background: var(--primary-bg);
  color: var(--primary);
  font-weight: 600;
}

.main {
  padding-top: 24px;
  padding-bottom: 40px;
}

@media (max-width: 700px) {
  .login-card {
    padding: 20px 16px;
  }

  .header-inner {
    flex-wrap: wrap;
    height: auto;
    padding: 12px 0;
    gap: 12px;
  }

  .tabs {
    order: 3;
    flex-basis: 100%;
  }
}
</style>