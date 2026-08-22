<template>
  <div class="admin-page">
    <template v-if="!loggedIn">
      <div class="login-wrap">
        <div class="card login-card">
          <h2>盘搜管理后台</h2>
          <p class="tip">请输入管理令牌登录</p>
          <form @submit.prevent="login">
            <div class="form-item">
              <input v-model="token" type="password" placeholder="Admin Token" required />
            </div>
            <button class="btn btn-primary" style="width: 100%" type="submit" :disabled="busy">
              {{ busy ? '验证中...' : '登录' }}
            </button>
          </form>
          <p class="back"><router-link to="/">返回首页</router-link></p>
        </div>
      </div>
    </template>

    <template v-else>
      <header class="header">
        <div class="container header-inner">
          <span class="logo">盘搜后台</span>
          <nav>
            <a
              v-for="t in tabs"
              :key="t.key"
              :class="{ active: currentTab === t.key }"
              @click.prevent="currentTab = t.key"
            >{{ t.label }}</a>
          </nav>
          <button class="btn btn-sm" @click="logout">退出</button>
        </div>
      </header>
      <main class="container main">
        <DashboardPanel v-if="currentTab === 'dashboard'" />
        <ResourcesPanel v-else-if="currentTab === 'resources'" />
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

export default {
  name: 'AdminView',
  components: {
    DashboardPanel,
    ResourcesPanel,
    ChannelsPanel,
    SensitivePanel,
    FeedbacksPanel,
    LogsPanel,
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
}

.login-card {
  width: min(360px, calc(100vw - 32px));
  padding: 28px;
}

.login-card h2 {
  font-size: 18px;
  margin-bottom: 6px;
}

.tip {
  color: var(--text-3);
  font-size: 13px;
  margin-bottom: 16px;
}

.back {
  margin-top: 14px;
  text-align: center;
  font-size: 13px;
}

.header {
  background: #fff;
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 50;
}

.header-inner {
  height: 54px;
  display: flex;
  align-items: center;
  gap: 20px;
}

.logo {
  font-weight: 700;
  color: var(--primary);
  font-size: 17px;
  flex-shrink: 0;
}

.header nav {
  display: flex;
  gap: 4px;
  overflow-x: auto;
  flex: 1;
}

.header nav a {
  padding: 6px 12px;
  border-radius: 6px;
  color: var(--text-2);
  cursor: pointer;
  font-size: 13px;
  white-space: nowrap;
}

.header nav a.active {
  background: #eef3ff;
  color: var(--primary);
  font-weight: 500;
}

.main {
  padding-top: 20px;
  padding-bottom: 40px;
}
</style>
