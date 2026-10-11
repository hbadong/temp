import { createRouter, createWebHistory } from 'vue-router'
import Landing from './views/Landing.vue'
import Login from './views/Login.vue'
import WorkbenchLayout from './layouts/WorkbenchLayout.vue'
import AdminLayout from './layouts/AdminLayout.vue'

const crud = (name) => ({
  path: name,
  component: () => import('./views/app/CrudPage.vue'),
  props: { page: name },
})

const routes = [
  { path: '/', component: Landing },
  { path: '/login', component: Login },
  { path: '/agreement', component: () => import('./views/Agreement.vue') },
  {
    path: '/app',
    component: WorkbenchLayout,
    children: [
      { path: '', redirect: '/app/dashboard' },
      { path: 'dashboard', component: () => import('./views/app/Dashboard.vue') },
      { path: 'analyze-pro', component: () => import('./views/app/AnalyzePro.vue') },
      { path: 'analyze', component: () => import('./views/app/Analyze.vue') },
      { path: 'analyze-reports', component: () => import('./views/app/AnalyzeReports.vue') },
      { path: 'analyze-reports/:id', component: () => import('./views/app/AnalyzeDetail.vue') },
      crud('keywords'),
      { path: 'titles', component: () => import('./views/app/Titles.vue') },
      { path: 'images', component: () => import('./views/app/ImageLib.vue') },
      crud('knowledge'),
      crud('instructions'),
      crud('article-types'),
      crud('write-tasks'),
      crud('articles'),
      crud('reprint'),
      crud('reprint-batch'),
      crud('tdk'),
      crud('video-mix'),
      crud('video-jobs'),
      crud('video-records'),
      { path: 'video-assets', component: () => import('./views/app/VideoAssets.vue') },
      crud('b2b'),
      crud('b2b-tasks'),
      crud('contacts'),
      crud('b2b-records'),
      crud('webmedia'),
      crud('webmedia-tasks'),
      crud('webmedia-records'),
      crud('media-favs-1'),
      crud('zimedia'),
      crud('zimedia-records'),
      crud('media-favs-2'),
      crud('weixin-auth'),
      crud('weixin-tasks'),
      crud('weixin-records'),
      crud('sites'),
      crud('site-tasks'),
      crud('site-records'),
      { path: 'stats', component: () => import('./views/app/Stats.vue') },
      { path: 'inclusion', component: () => import('./views/app/Inclusion.vue') },
      { path: 'inclusion-records', component: () => import('./views/app/Inclusion.vue') },
      { path: 'zhishu', component: () => import('./views/app/Zhishu.vue') },
      { path: 'zhengliu', component: () => import('./views/app/Zhengliu.vue') },
      { path: 'tuozhan', component: () => import('./views/app/Tuozhan.vue') },
      crud('score-log'),
      crud('money-log'),
      { path: 'kyc', component: () => import('./views/app/Kyc.vue') },
      crud('entitlements'),
      crud('proxy'),
      { path: 'profile', component: () => import('./views/app/Profile.vue') },
    ],
  },
  { path: '/admin/login', component: () => import('./views/admin/AdminLogin.vue') },
  {
    path: '/admin',
    component: AdminLayout,
    children: [
      { path: '', redirect: '/admin/kyc' },
      { path: 'kyc', component: () => import('./views/admin/KycAdmin.vue') },
      { path: 'adjust', component: () => import('./views/admin/Adjust.vue') },
      { path: 'tasks', component: () => import('./views/admin/Tasks.vue') },
      { path: 'settings', component: () => import('./views/admin/Settings.vue') },
    ],
  },
]

const router = createRouter({ history: createWebHistory(), routes })
export default router
