import { createRouter, createWebHistory } from 'vue-router';

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
    path: '/gaimingzi',
    name: 'AdultName',
    component: () => import('../pages/adult-name/AdultNamePage.vue')
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
    path: '/zhouyi',
    name: 'Zhouyi',
    component: () => import('../pages/zhouyi/ZhouyiPage.vue')
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
  },
  {
    path: '/nvhai',
    name: 'GirlName',
    component: () => import('../pages/girl-name/GirlNamePage.vue')
  },
  {
    path: '/nanhai',
    name: 'BoyName',
    component: () => import('../pages/boy-name/BoyNamePage.vue')
  },
  {
    path: '/xingmingpeidui',
    name: 'NameMatch',
    component: () => import('../pages/name-match/NameMatchPage.vue')
  },
  {
    path: '/tangshi',
    name: 'Tangshi',
    component: () => import('../pages/tangshi/TangshiPage.vue')
  },
  {
    path: '/shijing',
    name: 'Shijing',
    component: () => import('../pages/shijing/ShijingPage.vue')
  },
  {
    path: '/songci',
    name: 'Songci',
    component: () => import('../pages/songci/SongciPage.vue')
  },
  {
    path: '/chuci',
    name: 'Chuci',
    component: () => import('../pages/chuci/ChuciPage.vue')
  },
  {
    path: '/dingzi',
    name: 'Dingzi',
    component: () => import('../pages/dingzi/DingziPage.vue')
  },
  {
    path: '/search',
    name: 'Search',
    component: () => import('../pages/search/SearchPage.vue')
  },
  {
    path: '/about',
    name: 'About',
    component: () => import('../pages/about/AboutPage.vue')
  },
  {
    path: '/service',
    name: 'Service',
    component: () => import('../pages/service/ServicePage.vue')
  },
  {
    path: '/copyright',
    name: 'Copyright',
    component: () => import('../pages/copyright/CopyrightPage.vue')
  },
  {
    path: '/busine',
    name: 'Busine',
    component: () => import('../pages/busine/BusinePage.vue')
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;
