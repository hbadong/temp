import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../pages/home/HomePage.vue')
  },
  {
    path: '/baobao',
    name: 'BabyName',
    component: () => import('../pages/baby-name/BabyNamePage.vue')
  },
  {
    path: '/bazi',
    name: 'BaziName',
    component: () => import('../pages/bazi-name/BaziNamePage.vue')
  },
  {
    path: '/shici',
    name: 'PoetryName',
    component: () => import('../pages/poetry-name/PoetryNamePage.vue')
  },
  {
    path: '/xingmingceshi',
    name: 'NameTest',
    component: () => import('../pages/name-test/NameTestPage.vue')
  },
  {
    path: '/gongsiqiming',
    name: 'CompanyName',
    component: () => import('../pages/company-name/CompanyNamePage.vue')
  },
  {
    path: '/kxzd',
    name: 'Kanxi',
    component: () => import('../pages/kanxi/KanxiPage.vue')
  },
  {
    path: '/baijiaxing',
    name: 'Surname',
    component: () => import('../pages/surname/SurnamePage.vue')
  },
  {
    path: '/zhishi/:category?',
    name: 'Articles',
    component: () => import('../pages/articles/ArticlesPage.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
