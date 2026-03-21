<template>
  <a-layout class="admin-layout">
    <a-layout-sider v-model:collapsed="collapsed" :trigger="null" collapsible class="sider">
      <div class="logo">
        <img src="@/assets/images/logo.png" alt="logo" v-if="!collapsed" />
        <span v-if="!collapsed">起名网</span>
        <span v-else>QM</span>
      </div>
      <a-menu v-model:selectedKeys="selectedKeys" theme="dark" mode="inline">
        <a-menu-item key="/">
          <dashboard-outlined />
          <span>仪表盘</span>
        </a-menu-item>
        <a-menu-item key="/users">
          <user-outlined />
          <span>用户管理</span>
        </a-menu-item>
        <a-menu-item key="/names">
          <book-outlined />
          <span>名字库</span>
        </a-menu-item>
        <a-menu-item key="/orders">
          <shopping-cart-outlined />
          <span>订单管理</span>
        </a-menu-item>
        <a-menu-item key="/articles">
          <file-text-outlined />
          <span>文章管理</span>
        </a-menu-item>
        <a-menu-item key="/config">
          <setting-outlined />
          <span>系统配置</span>
        </a-menu-item>
      </a-menu>
    </a-layout-sider>
    
    <a-layout>
      <a-layout-header class="header">
        <menu-unfold-outlined v-if="collapsed" class="trigger" @click="() => collapsed = !collapsed" />
        <menu-fold-outlined v-else class="trigger" @click="() => collapsed = !collapsed" />
        
        <div class="header-right">
          <a-dropdown>
            <a-avatar class="avatar">
              <template #icon><user-outlined /></template>
            </a-avatar>
            <template #overlay>
              <a-menu>
                <a-menu-item key="profile">个人中心</a-menu-item>
                <a-menu-divider />
                <a-menu-item key="logout" @click="handleLogout">退出登录</a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
        </div>
      </a-layout-header>
      
      <a-layout-content class="content">
        <router-view />
      </a-layout-content>
    </a-layout>
  </a-layout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  DashboardOutlined,
  UserOutlined,
  BookOutlined,
  ShoppingCartOutlined,
  FileTextOutlined,
  SettingOutlined,
  MenuUnfoldOutlined,
  MenuFoldOutlined
} from '@ant-design/icons-vue'

const router = useRouter()
const route = useRoute()
const collapsed = ref(false)
const selectedKeys = ref(['/'])

watch(() => route.path, (path) => {
  selectedKeys.value = [path]
}, { immediate: true })

const handleLogout = () => {
  localStorage.removeItem('adminToken')
  router.push('/login')
}
</script>

<style lang="scss" scoped>
.admin-layout {
  min-height: 100vh;
}

.sider {
  .logo {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 64px;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    font-size: 18px;
    font-weight: bold;

    img {
      height: 32px;
      margin-right: 8px;
    }
  }
}

.header {
  background: #fff;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

  .trigger {
    font-size: 18px;
    cursor: pointer;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 16px;

    .avatar {
      cursor: pointer;
    }
  }
}

.content {
  margin: 24px;
  padding: 24px;
  background: #fff;
  border-radius: 4px;
  min-height: calc(100vh - 112px);
}
</style>
