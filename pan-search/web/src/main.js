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

// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then((registration) => {
        console.log('[SW] Registered:', registration.scope)
        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              // New version available, notify user
              if (confirm('发现新版本，是否刷新页面更新？')) {
                window.location.reload()
              }
            }
          })
        })
      })
      .catch((error) => {
        console.error('[SW] Registration failed:', error)
      })
  })
}

app.use(router)
app.mount('#app')
