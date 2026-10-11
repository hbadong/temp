async function request(url, options = {}) {
  const headers = { ...(options.headers || {}) }
  if (options.body && !(options.body instanceof ArrayBuffer) && !(options.body instanceof Blob) && !(options.body instanceof Uint8Array)) {
    headers['Content-Type'] = 'application/json'
  }
  const res = await fetch(url, { credentials: 'include', ...options, headers })
  if (res.status === 401) {
    if (!location.pathname.startsWith('/login') && !location.pathname.startsWith('/admin/login') && location.pathname !== '/') {
      const login = location.pathname.startsWith('/admin') ? '/admin/login' : '/login'
      location.assign(login)
    }
    const data = await res.json().catch(() => ({ code: 0, msg: '未登录', data: {} }))
    throw Object.assign(new Error(data.msg || '未登录'), { auth: true, data })
  }
  const data = await res.json()
  if (data.code !== 1) {
    throw Object.assign(new Error(data.msg || '失败'), { data })
  }
  return data.data
}

export const api = {
  get: (url) => request(url),
  post: (url, body) => request(url, { method: 'POST', body: JSON.stringify(body || {}) }),
  put: (url, body) => request(url, { method: 'PUT', body: JSON.stringify(body || {}) }),
  del: (url) => request(url, { method: 'DELETE' }),
}
