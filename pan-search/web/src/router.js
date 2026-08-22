import { createRouter, createWebHistory } from 'vue-router'
import Home from './views/Home.vue'
import Search from './views/Search.vue'
import Complain from './views/Complain.vue'
import Admin from './views/admin/Admin.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: Home },
    { path: '/search', component: Search },
    { path: '/complain', component: Complain },
    { path: '/admin', component: Admin },
  ],
})

export default router
