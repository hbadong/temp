import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  timeout: 10000
})

export const baziApi = {
  analyze(data) {
    return api.post('/bazi/analyze', data)
  }
}

export const nameApi = {
  analyze(data) {
    return api.post('/name/analyze', data)
  },
  getNames(params) {
    return api.get('/names', { params })
  },
  recommend(data) {
    return api.post('/names/recommend', null, { params: data })
  }
}

export default api
