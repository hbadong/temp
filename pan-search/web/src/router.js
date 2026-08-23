import { createRouter, createWebHistory } from 'vue-router'

const Home = () => import('./views/Home.vue')
const Search = () => import('./views/Search.vue')
const Detail = () => import('./views/DetailView.vue')
const Redirect = () => import('./views/RedirectView.vue')
const Complain = () => import('./views/Complain.vue')
const Submit = () => import('./views/Submit.vue')
const Admin = () => import('./views/admin/Admin.vue')

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: Home },
    { path: '/search', component: Search },
    { path: '/detail/:id', component: Detail, props: true },
    { path: '/redirect/:id', component: Redirect, props: true },
    { path: '/complain', component: Complain },
    { path: '/submit', component: Submit },
    { path: '/admin', component: Admin },
  ],
})

export default router
