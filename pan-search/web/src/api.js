async function request(path, options = {}) {
  const resp = await fetch(`/api${path}`, {
    headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
    ...options,
  })
  const data = await resp.json().catch(() => ({}))
  if (!resp.ok) {
    throw new Error(data.error || `请求失败 (${resp.status})`)
  }
  return data
}

export function search(params) {
  const qs = new URLSearchParams()
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== null && v !== '') qs.set(k, v)
  }
  return request(`/search?${qs.toString()}`)
}

export const getHot = () => request('/hot')
export const getStats = () => request('/stats')
export const reportResource = (id, body) =>
  request(`/resources/${id}/report`, { method: 'POST', body: JSON.stringify(body) })
export const submitFeedback = (body) =>
  request('/feedback', { method: 'POST', body: JSON.stringify(body) })

function adminHeaders() {
  return { 'X-Admin-Token': localStorage.getItem('admin_token') || '' }
}

export function adminLogin(token) {
  return request('/admin/login', { method: 'POST', body: JSON.stringify({ token }) })
}

export const adminGet = (path) => request(path, { headers: adminHeaders() })
export const adminPost = (path, body) =>
  request(path, { method: 'POST', headers: adminHeaders(), body: JSON.stringify(body) })
export const adminPut = (path, body) =>
  request(path, { method: 'PUT', headers: adminHeaders(), body: JSON.stringify(body) })
export const adminDelete = (path) => request(path, { method: 'DELETE', headers: adminHeaders() })
