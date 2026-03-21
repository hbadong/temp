import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../pages/login/LoginPage.vue')
  },
  {
    path: '/',
    component: () => import('../layouts/AdminLayout.vue'),
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('../pages/dashboard/DashboardPage.vue')
      },
      {
        path: 'users',
        name: 'UserManagement',
        component: () => import('../pages/user/UserPage.vue')
      },
      {
        path: 'names',
        name: 'NameManagement',
        component: () => import('../pages/name/NamePage.vue')
      },
      {
        path: 'orders',
        name: 'OrderManagement',
        component: () => import('../pages/order/OrderPage.vue')
      },
      {
        path: 'articles',
        name: 'ArticleManagement',
        component: () => import('../pages/article/ArticlePage.vue')
      },
      {
        path: 'config',
        name: 'SystemConfig',
        component: () => import('../pages/config/ConfigPage.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory('/admin'),
  routes
})

export default router
