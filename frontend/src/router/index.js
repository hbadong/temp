import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Analysis from '../views/Analysis.vue'
import NamesList from '../views/NamesList.vue'

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/analysis', name: 'Analysis', component: Analysis },
  { path: '/names', name: 'NamesList', component: NamesList }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
