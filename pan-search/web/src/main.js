import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './styles.css'

const app = createApp(App)

let toastTimer = null
app.config.globalProperties.$toast = function (msg) {
  let el = document.querySelector('.toast')
  if (!el) {
    el = document.createElement('div')
    el.className = 'toast'
    document.body.appendChild(el)
  }
  el.textContent = msg
  el.style.display = 'block'
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    el.style.display = 'none'
  }, 2200)
}

app.use(router)
app.mount('#app')
